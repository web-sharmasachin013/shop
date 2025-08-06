<?php

namespace Drupal\commerce_cart_estimate\Event;

use Drupal\commerce\EventBase;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\ShippingRate;

/**
 * Defines the event for reacting to shipping rate calculation.
 *
 * @see \Drupal\commerce_shipping\Event\ShippingEvents
 */
class SelectShippingRateEvent extends EventBase {

  /**
   * The shipping rates.
   *
   * @var \Drupal\commerce_shipping\ShippingRate[]
   */
  protected $rates;

  /**
   * The rate to apply.
   *
   * @var \Drupal\commerce_shipping\ShippingRate
   */
  protected $rate;

  /**
   * The shipment.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $shipment;

  /**
   * Constructs a new SelectShippingRateEvent.
   *
   * @param \Drupal\commerce_shipping\ShippingRate[] $rates
   *   The shipping rates.
   * @param \Drupal\commerce_shipping\ShippingRate $rate
   *   The shipping rate to apply.
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   */
  public function __construct(array $rates, ShippingRate $rate, ShipmentInterface $shipment) {
    $this->rates = $rates;
    $this->rate = $rate;
    $this->shipment = $shipment;
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

  /**
   * Gets the shipping rate to apply.
   *
   * @return \Drupal\commerce_shipping\ShippingRate
   *   The shipping rate to apply.
   */
  public function getRate() {
    return $this->rate;
  }

  /**
   * Sets the shipping rate to apply for the shipping estimate.
   *
   * @param \Drupal\commerce_shipping\ShippingRate $rate
   *   The shipping rate to apply.
   */
  public function setRate(ShippingRate $rate) {
    $this->rate = $rate;
  }

  /**
   * Gets the shipment.
   *
   * @return \Drupal\commerce_shipping\Entity\ShipmentInterface
   *   The shipment.
   */
  public function getShipment() {
    return $this->shipment;
  }

}
