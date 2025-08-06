<?php

namespace Drupal\commerce_cart_estimate\Event;

/**
 * Cart Estimate Events.
 */
final class CartEstimateEvents {

  /**
   * Name of the event fired when selecting a shipping rate during the estimate.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart_estimate\Event\ShippingRatesEvent
   */
  public const SELECT_SHIPPING_RATE = 'commerce_cart_estimate.select_shipping_rate';

}
