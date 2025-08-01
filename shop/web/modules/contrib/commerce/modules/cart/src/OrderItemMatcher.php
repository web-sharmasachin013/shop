<?php

namespace Drupal\commerce_cart;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\OrderItemComparisonFieldsEvent;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Default implementation of the order item matcher.
 */
class OrderItemMatcher implements OrderItemMatcherInterface {

  /**
   * Constructs a new OrderItemMatcher object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, protected EventDispatcherInterface $eventDispatcher) {}

  /**
   * {@inheritdoc}
   */
  public function match(OrderItemInterface $order_item, array $order_items) {
    $order_items = $this->matchAll($order_item, $order_items);
    return count($order_items) ? $order_items[0] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function matchAll(OrderItemInterface $order_item, array $order_items) {
    $purchased_entity = $order_item->getPurchasedEntity();
    if (empty($purchased_entity)) {
      // Don't support combining order items without a purchased entity.
      return [];
    }

    $comparison_fields = ['type', 'purchased_entity'];
    $comparison_fields = array_merge($comparison_fields, $this->getCustomFields($order_item));
    $event = new OrderItemComparisonFieldsEvent($comparison_fields, $order_item);
    $this->eventDispatcher->dispatch($event, CartEvents::ORDER_ITEM_COMPARISON_FIELDS);
    $comparison_fields = $event->getComparisonFields();
    $comparison_fields = array_unique($comparison_fields);

    $matched_order_items = [];
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $existing_order_item */
    foreach ($order_items as $existing_order_item) {
      if ($existing_order_item->getData('owned_by_promotion')) {
        // We should not attempt to match order items owned by a promotion,
        // as they may be removed by the promotion.
        continue;
      }

      foreach ($comparison_fields as $field_name) {
        if (!$existing_order_item->hasField($field_name) || !$order_item->hasField($field_name)) {
          // The field is missing on one of the order items.
          continue 2;
        }

        $existing_order_item_field = $existing_order_item->get($field_name);
        $order_item_field = $order_item->get($field_name);
        // Two empty fields should be considered identical, but an empty item
        // can affect the comparison and cause a false mismatch.
        $existing_order_item_field = $existing_order_item_field->filterEmptyItems();
        $order_item_field = $order_item_field->filterEmptyItems();

        if (!$existing_order_item_field->equals($order_item_field)) {
          // Order item doesn't match.
          continue 2;
        }
      }
      $matched_order_items[] = $existing_order_item;
    }

    return $matched_order_items;
  }

  /**
   * Gets the names of custom fields shown on the add to cart form.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return string[]
   *   The field names.
   */
  protected function getCustomFields(OrderItemInterface $order_item) {
    $field_names = [];
    $storage = $this->entityTypeManager->getStorage('entity_form_display');
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $storage->load("commerce_order_item.{$order_item->bundle()}.add_to_cart");
    if ($form_display) {
      $field_names = array_keys($form_display->getComponents());
      // Remove base fields.
      $field_names = array_diff($field_names, [
        'purchased_entity',
        'quantity',
        'created',
      ]);
    }

    return $field_names;
  }

}
