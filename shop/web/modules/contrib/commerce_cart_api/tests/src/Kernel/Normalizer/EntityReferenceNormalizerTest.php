<?php

namespace Drupal\Tests\commerce_cart_api\Kernel\Normalizer;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Route;

/**
 * @group commerce_cart_api
 */
class EntityReferenceNormalizerTest extends OrderKernelTestBase implements ServiceModifierInterface {

  use ProphecyTrait;

  /**
   * @var \Drupal\commerce_order\Entity\Order
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'commerce_product',
    'commerce_cart',
    'commerce_cart_api',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $params = $this->container->getParameter('commerce_cart_api');
    if (method_exists($this, 'getName')) {
      $name = $this->getName();
    }
    else {
      $name = $this->name();
    }

    if ($name === 'testDefaults') {
      // Use defaults.
    }
    elseif ($name === 'testWithProductId') {
      $params['normalized_entity_references'] = [
        'order_items',
        'purchased_entity',
        'product_id',
      ];
    }
    elseif ($name === 'testWithAttributeColor') {
      $params['normalized_entity_references'] = [
        'order_items',
        'purchased_entity',
        'attribute_color',
      ];
    }
    elseif ($name === 'testWithProductIdAttributeColor') {
      $params['normalized_entity_references'] = [
        'order_items',
        'purchased_entity',
        'product_id',
        'attribute_color',
      ];
    }

    $this->container->setParameter('commerce_cart_api', $params);
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Remove commerce_order.order_store_resolver.
    // \Drupalcommerce_price\CurrencyFormatter is constructed and runs locale
    // resolving which kicks off discovering the current country, which ends up
    // running the store resolver. The OrderStoreResolver tries to get the
    // order parameter which we do not have mocked.
    $container->removeDefinition('commerce_order.order_store_resolver');

    $mocked_route_match = $this->prophesize(ResettableStackedRouteMatchInterface::class);
    $mocked_route = $this->prophesize(Route::class);
    $mocked_route->hasRequirement('_cart_api')->willReturn(TRUE);
    $mocked_route_match->getRouteObject()->willReturn($mocked_route->reveal());

    $container->set('current_route_match', $mocked_route_match->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installConfig([
      'commerce_product',
      'commerce_order',
    ]);

    $color_attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $this->container->get('commerce_product.attribute_field_manager')->createField($color_attribute, 'default');
    $color_blue = ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Blue',
    ]);
    $color_blue->save();

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'completed',
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderItem $order_item */
    $order_item = OrderItem::create([
      'type' => 'default',
    ]);
    /** @var \Drupal\commerce_product\Entity\Product $product */
    $product = Product::create([
      'type' => 'default',
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductVariation $product_variation */
    $product_variation = ProductVariation::create([
      'type' => 'default',
      'attribute_color' => $color_blue->id(),
    ]);
    $product_variation->save();
    $product->addVariation($product_variation);
    $product->save();
    $order->save();
    $order_item->set('order_id', $order->id());
    $order_item->get('purchased_entity')->appendItem($product_variation);
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);

    $order->addItem($order_item);
    $order->save();
    $order = $this->reloadEntity($order);
    $this->order = $order;
  }

  /**
   * Tests default configuration.
   */
  public function testDefaults() {
    $this->assertEntityReferenceNormalization(
      [],
      [
        ['order_items', 0, 'uuid'],
        ['order_items', 0, 'purchased_entity', 0, 'uuid'],
      ],
      [
        ['order_items', 0, 'purchased_entity', 0, 'product_id', 0, 'uuid'],
        ['order_items', 0, 'purchased_entity', 0, 'attribute_color', 0, 'uuid'],
      ]
    );
  }

  /**
   * Tests adding product ID.
   */
  public function testWithProductId() {
    $this->assertEntityReferenceNormalization(
      ['order_items', 'purchased_entity', 'product_id'],
      [
        ['order_items', 0, 'uuid'],
        ['order_items', 0, 'purchased_entity', 0, 'uuid'],
        ['order_items', 0, 'purchased_entity', 0, 'product_id', 0, 'uuid'],
      ],
      [
        ['order_items', 0, 'purchased_entity', 0, 'attribute_color', 0, 'uuid'],
      ]
    );
  }

  /**
   * Tests adding attribute color.
   */
  public function testWithAttributeColor() {
    $this->assertEntityReferenceNormalization(
      ['order_items', 'purchased_entity', 'attribute_color'],
      [
        ['order_items', 0, 'uuid'],
        ['order_items', 0, 'purchased_entity', 0, 'uuid'],
        ['order_items', 0, 'purchased_entity', 0, 'attribute_color', 0, 'uuid'],
      ],
      [
        ['order_items', 0, 'purchased_entity', 0, 'product_id', 0, 'uuid'],
      ]
    );
  }

  /**
   * Tests adding product ID and attribute color.
   */
  public function testWithProductIdAttributeColor() {
    $this->assertEntityReferenceNormalization(
      ['order_items', 'purchased_entity', 'product_id', 'attribute_color'],
      [
        ['order_items', 0, 'uuid'],
        ['order_items', 0, 'purchased_entity', 0, 'uuid'],
        ['order_items', 0, 'purchased_entity', 0, 'product_id', 0, 'uuid'],
        ['order_items', 0, 'purchased_entity', 0, 'attribute_color', 0, 'uuid'],
      ],
      []
    );
  }

  /**
   * Tests the field overrides and keys to check.
   *
   * @note This was the test method using a data provider, but the setUp method
   *       is only called once per test method and not again for each test data.
   */
  protected function assertEntityReferenceNormalization(array $field_overrides, array $keys_exists, array $keys_not_exist) {
    $params = $this->container->getParameter('commerce_cart_api');
    if ($field_overrides === []) {
      $field_overrides = ['order_items', 'purchased_entity'];
    }
    $this->assertEquals($field_overrides, $params['normalized_entity_references']);

    $serializer = $this->container->get('serializer');
    $data = $serializer->normalize($this->order, 'json');
    foreach ($keys_exists as $parents) {
      $this->assertTrue(
        NestedArray::keyExists($data, $parents),
        sprintf('Parent keys %s not found.', implode('.', $parents))
      );
    }
    foreach ($keys_not_exist as $parents) {
      $this->assertFalse(
        NestedArray::keyExists($data, $parents),
        sprintf('Parent keys %s should not be found.', implode('.', $parents))
      );
    }
  }

}
