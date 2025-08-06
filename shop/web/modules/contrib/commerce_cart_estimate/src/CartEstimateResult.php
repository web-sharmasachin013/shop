<?php

namespace Drupal\commerce_cart_estimate;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Represents the result of a cart estimation.
 *
 * @see \Drupal\commerce_cart_estimate\EstimatorInterface::estimate()
 */
final class CartEstimateResult {

  /**
   * A rated order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $ratedOrder;

  /**
   * Shipping rates.
   *
   * @var \Drupal\commerce_shipping\ShippingRate[]
   */
  protected $rates;

  /**
   * Constructs a new CartEstimateResult object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $rated_order
   *   A rated order.
   * @param array $rates
   *   An array of shipping rates.
   */
  public function __construct(OrderInterface $rated_order, array $rates) {
    $this->ratedOrder = $rated_order;
    $this->rates = $rates;
  }

  /**
   * Gets the rated order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   A rated order.
   */
  public function getRatedOrder() {
    return $this->ratedOrder;
  }

  /**
   * Gets the shipping rates.
   *
   * @return \Drupal\commerce_shipping\ShippingRate[]
   *   The shipping rates.
   */
  public function getRates() {
    return $this->rates;
  }

}
