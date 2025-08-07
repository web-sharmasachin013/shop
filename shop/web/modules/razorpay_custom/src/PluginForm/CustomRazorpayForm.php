<?php

namespace Drupal\razorpay_custom\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

class CustomRazorpayForm extends PaymentMethodAddForm {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $gateway = $this->entity->getPaymentGateway()->getPlugin();
    $key_id = $gateway->getConfiguration()['key_id'];

    $form['#attached']['library'][] = 'razorpay_custom/razorpay_checkout';
    $form['razorpay_checkout'] = [
      '#markup' => '<button id="rzp-button1">Pay with Razorpay</button>',
    ];

    $form['#attached']['drupalSettings']['razorpay'] = [
      'key' => $key_id,
      'amount' => $this->entity->getAmount()->getNumber() * 100,
      'currency' => $this->entity->getAmount()->getCurrencyCode(),
      'name' => 'My Store',
    ];

    return $form;
  }

}
