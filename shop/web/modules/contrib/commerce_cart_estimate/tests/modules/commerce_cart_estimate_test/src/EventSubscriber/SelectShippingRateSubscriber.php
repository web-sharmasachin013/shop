<?php

namespace Drupal\commerce_cart_estimate_test\EventSubscriber;

use Drupal\commerce_cart_estimate\Event\CartEstimateEvents;
use Drupal\commerce_cart_estimate\Event\SelectShippingRateEvent;
use Drupal\commerce_shipping\ShippingRate;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a test event subscriber for selecting a different shipping rate.
 */
final class SelectShippingRateSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CartEstimateEvents::SELECT_SHIPPING_RATE => 'onSelectShippingRate',
    ];
  }

  /**
   * Alters the shipping rate about to be applied for shipping estimation.
   *
   * @param \Drupal\commerce_cart_estimate\Event\SelectShippingRateEvent $event
   *   The select shipping rate event.
   */
  public function onSelectShippingRate(SelectShippingRateEvent $event) {
    $shipment = $event->getShipment();
    if (!$shipment->getOrder()->getData('pick_most_expensive_rate')) {
      return;
    }
    $rates = $event->getRates();
    // Sort by original_amount descending.
    usort($rates, function (ShippingRate $first_rate, ShippingRate $second_rate) {
      return $first_rate->getOriginalAmount()->greaterThan($second_rate->getOriginalAmount()) ? -1 : 1;
    });
    $event->setRate(reset($rates));
  }

}
