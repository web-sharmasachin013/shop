<?php

namespace Drupal\drupal_commerce_razorpay\Plugin\Commerce\PaymentGateway;




use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\commerce_payment\Attribute\CommercePaymentGateway;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsZeroBalanceOrderInterface;
use Drupal\commerce_payment\PluginForm\PaymentMethodEditForm;
use Drupal\commerce_payment_example\PluginForm\Onsite\PaymentMethodAddForm; // Optional if you provide add form
use Drupal\commerce_price\Price;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\Error as RazorpayError;

/**
 * Provides the Razorpay On-site payment gateway.
 */
#[CommercePaymentGateway(
  id: "razorpay",
  label: new TranslatableMarkup("Razorpay (On-site)"),
  display_label: new TranslatableMarkup("Razorpay"),
  forms: [
    // Provide your own add/edit forms if you support storing payment methods.
    "add-payment-method" => PaymentMethodAddForm::class,
    "edit-payment-method" => PaymentMethodEditForm::class,
  ],
  payment_method_types: ["credit_card"],
  requires_billing_information: FALSE,
)]
class RazorpayCheckout extends OnsitePaymentGatewayBase implements SupportsZeroBalanceOrderInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'key_id' => '',
      'key_secret' => '',
      // optionally, store webhook secret or other settings.
    ] + parent::defaultConfiguration();
  }

  /**
   * Helper: create Razorpay API client.
   *
   * @return \Razorpay\Api\Api
   */
  protected function getApiClient() {
    $key_id = $this->configuration['key_id'] ?? '';
    $key_secret = $this->configuration['key_secret'] ?? '';
    return new Api($key_id, $key_secret);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['key_id'] = $values['key_id'];
      $this->configuration['key_secret'] = $values['key_secret'];
    }
  }

  /**
   * {@inheritdoc}
   *
   * Create a Razorpay Order and set the Payment remote id to the Razorpay order id.
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    // Payment method may be optional if you use on-page Checkout tokens.
    // $this->assertPaymentMethod($payment_method);

    // Build amount in paise if INR. Razorpay expects amount in the smallest currency unit.
    $amount = $payment->getAmount();
    $currency = $amount->getCurrencyCode();
    $number = $amount->getNumber();

    // Convert decimal string to smallest unit integer.
    // Example: "100.00" => 10000 paise.
    $amount_integer = $this->amountToMinorUnits($number, $currency);

    try {
      $api = $this->getApiClient();

      // Create Razorpay Order.
      $order_data = [
        'amount' => $amount_integer,
        'currency' => $currency,
        'receipt' => 'order_' . $payment->id(),
        // 'payment_capture' => $capture ? 1 : 0,
        // You may attach notes or customer id if available.
      ];
      $razorpay_order = $api->order->create($order_data);

      // Save razorpay order id as remote id on the payment entity.
      $payment->setRemoteId($razorpay_order['id']);
      // If you prefer to mark authorization vs completed depending on capture:
      $next_state = $capture ? 'completed' : 'authorization';
      $payment->setState($next_state);

      // Optionally set additional metadata:
      $payment->set('payment_gateway_response', json_encode($razorpay_order));

      $payment->save();
    }
    catch (RazorpayError $e) {
      // Map Razorpay errors to Commerce exceptions. Use HardDecline for unrecoverable declines.
      throw HardDeclineException::createForPayment($payment, $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   *
   * Capture a Razorpay payment. This expects the Payment->remote_id to be the
   * *Razorpay payment id* (not the order id). If you used Checkout, client must
   * send the razorpay_payment_id to Drupal and you should set it on the Payment
   * remote id prior to capture.
   */
  public function capturePayment(PaymentInterface $payment, ?Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization', 'new']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $currency = $amount->getCurrencyCode();

    // The plugin needs a razorpay_payment_id to capture. This often comes from
    // the client-side checkout (razorpay_payment_id) and should be set on the
    // payment->remote_id before capture. If you stored only order id earlier,
    // you'll need to map order->payment id via webhooks or client-provided token.
    $razorpay_payment_id = $payment->getRemoteId();
    if (empty($razorpay_payment_id)) {
      throw new \InvalidArgumentException('Missing Razorpay payment id for capture.');
    }

    $amount_integer = $this->amountToMinorUnits($amount->getNumber(), $currency);

    try {
      $api = $this->getApiClient();
      $payment_obj = $api->payment->fetch($razorpay_payment_id);

      // Perform capture.
      $capture = $payment_obj->capture(['amount' => $amount_integer, 'currency' => $currency]);

      // Update local payment state and amounts.
      $payment->setState('completed');
      $payment->setAmount($amount);
      $payment->set('payment_gateway_response', json_encode($capture));
      $payment->save();
    }
    catch (RazorpayError $e) {
      throw HardDeclineException::createForPayment($payment, $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    // Razorpay: you can cancel an authorization or refund a captured payment.
    // If payment is a captured payment, you must refund; if it's an order that
    // hasn't been paid, you may cancel or ignore.
    // Implement minimal behavior: if we have a razorpay_payment_id and it's not
    // captured, we can attempt to cancel via API (Razorpay doesn't have an explicit void).
    $this->assertPaymentState($payment, ['authorization']);

    $razorpay_payment_id = $payment->getRemoteId();
    if (empty($razorpay_payment_id)) {
      throw new \InvalidArgumentException('Missing Razorpay payment id for void.');
    }

    try {
      $api = $this->getApiClient();
      $payment_obj = $api->payment->fetch($razorpay_payment_id);

      // If payment is captured, we can't void — we refund instead.
      if (!empty($payment_obj['captured'])) {
        // Refund the payment immediately.
        $refund = $payment_obj->refund();
        $payment->setState('authorization_voided');
        $payment->set('payment_gateway_response', json_encode($refund));
      }
      else {
        // If not captured, you can do nothing or update metadata.
        $payment->setState('authorization_voided');
      }
      $payment->save();
    }
    catch (RazorpayError $e) {
      throw HardDeclineException::createForPayment($payment, $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, ?Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $razorpay_payment_id = $payment->getRemoteId();
    if (empty($razorpay_payment_id)) {
      throw new \InvalidArgumentException('Missing Razorpay payment id for refund.');
    }

    $amount_integer = $this->amountToMinorUnits($amount->getNumber(), $amount->getCurrencyCode());

    try {
      $api = $this->getApiClient();
      $payment_obj = $api->payment->fetch($razorpay_payment_id);

      $refund = $payment_obj->refund([
        'amount' => $amount_integer,
      ]);

      // Update refunded amount locally.
      $old_refunded_amount = $payment->getRefundedAmount();
      $new_refunded_amount = $old_refunded_amount ? $old_refunded_amount->add($amount) : $amount;

      if ($new_refunded_amount->lessThan($payment->getAmount())) {
        $payment->setState('partially_refunded');
      }
      else {
        $payment->setState('refunded');
      }

      $payment->setRefundedAmount($new_refunded_amount);
      $payment->set('payment_gateway_response', json_encode($refund));
      $payment->save();
    }
    catch (RazorpayError $e) {
      throw HardDeclineException::createForPayment($payment, $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   *
   * Create a saved PaymentMethod in your local DB and optionally create a
   * Razorpay customer and attach a card token. How you obtain the token depends
   * on your frontend (Checkout) implementation.
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    // $payment_details should include the razorpay token / payment_method_id sent from client.
    if (empty($payment_details['razorpay_payment_method_id']) && empty($payment_details['razorpay_token'])) {
      throw new \InvalidArgumentException('Missing Razorpay payment method token/id.');
    }

    // Example: store remote id and expiry if available.
    $remote_id = $payment_details['razorpay_payment_method_id'] ?? $payment_details['razorpay_token'] ?? NULL;
    if ($remote_id) {
      $payment_method->setRemoteId($remote_id);
    }

    // If you have card details like last4, exp_month/year, store them safely (only last4).
    if (!empty($payment_details['last4'])) {
      $payment_method->card_number = $payment_details['last4'];
    }
    if (!empty($payment_details['exp_month'])) {
      $payment_method->card_exp_month = $payment_details['exp_month'];
    }
    if (!empty($payment_details['exp_year'])) {
      $payment_method->card_exp_year = $payment_details['exp_year'];
    }

    // Optionally create a Razorpay customer for the owner and attach the method.
    $owner = $payment_method->getOwner();
    if ($owner && !$owner->isAnonymous()) {
      // Implement getRemoteCustomerId($owner) and setRemoteCustomerId($owner, $id)
      // if you want to link a Razorpay customer id with the Drupal user.
    }

    // Persist the payment method locally.
    $payment_method->save();
  }

  /**
   * Convert a decimal amount string to minor units (smallest currency unit).
   *
   * @param string $number
   *   Decimal string like "100.00".
   * @param string $currency
   *   Currency code like "INR".
   *
   * @return int
   *   Amount in minor units (e.g., paise).
   */
  protected function amountToMinorUnits($number, $currency) {
    // Most currencies have 2 decimal places. Add special handling if needed.
    $decimal_places = 2;
    // Remove any formatting, ensure string.
    $normalized = (string) $number;
    // Multiply and round to integer.
    return (int) round((float) $normalized * (10 ** $decimal_places));
  }

  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
  // If you stored a remote ID (e.g. a Razorpay token or customer card id) on
  // the payment method, attempt to delete it from Razorpay.
  $remote_id = $payment_method->getRemoteId();
  if (!empty($remote_id)) {
    try {
      $api = $this->getApiClient();

      // Example 1: If remote_id is a Razorpay customer id and you store a card id
      // in a field like $payment_method->card_remote_id, then fetch and remove:
      // $customer = $api->customer->fetch($remote_id);
      // $customer->cards->delete($card_id); // adjust to your actual API shape

      // Example 2: If remote_id is a "payment method token" that can be deleted:
      // (Razorpay's API may not support direct deletion of tokens in all flows;
      // adapt based on how you saved remote_id.)
      // $api->someResource->delete($remote_id);

      // NOTE: Razorpay often requires deleting a card via customer API or simply
      // removing the saved source; change the above to match your token type.

    }
    catch (RazorpayError $e) {
      // If remote deletion fails, you can either log the error or throw a
      // Commerce exception. Logging is safer to avoid blocking deletion locally.
      \Drupal::logger('drupal_commerce_razorpay')->error('Razorpay delete error: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  // Finally, delete the local payment method entity.
  $payment_method->delete();
}

}
