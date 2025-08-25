<?php

namespace Drupal\drupal_commerce_razorpay\Form;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;

/**
 * Razorpay checkout form.
 */
class RazorpayCheckoutForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  // public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
  //   $payment = $this->entity;
  //   $gateway_plugin = $payment->getPaymentGateway()->getPlugin();

  //   $order = $payment->getOrder();

  //   // Example values (replace with actual Razorpay order API integration).
  //   $key_id = $gateway_plugin->getConfiguration()['key_id'];
  //   $amount = $payment->getAmount()->getNumber() * 100; // in paise

  //   $form['#attached']['library'][] = 'drupal_commerce_razorpay/razorpay_sdk';

  //   $form['#attributes']['class'][] = 'razorpay-checkout-form';

  //   $form['razorpay'] = [
  //     '#type' => 'markup',
  //     '#markup' => '<button id="rzp-button1">Pay with Razorpay</button>',
  //   ];

  //   return $this->buildRedirectForm(
  //     $form,
  //     $form_state,
  //     'https://api.razorpay.com/v1/checkout/embedded', // Offsite redirect target
  //     [],
  //     PaymentOffsiteForm::REDIRECT_POST
  //   );
  // }
   public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    //$form = parent::buildConfigurationForm($form, $form_state);
    // $payment = $this->entity;
//dump($payment);
       // $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    exit('IN');

   
  //  $order = $payment->getOrder();

    // Build redirect to Razorpay checkout
    return $this->buildRedirectForm(
      $form,
      $form_state,
      'https://checkout.razorpay.com/v1/checkout.js',
      [],
      self::REDIRECT_POST
    );
  }

}
