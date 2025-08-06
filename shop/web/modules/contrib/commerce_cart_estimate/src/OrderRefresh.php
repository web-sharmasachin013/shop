<?php

namespace Drupal\commerce_cart_estimate;

use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderPreprocessorInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_order\OrderRefreshInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;

/**
 * Estimator implementation for order refresh.
 *
 * The "fake" order used in the estimator should NOT save/persist any changes.
 */
class OrderRefresh implements OrderRefreshInterface {

  /**
   * The chain price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * The order preprocessors.
   *
   * @var \Drupal\commerce_order\OrderPreprocessorInterface[]
   */
  protected $preprocessors = [];

  /**
   * The order processors.
   *
   * @var \Drupal\commerce_order\OrderProcessorInterface[]
   */
  protected $processors = [];

  /**
   * Constructs a new OrderRefresh object.
   *
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain price resolver.
   */
  public function __construct(ChainPriceResolverInterface $chain_price_resolver) {
    $this->chainPriceResolver = $chain_price_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function addPreprocessor(OrderPreprocessorInterface $processor) {
    $this->preprocessors[] = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function addProcessor(OrderProcessorInterface $processor) {
    $this->processors[] = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function needsRefresh(OrderInterface $order) {
    // This method should never be invoked but is defined by the interface.
    // We technically only care about the actual refresh() method.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRefresh(OrderInterface $order) {
    // This method should never be invoked but is defined by the interface.
    // We technically only care about the actual refresh() method.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function refresh(OrderInterface $order) {
    // First invoke order preprocessors if any.
    foreach ($this->preprocessors as $processor) {
      $processor->preprocess($order);
    }
    $order->clearAdjustments();

    // Nothing else can be done while the order is empty.
    if (!$order->getItems()) {
      return;
    }
    $customer = $order->getCustomer();
    $time = $order->getCalculationDate()->format('U');
    $context = new Context($customer, $order->getStore(), $time);
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity) {
        $order_item->setTitle($purchased_entity->getOrderItemTitle());
        if (!$order_item->isUnitPriceOverridden()) {
          $unit_price = $this->chainPriceResolver->resolve($purchased_entity, $order_item->getQuantity(), $context);
          $unit_price ? $order_item->setUnitPrice($unit_price) : $order_item->set('unit_price', NULL);
        }
      }
      // If the order refresh is running during order preSave(),
      // $order_item->getOrder() will point to the original order (or
      // NULL, in case the order item is new).
      $order_item->order_id->entity = $order;
    }

    // Allow the processors to modify the order and its items.
    foreach ($this->processors as $processor) {
      $processor->process($order);
      if (!$order->hasItems()) {
        return;
      }
    }
  }

}
