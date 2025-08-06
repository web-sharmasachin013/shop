<?php

namespace Drupal\commerce_cart_estimate;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Swaps the commerce_shipping late order processor.
 */
class CommerceCartEstimateServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('commerce_shipping.late_order_processor')) {
      $container
        ->getDefinition('commerce_shipping.late_order_processor')
        ->setClass(LateOrderProcessor::class);
    }
  }

}
