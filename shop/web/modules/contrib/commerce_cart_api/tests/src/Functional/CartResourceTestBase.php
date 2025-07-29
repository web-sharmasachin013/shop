<?php

namespace Drupal\Tests\commerce_cart_api\Functional;

use Drupal\commerce_price\Comparator\NumberComparator;
use Drupal\commerce_price\Comparator\PriceComparator;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Core\Url;
use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\ResourceTestBase;
use SebastianBergmann\Comparator\Factory as PhpUnitComparatorFactory;

/**
 * Defines base class for commerce_cart_api test cases.
 */
abstract class CartResourceTestBase extends ResourceTestBase {

  use CookieResourceTestTrait;
  use StoreCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

  /**
   * The store entity.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * A variation to add to carts.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * A second variation to add to carts.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation_2;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_cart_api',
    'commerce',
    'commerce_cart',
    'commerce_order',
    'commerce_store',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $factory = PhpUnitComparatorFactory::getInstance();
    $factory->register(new NumberComparator());
    $factory->register(new PriceComparator());

    $this->store = $this->createStore();
    $this->cartManager = \Drupal::service('commerce_cart.cart_manager');
    $this->cartProvider = \Drupal::service('commerce_cart.cart_provider');

    // Create a product variation.
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 1000,
        'currency_code' => 'USD',
      ],
    ]);

    // Create a second product variation.
    $this->variation_2 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 500,
        'currency_code' => 'USD',
      ],
    ]);

    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation, $this->variation_2],
    ]);

    $auth = isset(static::$auth) ? [static::$auth] : [];
    $this->provisionResource([static::$format], $auth);

    $this->initAuthentication();
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options) {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedBcUnauthorizedAccessMessage($method) {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {}

  /**
   * {@inheritdoc}
   *
   * We do not return specific resource permissions, as we respect the existing
   * cart management and ownership logic.
   */
  protected function setUpAuthorization($method) {
  }

  /**
   * Creates a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

}
