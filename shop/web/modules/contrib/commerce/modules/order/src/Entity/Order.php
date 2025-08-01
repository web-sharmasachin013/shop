<?php

namespace Drupal\commerce_order\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderLabelEvent;
use Drupal\commerce_order\Event\OrderProfilesEvent;
use Drupal\commerce_order\Exception\OrderVersionMismatchException;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the order entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_order",
 *   label = @Translation("Order", context = "Commerce"),
 *   label_collection = @Translation("Orders", context = "Commerce"),
 *   label_singular = @Translation("order", context = "Commerce"),
 *   label_plural = @Translation("orders", context = "Commerce"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order",
 *     plural = "@count orders",
 *     context = "Commerce",
 *   ),
 *   bundle_label = @Translation("Order type", context = "Commerce"),
 *   handlers = {
 *     "event" = "Drupal\commerce_order\Event\OrderEvent",
 *     "storage" = "Drupal\commerce_order\OrderStorage",
 *     "storage_schema" = "Drupal\commerce\CommerceContentEntityStorageSchema",
 *     "access" = "Drupal\commerce_order\OrderAccessControlHandler",
 *     "query_access" = "Drupal\commerce_order\OrderQueryAccessHandler",
 *     "permission_provider" = "Drupal\commerce_order\OrderPermissionProvider",
 *     "list_builder" = "Drupal\commerce_order\OrderListBuilder",
 *     "views_data" = "Drupal\commerce\CommerceEntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_order\Form\OrderForm",
 *       "add" = "Drupal\commerce_order\Form\OrderForm",
 *       "edit" = "Drupal\commerce_order\Form\OrderForm",
 *       "delete" = "Drupal\commerce_order\Form\OrderDeleteForm",
 *       "unlock" = "Drupal\commerce_order\Form\OrderUnlockForm",
 *       "resend-receipt" = "Drupal\commerce_order\Form\OrderReceiptResendForm",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_order\OrderRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "entity_print" = "Drupal\commerce_order\EntityPrint\OrderRenderer"
 *   },
 *   base_table = "commerce_order",
 *   admin_permission = "administer commerce_order",
 *   permission_granularity = "bundle",
 *   field_indexes = {
 *     "order_number",
 *     "state"
 *   },
 *   entity_keys = {
 *     "id" = "order_id",
 *     "label" = "order_number",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/orders/{commerce_order}",
 *     "edit-form" = "/admin/commerce/orders/{commerce_order}/edit",
 *     "delete-form" = "/admin/commerce/orders/{commerce_order}/delete",
 *     "delete-multiple-form" = "/admin/commerce/orders/delete",
 *     "reassign-form" = "/admin/commerce/orders/{commerce_order}/reassign",
 *     "unlock-form" = "/admin/commerce/orders/{commerce_order}/unlock",
 *     "collection" = "/admin/commerce/orders",
 *     "resend-receipt-form" = "/admin/commerce/orders/{commerce_order}/resend-receipt",
 *     "state-transition-form" = "/admin/commerce/orders/{commerce_order}/{field_name}/{transition_id}"
 *   },
 *   bundle_entity_type = "commerce_order_type",
 *   field_ui_base_route = "entity.commerce_order_type.edit_form",
 *   allow_number_patterns = TRUE,
 *   log_version_mismatch = TRUE,
 *   constraints = {
 *     "OrderVersion" = {}
 *   }
 * )
 */
class Order extends CommerceContentEntityBase implements OrderInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = NULL;
    $order_number = $this->getOrderNumber();
    if ($order_number) {
      $label = $this->t('Order @order_number', ['@order_number' => $order_number]);
    }
    elseif ($this->getState()->getId() === 'draft' && !$this->isNew()) {
      $label = $this->t('Draft @id', ['@id' => $this->id()]);
    }
    // Allow the label to be overridden.
    $event = new OrderLabelEvent($this, $label);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($event, OrderEvents::ORDER_LABEL);

    return $event->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderNumber() {
    return $this->get('order_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderNumber($order_number) {
    $this->set('order_number', $order_number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->get('version')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVersion($version) {
    $this->set('version', $version);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStore() {
    return $this->getTranslatedReferencedEntity('store_id');
  }

  /**
   * {@inheritdoc}
   */
  public function setStore(StoreInterface $store) {
    $this->set('store_id', $store->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreId() {
    return $this->get('store_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreId($store_id) {
    $this->set('store_id', $store_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomer() {
    $customer = $this->get('uid')->entity;
    // Handle deleted customers.
    if (!$customer) {
      $customer = User::getAnonymousUser();
    }
    return $customer;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomer(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($mail) {
    $this->set('mail', $mail);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIpAddress() {
    return $this->get('ip_address')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setIpAddress($ip_address) {
    $this->set('ip_address', $ip_address);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingProfile() {
    return $this->get('billing_profile')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingProfile(ProfileInterface $profile) {
    $this->set('billing_profile', $profile);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function collectProfiles() {
    $profiles = [];
    if ($billing_profile = $this->getBillingProfile()) {
      $profiles['billing'] = $billing_profile;
    }
    // Allow other modules to register their own profiles (e.g. shipping).
    $event = new OrderProfilesEvent($this, $profiles);
    \Drupal::service('event_dispatcher')->dispatch($event, OrderEvents::ORDER_PROFILES);
    $profiles = $event->getProfiles();

    return $profiles;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->get('order_items')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setItems(array $order_items) {
    $this->set('order_items', $order_items);
    $this->recalculateTotalPrice();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItems() {
    return !$this->get('order_items')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(OrderItemInterface $order_item) {
    if (!$this->hasItem($order_item)) {
      $this->get('order_items')->appendItem($order_item);
      $this->recalculateTotalPrice();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem(OrderItemInterface $order_item) {
    $index = $this->getItemIndex($order_item);
    if ($index !== FALSE) {
      $this->get('order_items')->offsetUnset($index);
      $this->recalculateTotalPrice();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItem(OrderItemInterface $order_item) {
    return $this->getItemIndex($order_item) !== FALSE;
  }

  /**
   * Gets the index of the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return int|bool
   *   The index of the given order item, or FALSE if not found.
   */
  protected function getItemIndex(OrderItemInterface $order_item) {
    $values = $this->get('order_items')->getValue();
    $order_item_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($order_item->id(), $order_item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustments(array $adjustment_types = []) {
    /** @var \Drupal\commerce_order\Adjustment[] $adjustments */
    $adjustments = $this->get('adjustments')->getAdjustments();
    // Filter adjustments by type, if needed.
    if ($adjustment_types) {
      foreach ($adjustments as $index => $adjustment) {
        if (!in_array($adjustment->getType(), $adjustment_types)) {
          unset($adjustments[$index]);
        }
      }
      $adjustments = array_values($adjustments);
    }

    return $adjustments;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdjustments(array $adjustments) {
    $this->set('adjustments', $adjustments);
    $this->recalculateTotalPrice();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addAdjustment(Adjustment $adjustment) {
    $this->get('adjustments')->appendItem($adjustment);
    // No point in recalculating the order total when the adjustment being added
    // is already included since it doesn't affect the order total.
    if (!$adjustment->isIncluded()) {
      $this->recalculateTotalPrice();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAdjustment(Adjustment $adjustment) {
    $this->get('adjustments')->removeAdjustment($adjustment);
    // No point in recalculating the order total when the adjustment being
    // removed is already included since it doesn't affect the order total.
    if (!$adjustment->isIncluded()) {
      $this->recalculateTotalPrice();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearAdjustments() {
    $locked_callback = function ($adjustment) {
      /** @var \Drupal\commerce_order\Adjustment $adjustment */
      return $adjustment->isLocked();
    };
    // Remove all unlocked adjustments.
    foreach ($this->getItems() as $order_item) {
      /** @var \Drupal\commerce_order\Adjustment[] $adjustments */
      $adjustments = array_filter($order_item->getAdjustments(), $locked_callback);
      // Convert legacy locked adjustments.
      if ($adjustments && $order_item->usesLegacyAdjustments()) {
        foreach ($adjustments as $index => $adjustment) {
          $adjustments[$index] = $adjustment->multiply($order_item->getQuantity());
        }
      }
      $order_item->set('uses_legacy_adjustments', FALSE);
      $order_item->setAdjustments($adjustments);
    }
    $adjustments = array_filter($this->getAdjustments(), $locked_callback);
    $this->setAdjustments($adjustments);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function collectAdjustments(array $adjustment_types = []) {
    $adjustments = [];
    foreach ($this->getItems() as $order_item) {
      foreach ($order_item->getAdjustments($adjustment_types) as $adjustment) {
        if ($order_item->usesLegacyAdjustments()) {
          $adjustment = $adjustment->multiply($order_item->getQuantity());
        }
        $adjustments[] = $adjustment;
      }
    }
    foreach ($this->getAdjustments($adjustment_types) as $adjustment) {
      $adjustments[] = $adjustment;
    }

    return $adjustments;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtotalPrice() {
    /** @var \Drupal\commerce_price\Price $subtotal_price */
    $subtotal_price = NULL;
    foreach ($this->getItems() as $order_item) {
      if ($order_item_total = $order_item->getTotalPrice()) {
        $subtotal_price = $subtotal_price ? $subtotal_price->add($order_item_total) : $order_item_total;
      }
    }
    return $subtotal_price;
  }

  /**
   * {@inheritdoc}
   */
  public function recalculateTotalPrice() {
    /** @var \Drupal\commerce_price\Price $total_price */
    $total_price = NULL;
    foreach ($this->getItems() as $order_item) {
      if ($order_item_total = $order_item->getTotalPrice()) {
        $total_price = $total_price ? $total_price->add($order_item_total) : $order_item_total;
      }
    }
    $adjustments = $this->collectAdjustments();
    if ($adjustments) {
      /** @var \Drupal\commerce_order\AdjustmentTransformerInterface $adjustment_transformer */
      $adjustment_transformer = \Drupal::service('commerce_order.adjustment_transformer');
      $adjustments = $adjustment_transformer->combineAdjustments($adjustments);
      $adjustments = $adjustment_transformer->roundAdjustments($adjustments);
      foreach ($adjustments as $adjustment) {
        if (!$adjustment->isIncluded()) {
          $total_price = $total_price ? $total_price->add($adjustment->getAmount()) : $adjustment->getAmount();
        }
      }
    }
    $this->total_price = $total_price;
    $this->recalculateBalance();

    return $this;
  }

  /**
   * Recalculates the order balance.
   *
   * @return $this
   */
  protected function recalculateBalance() {
    $balance = NULL;
    if ($total_price = $this->getTotalPrice()) {
      $balance = $total_price->subtract($this->getTotalPaid());
    }
    $this->set('balance', $balance);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPrice() {
    if (!$this->get('total_price')->isEmpty()) {
      return $this->get('total_price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPaid() {
    if (!$this->get('total_paid')->isEmpty()) {
      return $this->get('total_paid')->first()->toPrice();
    }
    elseif ($total_price = $this->getTotalPrice()) {
      // Provide a default without storing it, to avoid having to update
      // the field if the order currency changes before the order is placed.
      return new Price('0', $total_price->getCurrencyCode());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setTotalPaid(Price $total_paid) {
    $this->set('total_paid', $total_paid);
    $this->recalculateBalance();
  }

  /**
   * {@inheritdoc}
   */
  public function getBalance() {
    if (!$this->get('balance')->isEmpty()) {
      return $this->get('balance')->first()->toPrice();
    }
    if ($total_price = $this->getTotalPrice()) {
      // Provide a default without storing it, to avoid having to update
      // the field if the order currency changes before the order is placed.
      return $total_price->subtract($this->getTotalPaid());
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isPaid() {
    $total_price = $this->getTotalPrice();
    if (!$total_price) {
      return FALSE;
    }

    $balance = $this->getBalance();
    // Free orders are considered fully paid once they have been placed.
    if ($total_price->isZero()) {
      $state_id = $this->getState()->getId();
      return ($state_id != 'draft') && ($state_id != 'canceled');
    }
    else {
      return $balance->isNegative() || $balance->isZero();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function getRefreshState() {
    return $this->getData('refresh_state');
  }

  /**
   * {@inheritdoc}
   */
  public function setRefreshState($refresh_state) {
    return $this->setData('refresh_state', $refresh_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getData($key, $default = NULL) {
    $data = [];
    if (!$this->get('data')->isEmpty()) {
      $data = $this->get('data')->first()->getValue();
    }
    return $data[$key] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($key, $value) {
    $this->get('data')->__set($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetData($key) {
    if (!$this->get('data')->isEmpty()) {
      $data = $this->get('data')->first()->getValue();
      unset($data[$key]);
      $this->set('data', $data);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->get('locked')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function lock() {
    $this->set('locked', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unlock() {
    $this->set('locked', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlacedTime() {
    return $this->get('placed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlacedTime($timestamp) {
    $this->set('placed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletedTime() {
    return $this->get('completed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompletedTime($timestamp) {
    $this->set('completed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCalculationDate() {
    $timezone = $this->getStore()?->getTimezone();
    $timestamp = $this->getPlacedTime() ?: \Drupal::time()->getRequestTime();
    $date = DrupalDateTime::createFromTimestamp($timestamp, $timezone);

    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerComments(): ?string {
    return $this->get('customer_comments')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomerComments($comments): static {
    $this->set('customer_comments', $comments);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (isset($this->original) && !$this->isNew() && $this->original->getVersion() > $this->getVersion()) {
      $mismatch_exception = new OrderVersionMismatchException(sprintf('Attempted to save order %s with version %s. Current version is %s.', $this->id(), $this->getVersion(), $this->original->getVersion()));
      $log_only = $this->getEntityType()->get('log_version_mismatch');
      if ($log_only) {
        \Drupal::logger('commerce_order')->error('<pre>%exception</pre>', [
          '%exception' => $mismatch_exception->__toString(),
        ]);
      }
      else {
        throw $mismatch_exception;
      }
    }
    $this->setVersion($this->getVersion() + 1);

    if ($this->isNew() && !$this->getIpAddress()) {
      $this->setIpAddress(\Drupal::request()->getClientIp());
    }
    $customer = $this->getCustomer();
    // The customer has been deleted, clear the reference.
    if ($this->getCustomerId() && $customer->isAnonymous()) {
      $this->setCustomerId(0);
    }
    // Maintain the order email.
    if (!$this->getEmail() && !$customer->isAnonymous()) {
      $this->setEmail($customer->getEmail());
    }
    // Make sure the billing profile is owned by the order, not the customer.
    $billing_profile = $this->getBillingProfile();
    if ($billing_profile && $billing_profile->getOwnerId()) {
      $billing_profile->setOwnerId(0);
      $billing_profile->save();
    }

    if ($this->getState()->getId() == 'draft') {
      // Refresh draft orders on every save.
      if (empty($this->getRefreshState())) {
        $this->setRefreshState(self::REFRESH_ON_SAVE);
      }
      // Initialize the flag for OrderStorage::doOrderPreSave().
      if ($this->getData('paid_event_dispatched') === NULL) {
        $this->setData('paid_event_dispatched', FALSE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a back-reference on each order item.
    foreach ($this->getItems() as $order_item) {
      if ($order_item->order_id->isEmpty()) {
        $order_item->order_id = $this->id();
        $order_item->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Delete the order items of a deleted order.
    $order_items = [];
    /** @var \Drupal\commerce_order\Entity\OrderInterface $entity */
    foreach ($entities as $entity) {
      foreach ($entity->getItems() as $order_item) {
        $order_items[$order_item->id()] = $order_item;
      }
    }
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_order_item');
    $order_item_storage->delete($order_items);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['order_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order number'))
      ->setDescription(t('The order number displayed to the customer.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['version'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Version'))
      ->setDescription(t('The order version number, it gets incremented on each save.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE)
      // Default to zero, so that the first save is version one.
      ->setDefaultValue(0);

    $fields['store_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store'))
      ->setDescription(t('The store to which the order belongs.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Customer'))
      ->setDescription(t('The customer.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_order\Entity\Order::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Contact email'))
      ->setDescription(t('The email address associated with the order.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP address'))
      ->setDescription(t('The IP address of the order.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 128)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_profile'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Billing information'))
      ->setDescription(t('Billing profile'))
      ->setSetting('target_type', 'profile')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['customer' => 'customer']])
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_billing_profile',
        'weight' => 0,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['order_items'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order items'))
      ->setDescription(t('The order items.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_order_item')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
        'weight' => 0,
        'settings' => [
          'override_labels' => TRUE,
          'label_singular' => t('order item'),
          'label_plural' => t('order items'),
          'removed_reference' => 'delete',
        ],
      ])
      ->setDisplayOptions('view', [
        'type' => 'commerce_order_item_table',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['adjustments'] = BaseFieldDefinition::create('commerce_adjustment')
      ->setLabel(t('Adjustments'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'commerce_adjustment_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total price'))
      ->setDescription(t('The total price of the order.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'commerce_order_total_summary',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_paid'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total paid'))
      ->setDescription(t('The total paid price of the order.'))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['balance'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Order balance'))
      ->setDescription(t('The order balance.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The order state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'settings' => [
          'require_confirmation' => TRUE,
          'use_modal' => TRUE,
        ],
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', ['\Drupal\commerce_order\Entity\Order', 'getWorkflowId']);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['locked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Locked'))
      ->setSettings([
        'on_label' => t('Yes'),
        'off_label' => t('No'),
      ])
      ->setDefaultValue(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the order was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the order was last edited.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['placed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Placed'))
      ->setDescription(t('The time when the order was placed.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Completed'))
      ->setDescription(t('The time when the order was completed.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['customer_comments'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Customer comments'))
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'above',
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return string
   *   The workflow ID, "order_default" if it cannot be determined.
   */
  public static function getWorkflowId(OrderInterface $order) {
    $order_type = OrderType::load($order->bundle());
    return $order_type?->getWorkflowId() ?? 'order_default';
  }

}
