<?php

namespace Drupal\razorpay_integration\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Razorpay\Api\Api;
use Drupal\Core\Url;


/**
 * Razorpay payment form.
 */
class RazorpayForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $payment = $this->entity;
    $amount = $payment->getAmount();
    dump($amount);
    $currency = $amount->getCurrencyCode();
    $total = $amount->getNumber();
    dump($total);

    $gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    dump($gateway_plugin);
    $config = $gateway_plugin->getConfiguration();

    $apiKey = 'rzp_test_R9baazd6HhWHbV';
    $apiSecret = 'MtYF0tUxTUajbRzAt2BXh6QF';

    $api = new Api($apiKey, $apiSecret);
   
    $order = $api->order->create([
      'receipt' => 'order_' . $payment->id(),
      'amount' => $total * 100, // Razorpay expects paise
      'currency' => $currency,
    ]);

    $callback = $this->getNotifyUrl();

    // Redirect to Razorpay checkout.
    return $this->buildRedirectForm(
      $form,
      $form_state,
      'https://checkout.razorpay.com/v1/checkout.js',
      [
        'key_id' => $config['api_key'],
        'amount' => $total * 100,
        'currency' => $currency,
        'name' => 'Drupal Commerce',
        'order_id' => $order['id'],
        'callback_url' => $callback,
      ],
      self::REDIRECT_POST
    );
  }

  protected function getNotifyUrl() {
  return Url::fromRoute('razorpay_integration.notify', [], ['absolute' => TRUE])->toString();
}
}
