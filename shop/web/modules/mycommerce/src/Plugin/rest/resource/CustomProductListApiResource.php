<?php

declare(strict_types=1);

namespace Drupal\mycommerce\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use Drupal\file\Entity\File;

/**
 * Represents Custom Product List Api records as resources.
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
#[
    RestResource(
        id: "mycommerce_custom_product_list_api",
        label: new TranslatableMarkup("Custom Product List Api"),
        uri_paths: [
            "canonical" => "/api/mycommerce/products",
            "create" => "/api/mycommerce/products",
        ]
    )
]
final class CustomProductListApiResource extends ResourceBase
{
    /**
     * The key-value storage.
     */
    private readonly KeyValueStoreInterface $storage;

    /**
     * Responds to POST requests and saves the new record.
     */
    public function post(array $data): ModifiedResourceResponse
    {
        $data["id"] = $this->getNextId();
        $this->storage->set($data["id"], $data);
        $this->logger->notice(
            "Created new custom product list api record @id.",
            ["@id" => $data["id"]]
        );
        // Return the newly created record in the response body.
        return new ModifiedResourceResponse($data, 201);
    }

    /**
     * Responds to GET requests.
     */
    public function get()
    {
        // Load product entities using global entityTypeManager.
        $product_storage = \Drupal::entityTypeManager()->getStorage(
            "commerce_product"
        );
        $product_ids = \Drupal::entityQuery("commerce_product")
            ->accessCheck(true) // or FALSE if you're sure access control isn't needed
            ->range(0, 10)
            ->execute();

        $products = $product_storage->loadMultiple($product_ids);
        $data = [];

        // Get image URL

        foreach ($products as $product) {
            $image_url = "";
            if (
                $product->hasField("field_image") &&
                !$product->get("field_image")->isEmpty()
            ) {
                $file = $product->get("field_image")->first()->entity;
                if ($file) {
                    // $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
                    $public_url = \Drupal::service(
                        "file_url_generator"
                    )->generateAbsoluteString($file->getFileUri());
                    $image_url = preg_replace(
                        "#^https?://[^/]+#",
                        "https://verbose-giggle-jwv59jqw69435pv9-80.app.github.dev",
                        $public_url
                    );

                    // Replace localhost:port with your desired domain
                }
            }
            $data[] = [
                "id" => $product->id(),
                "title" => $product->label(),
                "price" => $this->getProductPrice($product),
                "description" => $product->get("body")->value,
                "img" => $image_url,
            ];
        }

        return new ResourceResponse($data, 200);
    }

    /**
     * Get price from first variation (if exists).
     */
    private function getProductPrice($product)
    {
        if (
            $product->hasField("variations") &&
            !$product->get("variations")->isEmpty()
        ) {
            $variation_ref = $product->get("variations")->first();
            if ($variation_ref && $variation_ref->entity) {
                $variation = $variation_ref->entity;

                if (
                    $variation->hasField("price") &&
                    !$variation->get("price")->isEmpty() &&
                    $variation->get("price")->first() !== null
                ) {
                    $price_item = $variation->get("price")->getValue();
                    return $price_item[0]["number"];
                }
            }
        } else {
            \Drupal::logger("custom_product_api")->warning(
                "Variations field is missing or empty."
            );
        }

        return null;
    }

    /**
     * Responds to PATCH requests.
     */
    public function patch($id, array $data): ModifiedResourceResponse
    {
        if (!$this->storage->has($id)) {
            throw new NotFoundHttpException();
        }
        $stored_data = $this->storage->get($id);
        $data += $stored_data;
        $this->storage->set($id, $data);
        $this->logger->notice(
            "The custom product list api record @id has been updated.",
            ["@id" => $id]
        );
        return new ModifiedResourceResponse($data, 200);
    }

    /**
     * Responds to DELETE requests.
     */
    public function delete($id): ModifiedResourceResponse
    {
        if (!$this->storage->has($id)) {
            throw new NotFoundHttpException();
        }
        $this->storage->delete($id);
        $this->logger->notice(
            "The custom product list api record @id has been deleted.",
            ["@id" => $id]
        );
        // Deleted responses have an empty body.
        return new ModifiedResourceResponse(null, 204);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBaseRoute($canonical_path, $method): Route
    {
        $route = parent::getBaseRoute($canonical_path, $method);
        // Set ID validation pattern.
        if ($method !== "POST") {
            $route->setRequirement("id", "\d+");
        }
        return $route;
    }

    /**
     * Returns next available ID.
     */
    private function getNextId(): int
    {
        $ids = \array_keys($this->storage->getAll());
        return count($ids) > 0 ? max($ids) + 1 : 1;
    }
}
