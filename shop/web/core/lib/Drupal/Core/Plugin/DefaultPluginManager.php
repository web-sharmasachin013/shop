<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Plugin\Attribute\AttributeInterface;
use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Component\Plugin\Discovery\DiscoveryCachedTrait;
use Drupal\Core\Plugin\Discovery\AttributeClassDiscovery;
use Drupal\Core\Plugin\Discovery\AttributeDiscoveryWithAnnotations;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Base class for plugin managers.
 *
 * @ingroup plugin_api
 */
class DefaultPluginManager extends PluginManagerBase implements PluginManagerInterface, CachedDiscoveryInterface, CacheableDependencyInterface {

  use DiscoveryCachedTrait;
  use UseCacheBackendTrait;

  /**
   * The cache key.
   *
   * @var string
   */
  protected $cacheKey;

  /**
   * An array of cache tags to use for the cached definitions.
   *
   * @var array
   */
  protected $cacheTags = [];

  /**
   * Name of the alter hook if one should be invoked.
   *
   * @var string
   */
  protected $alterHook;

  /**
   * The subdirectory within a namespace to look for plugins.
   *
   * Set to FALSE if the plugins are in the top level of the namespace.
   *
   * @var string|bool
   */
  protected $subdir;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ?ModuleExtensionList $moduleExtensionList;

  /**
   * A set of defaults to be referenced by $this->processDefinition().
   *
   * Allows for additional processing of plugins when necessary or helpful for
   * development purposes.
   *
   * @var array
   */
  protected $defaults = [];

  /**
   * The name of the annotation that contains the plugin definition.
   *
   * @var string
   */
  protected $pluginDefinitionAnnotationName;

  /**
   * The name of the attribute that contains the plugin definition.
   *
   * @var string
   */
  protected $pluginDefinitionAttributeName;

  /**
   * The interface each plugin should implement.
   *
   * @var string|null
   */
  protected $pluginInterface;

  /**
   * An object of root paths that are traversable.
   *
   * The root paths are keyed by the corresponding namespace to look for plugin
   * implementations.
   *
   * @var \Traversable
   */
  protected $namespaces;

  /**
   * Additional annotation namespaces.
   *
   * The annotation discovery mechanism should scan these for annotation
   * definitions.
   *
   * @var string[]
   */
  protected $additionalAnnotationNamespaces = [];

  /**
   * Constructs a new \Drupal\Core\Plugin\DefaultPluginManager object.
   *
   * @param string|bool $subdir
   *   The plugin's subdirectory, for example Plugin/views/filter.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string|null $plugin_interface
   *   (optional) The interface each plugin should implement.
   * @param string|null $plugin_definition_attribute_name
   *   (optional) The name of the attribute that contains the plugin definition.
   * @param string|array|null $plugin_definition_annotation_name
   *   (optional) The name of the annotation that contains the plugin
   *   definition. Defaults to 'Drupal\Component\Annotation\Plugin'.
   * @param string[] $additional_annotation_namespaces
   *   (optional) Additional namespaces to scan for annotation definitions.
   *
   * @todo $plugin_definition_attribute_name should default to
   * 'Drupal\Component\Plugin\Attribute\Plugin' once annotations are no longer
   * supported.
   */
  public function __construct($subdir, \Traversable $namespaces, ModuleHandlerInterface $module_handler, $plugin_interface = NULL, ?string $plugin_definition_attribute_name = NULL, string|array|null $plugin_definition_annotation_name = NULL, array $additional_annotation_namespaces = []) {
    $this->subdir = $subdir;
    $this->namespaces = $namespaces;
    $this->moduleHandler = $module_handler;
    $this->pluginInterface = $plugin_interface;
    if (is_subclass_of($plugin_definition_attribute_name, AttributeInterface::class)) {
      $this->pluginDefinitionAttributeName = $plugin_definition_attribute_name;
      $this->pluginDefinitionAnnotationName = $plugin_definition_annotation_name;
      $this->additionalAnnotationNamespaces = $additional_annotation_namespaces;
    }
    else {
      // Backward compatibility.
      $this->pluginDefinitionAnnotationName = $plugin_definition_attribute_name ?? 'Drupal\Component\Annotation\Plugin';
      $this->additionalAnnotationNamespaces = $plugin_definition_annotation_name ?? [];
      if ($plugin_definition_attribute_name) {
        @trigger_error('Not supporting attribute discovery in ' . __CLASS__ . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Provide an Attribute class and an Annotation class for BC. See https://www.drupal.org/node/3395582', E_USER_DEPRECATED);
      }
    }
  }

  /**
   * Initialize the cache backend.
   *
   * Plugin definitions are cached using the provided cache backend.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param string $cache_key
   *   Cache key prefix to use.
   * @param array $cache_tags
   *   (optional) When providing a list of cache tags, the cached plugin
   *   definitions are tagged with the provided cache tags. These cache tags can
   *   then be used to clear the corresponding cached plugin definitions. Note
   *   that this should be used with care! For clearing all cached plugin
   *   definitions of a plugin manager, call that plugin manager's
   *   clearCachedDefinitions() method. Only use cache tags when cached plugin
   *   definitions should be cleared along with other, related cache entries.
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend, $cache_key, array $cache_tags = []) {
    assert(Inspector::assertAllStrings($cache_tags), 'Cache Tags must be strings.');
    $this->cacheBackend = $cache_backend;
    $this->cacheKey = $cache_key;
    $this->cacheTags = $cache_tags;
  }

  /**
   * Sets the alter hook name.
   *
   * @param string $alter_hook
   *   Name of the alter hook; for example, to invoke
   *   hook_my_module_data_alter() pass in "my_module_data".
   */
  protected function alterInfo($alter_hook) {
    $this->alterHook = $alter_hook;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = $this->getCachedDefinitions();
    if (!isset($definitions)) {
      $definitions = $this->findDefinitions();
      $this->setCachedDefinitions($definitions);
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    if ($this->cacheBackend) {
      if ($this->cacheTags) {
        // Use the cache tags to clear the cache.
        Cache::invalidateTags($this->cacheTags);
      }
      else {
        $this->cacheBackend->delete($this->cacheKey);
      }
    }
    if ($this->discovery instanceof CachedDiscoveryInterface) {
      $this->discovery->clearCachedDefinitions();
    }
    $this->definitions = NULL;
  }

  /**
   * Returns the cached plugin definitions of the decorated discovery class.
   *
   * @return array|null
   *   On success this will return an array of plugin definitions. On failure
   *   this should return NULL, indicating to other methods that this has not
   *   yet been defined. Success with no values should return as an empty array
   *   and would actually be returned by the getDefinitions() method.
   */
  protected function getCachedDefinitions() {
    if (!isset($this->definitions) && $cache = $this->cacheGet($this->cacheKey)) {
      $this->definitions = $cache->data;
    }
    return $this->definitions;
  }

  /**
   * Sets a cache of plugin definitions for the decorated discovery class.
   *
   * @param array $definitions
   *   List of definitions to store in cache.
   */
  protected function setCachedDefinitions($definitions) {
    $this->cacheSet($this->cacheKey, $definitions, Cache::PERMANENT, $this->cacheTags);
    $this->definitions = $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    if ($this->discovery instanceof CachedDiscoveryInterface) {
      $this->discovery->useCaches($use_caches);
    }
    $this->useCaches = $use_caches;
    if (!$use_caches) {
      $this->definitions = NULL;
    }
  }

  /**
   * Performs extra processing on plugin definitions.
   *
   * By default we add defaults for the type to the definition. If a type has
   * additional processing logic they can do that by replacing or extending the
   * method.
   */
  public function processDefinition(&$definition, $plugin_id) {
    // Only array-based definitions can have defaults merged in.
    if (is_array($definition) && !empty($this->defaults) && is_array($this->defaults)) {
      $definition = NestedArray::mergeDeep($this->defaults, $definition);
    }

    // Keep class definitions standard with no leading slash.
    if ($definition instanceof PluginDefinitionInterface) {
      assert(is_string($definition->getClass()), 'Plugin definitions must have a class');
      $definition->setClass(ltrim($definition->getClass(), '\\'));
    }
    elseif (is_array($definition) && isset($definition['class'])) {
      $definition['class'] = ltrim($definition['class'], '\\');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      if (isset($this->pluginDefinitionAttributeName) && isset($this->pluginDefinitionAnnotationName)) {
        $discovery = new AttributeDiscoveryWithAnnotations($this->subdir, $this->namespaces, $this->pluginDefinitionAttributeName, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);
      }
      elseif (isset($this->pluginDefinitionAttributeName)) {
        $discovery = new AttributeClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAttributeName);
      }
      else {
        $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);
      }
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFactory() {
    if (!$this->factory) {
      $this->factory = new ContainerFactory($this, $this->pluginInterface);
    }
    return $this->factory;
  }

  /**
   * Finds plugin definitions.
   *
   * @return array
   *   List of definitions to store in cache.
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();
    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    $this->alterDefinitions($definitions);
    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $provider = $this->extractProviderFromDefinition($plugin_definition);
      if ($provider && !in_array($provider, ['core', 'component']) && !$this->providerExists($provider)) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * Extracts the provider from a plugin definition.
   *
   * @param mixed $plugin_definition
   *   The plugin definition. Usually either an array or an instance of
   *   \Drupal\Component\Plugin\Definition\PluginDefinitionInterface.
   *
   * @return string|null
   *   The provider string, if it exists. NULL otherwise.
   */
  protected function extractProviderFromDefinition($plugin_definition) {
    if ($plugin_definition instanceof PluginDefinitionInterface) {
      return $plugin_definition->getProvider();
    }

    // Attempt to convert the plugin definition to an array.
    if (is_object($plugin_definition)) {
      $plugin_definition = (array) $plugin_definition;
    }

    if (isset($plugin_definition['provider'])) {
      return $plugin_definition['provider'];
    }
  }

  /**
   * Invokes the hook to alter the definitions if the alter hook is set.
   *
   * @param array $definitions
   *   The discovered plugin definitions.
   */
  protected function alterDefinitions(&$definitions) {
    if ($this->alterHook) {
      $this->moduleHandler->alter($this->alterHook, $definitions);
    }
  }

  /**
   * Determines if the provider of a definition exists.
   *
   * @return bool
   *   TRUE if provider exists, FALSE otherwise.
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->cacheTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
