<?php

namespace Drupal\commerce_cart_estimate\EventSubscriber;

use Drupal\commerce_cart_estimate\Estimator;
use Drupal\commerce_cart_estimate\Exception\OrderSaveException;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The Order Subscriber.
 */
class OrderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OrderEvents::ORDER_PRESAVE => ['onPreSave'],
    ];
  }

  /**
   * Ensures an estimated order is not saved.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The transition event.
   */
  public function onPreSave(OrderEvent $event): void {
    $order = $event->getOrder();
    if ($order !== NULL && Estimator::orderIsEstimate($order)) {
      throw new OrderSaveException('Order estimates cannot be saved');
    }
  }

}
