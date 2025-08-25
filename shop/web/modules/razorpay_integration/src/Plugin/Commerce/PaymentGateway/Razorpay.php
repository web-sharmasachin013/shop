<?php

namespace Drupal\razorpay_integration\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;
use Drupal\razorpay_integration\PluginForm\RazorpayForm;

/**
 * Provides the Razorpay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "razorpay",
 *   label = "Razorpay",
 *   display_label = "Razorpay",
 *   forms = {
 *     "offsite-payment" = "Drupal\razorpay_integration\PluginForm\RazorpayForm"
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "visa", "mastercard", "amex"
 *   }
 * )
 */

#[CommercePaymentGateway(
  id: "razorpay",
  label: new TranslatableMarkup("Razorpay)"),
  display_label: new TranslatableMarkup("Razorpay"),
  forms: [
    "offsite-payment" => RazorpayForm::class,
  ],
  payment_method_types: ["credit_card"],
  credit_card_types: [
    "amex",
    "dinersclub",
    "discover",
    "jcb",
    "maestro",
    "mastercard",
    "visa",
  ],
  requires_billing_information: FALSE,
)]
class Razorpay extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => 'rzp_test_R9baazd6HhWHbV',
      'api_secret' => 'MtYF0tUxTUajbRzAt2BXh6QF',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Razorpay API Key'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];
    $form['api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Razorpay API Secret'),
      '#default_value' => $this->configuration['api_secret'],
      '#required' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $remote_id) {
    $payment->setState('completed');
    $payment->setRemoteId($remote_id);
    $payment->save();
  }
}
