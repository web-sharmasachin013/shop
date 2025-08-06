<?php

namespace Drupal\commerce_cart_estimate;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Estimates taxes/shipping rates for orders.
 */
interface EstimatorInterface {

  /**
   * Builds a shipping profile for the given order, using the given address.
   *
   * Note this will populate automatically the administrative_area (i.e. the
   * state for US addresses whenever possible).
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param array $address
   *   An address, with at least the "country_code" key.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   A shipping profile for the given address.
   */
  public function buildShippingProfile(OrderInterface $order, array $address);

  /**
   * Estimates shipping rates/taxes for the given shippable order and profile.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to rate.
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a non-shippable order is passed.
   * @throws \Drupal\commerce_cart_estimate\Exception\CartEstimateException
   *   Thrown when the shipping can not be estimated for a given order/address.
   * @throws \Drupal\commerce_cart_estimate\Exception\NoApplicableShippingRatesException
   *   Thrown when no shipping rates are applicable for a given shipment.
   *
   * @return \Drupal\commerce_cart_estimate\CartEstimateResult|null
   *   A cart estimate, NULL in case the order could not be rated.
   */
  public function estimate(OrderInterface $order, ProfileInterface $profile);

}
