<?php

namespace Drupal\commerce_cart_estimate;

use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\commerce_cart_estimate\Event\CartEstimateEvents;
use Drupal\commerce_cart_estimate\Event\SelectShippingRateEvent;
use Drupal\commerce_cart_estimate\Exception\CartEstimateException;
use Drupal\commerce_cart_estimate\Exception\NoApplicableShippingRatesException;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderRefreshInterface;
use Drupal\commerce_shipping\ShipmentManagerInterface;
use Drupal\commerce_shipping\ShippingOrderManagerInterface;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a service for estimating taxes/shipping rates for orders.
 */
class Estimator implements EstimatorInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The order refresh service.
   *
   * @var \Drupal\commerce_order\OrderRefreshInterface
   */
  protected $orderRefresh;

  /**
   * The shipping order manager.
   *
   * @var \Drupal\commerce_shipping\ShippingOrderManagerInterface
   */
  protected $shippingOrderManager;

  /**
   * The shipment manager.
   *
   * @var \Drupal\commerce_shipping\ShipmentManagerInterface
   */
  protected $shipmentManager;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * Constructs a new Estimator object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_order\OrderRefreshInterface $order_refresh
   *   The order refresh service.
   * @param \Drupal\commerce_shipping\ShippingOrderManagerInterface $shipping_order_manager
   *   The shipping order manager.
   * @param \Drupal\commerce_shipping\ShipmentManagerInterface $shipment_manager
   *   The shipment manager.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface $subdivision_repository
   *   The subdivision repository.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, OrderRefreshInterface $order_refresh, ShippingOrderManagerInterface $shipping_order_manager, ShipmentManagerInterface $shipment_manager, SubdivisionRepositoryInterface $subdivision_repository) {
    $this->eventDispatcher = $event_dispatcher;
    $this->orderRefresh = $order_refresh;
    $this->shippingOrderManager = $shipping_order_manager;
    $this->shipmentManager = $shipment_manager;
    $this->subdivisionRepository = $subdivision_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function buildShippingProfile(OrderInterface $order, array $address) {
    return $this->shippingOrderManager->createProfile($order, [
      'address' => $address,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function estimate(OrderInterface $order, ProfileInterface $profile) {
    if (!$this->shippingOrderManager->isShippable($order)) {
      throw new \InvalidArgumentException('The provided order is not shippable.');
    }
    /** @var \Drupal\commerce_order\Entity\Order $fake_order */
    $fake_order = $order->createDuplicate();
    $fake_order->setData('commerce_cart_estimate', TRUE);
    // Clear the existing shipments reference, to ensure existing shipments
    // are not removed by the shipping order manager.
    $fake_order->set('shipments', NULL);
    $fake_order->set('order_id', $order->id());
    $fake_shipments = $this->shippingOrderManager->pack($fake_order, $profile);

    // @todo throw an exception when this happens?
    if (!$fake_shipments) {
      /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
      $address = $profile->get('address')->first();
      throw new CartEstimateException(sprintf('Could not pack the order when estimating shipping. (Country code: %s, Postal code: %s).', $address->getCountryCode(), $address->getPostalCode()));
    }

    $rates = [];
    $fake_order->set('shipments', $fake_shipments);
    // Rate the shipments returned.
    foreach ($fake_shipments as $fake_shipment) {
      // Custom flag to ensure the order isn't repacked during refresh.
      $fake_shipment->setData('owned_by_packer', FALSE);
      $fake_shipment->order_id->entity = $fake_order;
      $rates = $this->shipmentManager->calculateRates($fake_shipment);

      // When no rates could be calculated, throw an exception.
      if (!$rates) {
        /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
        $address = $profile->get('address')->first();
        throw new NoApplicableShippingRatesException(sprintf('No applicable shipping rates for the following partial address: (Country code: %s, Postal code: %s).', $address->getCountryCode(), $address->getPostalCode()));
      }
      // Default to the first rate from the rates array.
      $rate_to_apply = reset($rates);
      // Allow customizing the shipping rate that is going to be applied for
      // the cart estimate via an event.
      $event = new SelectShippingRateEvent($rates, $rate_to_apply, $fake_shipment);
      $this->eventDispatcher->dispatch($event, CartEstimateEvents::SELECT_SHIPPING_RATE);
      $this->shipmentManager->applyRate($fake_shipment, $event->getRate());
    }

    // Refresh the order so that shipping/tax adjustments are applied.
    // Add a custom flag to the order so that custom order processors can target
    // the order refresh performed for estimating shipping.
    $fake_order->setData('commerce_cart_estimate_refresh', TRUE);
    $this->orderRefresh->refresh($fake_order);
    $fake_order->recalculateTotalPrice();

    return new CartEstimateResult($fake_order, $rates);
  }

  /**
   * Determine if the order is an estimate.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   Whether the order is an estimate.
   */
  public static function orderIsEstimate(OrderInterface $order): bool {
    return $order->getData('commerce_cart_estimate', FALSE) ?? FALSE;
  }

}
