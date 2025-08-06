<?php

namespace Drupal\commerce_cart_estimate\EventSubscriber;

use Drupal\commerce_cart_estimate\Estimator;
use Drupal\commerce_cart_estimate\Exception\ShipmentSaveException;
use Drupal\commerce_shipping\Event\ShipmentEvent;
use Drupal\commerce_shipping\Event\ShippingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The Shipment Subscriber.
 */
class ShipmentSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ShippingEvents::SHIPMENT_PRESAVE => ['onPreSave'],
    ];
  }

  /**
   * Ensures an estimated order is not saved.
   *
   * @param \Drupal\commerce_shipping\Event\ShipmentEvent $event
   *   The transition event.
   */
  public function onPreSave(ShipmentEvent $event): void {
    $shipment = $event->getShipment();
    $order = $shipment->getOrder();
    if ($order !== NULL && Estimator::orderIsEstimate($order)) {
      throw new ShipmentSaveException('Order estimates cannot be saved');
    }
  }

}
