<?php

namespace Drupal\commerce_cart_js\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a cart block.
 *
 * @Block(
 *   id = "commerce_cart_js",
 *   admin_label = @Translation("Cart (JS)"),
 *   category = @Translation("Commerce")
 * )
 */
class CartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme registry used to render an output.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * The Twig theme registry loader.
   *
   * @var \Twig\Loader\LoaderInterface
   */
  protected $themeRegistryLoader;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->moduleExtensionList = $container->get('extension.list.module');
    $instance->themeRegistry = $container->get('theme.registry');
    $instance->themeRegistryLoader = $container->get('twig.loader.theme_registry');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $registry = $this->themeRegistry->get();
    $cart_block_theme = $registry['commerce_cart_js_block'];
    $twig = $this->themeRegistryLoader->getSourceContext($cart_block_theme['template'] . '.html.twig');
    return [
      '#attached' => [
        'library' => [
          'commerce_cart_js/cart',
        ],
        'drupalSettings' => [
          'cartBlock' => [
            'template' => $twig->getCode(),
            'context' => [
              'url' => Url::fromRoute('commerce_cart.page')->toString(),
              'icon' => $this->moduleExtensionList->getPath('commerce') . '/icons/ffffff/cart.png',
            ],
          ],
        ],
      ],
      '#markup' => Markup::create('<div id="commerce_cart_js_block"></div>'),
    ];
  }

}
