<?php



namespace Drupal\onlineshop\Plugin\rest\resource;

use Drupal\commerce_product\Entity\Product;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Represents Product List records as resources.
 *
 * @DCG
 * The plugin exposes key-value records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. An
 * example of such configuration can be located in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively, you can enable it through admin interface provider by REST UI
 * module.
 * @see https://www.drupal.org/project/restui
 *
 * @DCG
 * Notice that this plugin does not provide any validation for the data.
 * Consider creating custom normalizer to validate and normalize the incoming
 * data. It can be enabled in the plugin definition as follows.
 * @code
 *   serialization_class = "Drupal\foo\MyDataStructure",
 * @endcode
 *
 * @DCG
 * For entities, it is recommended to use REST resource plugin provided by
 * Drupal core.
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
#[RestResource(
  id: 'onlineshop_product_list',
  label: new TranslatableMarkup('Product List'),
  uri_paths: [
    'canonical' => '/api/v1/productlist',
    'create' => '/api/v1/productlist',
  ],
)]
final class ProductListResource extends ResourceBase {

  /**
   * The key-value storage.
   */
  private readonly KeyValueStoreInterface $storage;

  /**
   * {@inheritdoc}
   */
 protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->entityTypeManager = $entityTypeManager;
  }


  /**
   * {@inheritdoc}
   */
 public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('commerce_rest')
    );
  }

  /**
   * Responds to POST requests and saves the new record.
   */
  public function post(array $data): ModifiedResourceResponse {
    $data['id'] = $this->getNextId();
    $this->storage->set($data['id'], $data);
    $this->logger->notice('Created new product list record @id.', ['@id' => $data['id']]);
    // Return the newly created record in the response body.
    return new ModifiedResourceResponse($data, 201);
  }

  /**
   * Responds to GET requests.
   */
  public function get() {
    $productStorage = $this->entityTypeManager->getStorage('commerce_product');
    $products = $productStorage->loadMultiple();

    $response = [];

    foreach ($products as $product) {
      /** @var \Drupal\commerce_product\Entity\Product $product */
      $defaultVariation = $product->getDefaultVariation();

      $response[] = [
        'id' => $product->id(),
        'name' => $product->label(),
        'price' => $defaultVariation ? $defaultVariation->getPrice()->getNumber() : NULL,
        'category' => $product->hasField('field_category') ? $product->get('field_category')->entity->label() : NULL,
        'description' => $product->hasField('body') ? $product->get('body')->value : '',
      ];
    }
       return new ResourceResponse($response, 200);
  }

  /**
   * Responds to PATCH requests.
   */
  public function patch($id, array $data): ModifiedResourceResponse {
    if (!$this->storage->has($id)) {
      throw new NotFoundHttpException();
    }
    $stored_data = $this->storage->get($id);
    $data += $stored_data;
    $this->storage->set($id, $data);
    $this->logger->notice('The product list record @id has been updated.', ['@id' => $id]);
    return new ModifiedResourceResponse($data, 200);
  }

  /**
   * Responds to DELETE requests.
   */
  public function delete($id): ModifiedResourceResponse {
    if (!$this->storage->has($id)) {
      throw new NotFoundHttpException();
    }
    $this->storage->delete($id);
    $this->logger->notice('The product list record @id has been deleted.', ['@id' => $id]);
    // Deleted responses have an empty body.
    return new ModifiedResourceResponse(NULL, 204);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method): Route {
    $route = parent::getBaseRoute($canonical_path, $method);
    // Set ID validation pattern.
    if ($method !== 'POST') {
      $route->setRequirement('id', '\d+');
    }
    return $route;
  }

  /**
   * Returns next available ID.
   */
  private function getNextId(): int {
    $ids = \array_keys($this->storage->getAll());
    return count($ids) > 0 ? max($ids) + 1 : 1;
  }

}
