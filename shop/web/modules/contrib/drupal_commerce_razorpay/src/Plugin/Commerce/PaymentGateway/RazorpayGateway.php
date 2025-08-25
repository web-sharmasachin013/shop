<?php

namespace Drupal\drupal_commerce_razorpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;



#[CommercePaymentGateway(
  id: "Razorpay",
  label: new TranslatableMarkup("Razorpay With Drupal 11)"),
  display_label: new TranslatableMarkup("Example"),
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

class RazorpayGateway extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('razorpay')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['key_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Razorpay Key ID'),
      '#default_value' => $this->configuration['key_id'] ?? '',
      '#required' => TRUE,
    ];
    $form['key_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Razorpay Key Secret'),
      '#default_value' => $this->configuration['key_secret'] ?? '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'key_id' => '',
      'key_secret' => '',
    ] + parent::defaultConfiguration();
  }

}
