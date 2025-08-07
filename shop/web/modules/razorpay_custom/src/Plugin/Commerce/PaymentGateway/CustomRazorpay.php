<?php

namespace Drupal\razorpay_custom\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\Core\Form\FormStateInterface;
use Razorpay\Api\Api;

/**
 * Provides the Custom Razorpay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "custom_razorpay",
 *   label = @Translation("Custom Razorpay"),
 *   display_label = @Translation("Razorpay"),
 *   forms = {
 *     "add-payment" = "Drupal\commerce_payment\PluginForm\PaymentAddForm",
 *     "payment-method" = "Drupal\razorpay_custom\PluginForm\CustomRazorpayForm"
 *   },
 *   payment_method_types = {"credit_card"},
 *   modes = {"test", "live"},
 * )
 */
class CustomRazorpay extends OnsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'key_id' => '',
      'key_secret' => '',
      'mode' => 'test',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['key_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Razorpay Key ID'),
      '#default_value' => $this->configuration['key_id'],
      '#required' => TRUE,
    ];

    $form['key_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Razorpay Key Secret'),
      '#default_value' => $this->configuration['key_secret'],
      '#required' => TRUE,
    ];

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $this->configuration['mode'] ?? 'test',
    ];

    return parent::buildConfigurationForm($form, $form_state) + $form;
  }

  /**
   * Create a payment (signature required by Commerce interfaces).
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   * @param bool $capture
   *   Whether to capture immediately (TRUE) or only authorize (FALSE).
   *
   * @throws \Exception
   *   Throws on failure to create remote order.
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $key = $this->configuration['key_id'];
    $secret = $this->configuration['key_secret'];

    if (empty($key) || empty($secret)) {
      \Drupal::logger('razorpay_custom')->error('Razorpay keys not configured for gateway @id', ['@id' => $this->getPluginId()]);
      throw new \Exception($this->t('Payment gateway not configured.'));
    }

    // Instantiate Razorpay SDK.
    try {
      $api = new Api($key, $secret);
    }
    catch (\Throwable $e) {
      \Drupal::logger('razorpay_custom')->error('Razorpay SDK instantiation failed: @m', ['@m' => $e->getMessage()]);
      throw new \Exception($this->t('Payment gateway initialization failed.'));
    }

    // Convert amount to paise (integer).
    $amount_number = (string) $payment->getAmount()->getNumber();
    $amount_in_paise = (int) round((float) $amount_number * 100);

    $order = $payment->getOrder();
    $receipt = 'order_' . $order->id() . '_' . time();

    $order_data = [
      'receipt' => $receipt,
      'amount' => $amount_in_paise,
      'currency' => $payment->getAmount()->getCurrencyCode(),
      // payment_capture 1 = auto-capture, 0 = authorize only.
      'payment_capture' => $capture ? 1 : 0,
    ];

    try {
      $razorpay_order = $api->order->create($order_data);
    }
    catch (\Throwable $e) {
      \Drupal::logger('razorpay_custom')->error('Razorpay order creation failed: @m', ['@m' => $e->getMessage()]);
      throw new \Exception($this->t('Unable to create payment order with Razorpay.'));
    }

    if (empty($razorpay_order['id'])) {
      \Drupal::logger('razorpay_custom')->error('Razorpay order create returned unexpected response: @r', ['@r' => print_r($razorpay_order, TRUE)]);
      throw new \Exception($this->t('Invalid response from payment gateway.'));
    }

    // Save remote id and appropriate state.
    $payment->setRemoteId($razorpay_order['id']);
    if ($capture) {
      $payment->setState('completed');
    }
    else {
      $payment->setState('authorization');
    }
    $payment->save();
  }

  /**
   * Create (tokenize) a payment method.
   *
   * Minimal implementation: store remote id if provided in $payment_details.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method entity to populate.
   * @param array $payment_details
   *   Details returned from the payment form (e.g., token or remote id).
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface
   *   The (modified) payment method entity.
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    // If the checkout JS returned a razorpay token / id, save it as remoteId.
    if (!empty($payment_details['razorpay_payment_id'])) {
      $payment_method->setRemoteId($payment_details['razorpay_payment_id']);
    }
    elseif (!empty($payment_details['razorpay_card_id'])) {
      // Example: card token id returned from Razorpay.
      $payment_method->setRemoteId($payment_details['razorpay_card_id']);
    }
    else {
      // Fallback: set a placeholder remote id (not ideal for production).
      $payment_method->setRemoteId('local_' . uniqid());
    }

    // You can also set additional billing details here if provided:
    // $payment_method->set('billing_information', $billing_info_array);

    $payment_method->save();
    return $payment_method;
  }

  /**
   * Delete a stored payment method.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method to delete.
   *
   * @return bool
   *   TRUE on success.
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // If you have a remote token id, you may call Razorpay API to delete it.
    // For now, allow deletion and return TRUE.
    return TRUE;
  }

}
