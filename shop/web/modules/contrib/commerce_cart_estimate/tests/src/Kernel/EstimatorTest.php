<?php

namespace Drupal\Tests\commerce_cart_estimate\Kernel;

use Drupal\commerce_cart_estimate\Exception\CartEstimateException;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Entity\ShippingMethod;
use Drupal\physical\Weight;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_shipping\Kernel\ShippingKernelTestBase;

/**
 * Tests the estimator.
 *
 * @coversDefaultClass \Drupal\commerce_cart_estimate\Estimator
 * @group commerce_cart_estimate
 */
class EstimatorTest extends ShippingKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_cart_estimate',
    'commerce_cart_estimate_test',
  ];

  /**
   * The cart estimator.
   *
   * @var \Drupal\commerce_cart_estimate\EstimatorInterface
   */
  protected $estimator;

  /**
   * A test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $shipping_method = ShippingMethod::create([
      'name' => 'FR shipping method',
      'plugin' => [
        'target_plugin_id' => 'flat_rate',
        'target_plugin_configuration' => [
          'rate_amount' => [
            'number' => '10',
            'currency_code' => 'USD',
          ],
        ],
      ],
      'conditions' => [
        'target_plugin_id' => 'shipment_address',
        'target_plugin_configuration' => [
          'zone' => [
            'territories' => [
              [
                'country_code' => 'FR',
              ],
            ],
          ],
        ],
      ],
      'status' => 1,
      'stores' => $this->store->id(),
    ]);
    $shipping_method->save();

    $another_shipping_method = ShippingMethod::create([
      'name' => 'US shipping method',
      'plugin' => [
        'target_plugin_id' => 'flat_rate',
        'target_plugin_configuration' => [
          'rate_amount' => [
            'number' => '5',
            'currency_code' => 'USD',
          ],
        ],
      ],
      'conditions' => [
        'target_plugin_id' => 'shipment_address',
        'target_plugin_configuration' => [
          'zone' => [
            'territories' => [
              [
                'country_code' => 'US',
              ],
            ],
          ],
        ],
      ],
      'status' => 1,
      'stores' => $this->store->id(),
    ]);
    $another_shipping_method->save();

    $express_shipping_method = ShippingMethod::create([
      'name' => 'Express US shipping method',
      'plugin' => [
        'target_plugin_id' => 'flat_rate',
        'target_plugin_configuration' => [
          'rate_amount' => [
            'number' => '15',
            'currency_code' => 'USD',
          ],
        ],
      ],
      'conditions' => [
        'target_plugin_id' => 'shipment_address',
        'target_plugin_configuration' => [
          'zone' => [
            'territories' => [
              [
                'country_code' => 'US',
              ],
            ],
          ],
        ],
      ],
      'status' => 1,
      'stores' => $this->store->id(),
      'weight' => 5,
    ]);
    $express_shipping_method->save();

    $first_variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'test-product-01',
      'title' => 'Hat',
      'price' => new Price('70.00', 'USD'),
      'weight' => new Weight('0', 'g'),
    ]);
    $first_variation->save();

    $first_order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'title' => $first_variation->getOrderItemTitle(),
      'purchased_entity' => $first_variation,
      'unit_price' => new Price('70.00', 'USD'),
    ]);
    $first_order_item->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'uid' => $this->createUser(),
      'store_id' => $this->store->id(),
      'order_items' => [$first_order_item],
      'state' => 'completed',
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);

    $this->estimator = $this->container->get('commerce_cart_estimate.estimator');
  }

  /**
   * @covers ::estimate
   */
  public function testEstimate() {
    $fr_profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'FR',
      ],
    ]);
    $this->assertEquals(new Price('70', 'USD'), $this->order->getTotalPrice());
    $this->assertEquals(1, $this->order->getVersion());
    $cart_estimate = $this->estimator->estimate($this->order, $fr_profile);
    $rated_order = $cart_estimate->getRatedOrder();
    $this->assertNotNull($rated_order);
    $this->assertInstanceOf(OrderInterface::class, $rated_order);
    $shipping_adjustments = $rated_order->getAdjustments(['shipping']);
    $this->assertNotEmpty($shipping_adjustments);
    $this->assertCount(1, $shipping_adjustments);
    $this->assertEquals(new Price('10', 'USD'), $shipping_adjustments[0]->getAmount());
    $this->assertEquals(new Price('80', 'USD'), $rated_order->getTotalPrice());
    // Assert that the order is not saved and that its total is not altered.
    $this->order = $this->reloadEntity($this->order);
    $this->assertEquals(new Price('70', 'USD'), $this->order->getTotalPrice());
    $this->assertEquals(1, $this->order->getVersion());

    $us_profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
      ],
    ]);
    $cart_estimate = $this->estimator->estimate($this->order, $us_profile);
    $rated_order = $cart_estimate->getRatedOrder();
    $this->assertNotNull($rated_order);
    $this->assertInstanceOf(OrderInterface::class, $rated_order);
    $shipping_adjustments = $rated_order->getAdjustments(['shipping']);
    $this->assertCount(1, $shipping_adjustments);
    $this->assertNotEmpty($shipping_adjustments);
    $this->assertEquals(new Price('5', 'USD'), $shipping_adjustments[0]->getAmount());
    $this->assertEquals(new Price('75', 'USD'), $rated_order->getTotalPrice());
    $this->assertFalse($rated_order->get('shipments')->isEmpty());
    $shipments = $rated_order->get('shipments')->referencedEntities();
    $this->assertInstanceOf(ShipmentInterface::class, reset($shipments));
    // Assert that the order is not saved and that its total is not altered.
    $this->order = $this->reloadEntity($this->order);
    $this->assertEquals(new Price('70', 'USD'), $this->order->getTotalPrice());
    $this->assertEquals(1, $this->order->getVersion());

    // Test the event for altering the rate applied.
    $this->order->setData('pick_most_expensive_rate', TRUE);
    $cart_estimate = $this->estimator->estimate($this->order, $us_profile);
    $rated_order = $cart_estimate->getRatedOrder();
    $shipping_adjustments = $rated_order->getAdjustments(['shipping']);
    $this->assertCount(1, $shipping_adjustments);
    $this->assertNotEmpty($shipping_adjustments);
    $this->assertEquals(new Price('15', 'USD'), $shipping_adjustments[0]->getAmount());
    $this->assertEquals(new Price('85', 'USD'), $rated_order->getTotalPrice());
    // Assert that the order is not saved and that its total is not altered.
    $this->order = $this->reloadEntity($this->order);
    $this->assertEquals(new Price('70', 'USD'), $this->order->getTotalPrice());
    $this->assertEquals(1, $this->order->getVersion());

    $it_profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'IT',
      ],
    ]);
    $this->expectException(CartEstimateException::class);
    $this->estimator->estimate($this->order, $it_profile);
  }

}
