<?php

namespace Drupal\commerce_cart_estimate;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\LateOrderProcessor as ShippingLateOrderProcessor;

/**
 * Ensures shipment entities are not saved if this is an order estimate.
 *
 * @see \Drupal\commerce_shipping\LateOrderProcessor
 */
class LateOrderProcessor extends ShippingLateOrderProcessor {

  /**
   * {@inheritDoc}
   */
  protected function shouldSave(ShipmentInterface $shipment): bool {
    return !Estimator::orderIsEstimate($shipment->getOrder());
  }

}
