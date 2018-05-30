<?php

namespace Drupal\commerce_amws_product\Commands;

use Drupal\commerce_amws\Entity\StoreInterface as AmwsStoreInterface;
use Drupal\commerce_amws_product\Adapters\Mcs\ProductStorage as AmwsProductRemoteStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands that provide operations related to Amazon MWS products.
 */
class Commands extends DrushCommands {

  /**
   * The Amazon MWS store storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $amwsStoreStorage;

  /**
   * The Amazon MWS product storage.
   *
   * @var \Drupal\commerce_amws_product\ProductStorageInterface
   */
  protected $amwsProductStorage;

  /**
   * Constructs a new Commands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->amwsStoreStorage = $entity_type_manager->getStorage('commerce_amws_store');
    $this->amwsProductStorage = $entity_type_manager->getStorage('commerce_amws_product');
  }

  /**
   * Exports products for all enabled Amazon AMWS stores.
   *
   * @command commerce-amws-product:export-products
   *
   * @option $product-limit
   *   An integer number limiting the number of products to export.
   *
   * @validate-module-enabled commerce_amws_product
   *
   * @aliases camwsp:export-products, camwsp-ep
   */
  public function exportProducts(array $options) {
    $options = array_merge(['product-limit' => NULL], $options);

    // Load enabled stores.
    $amws_store_ids = $this->amwsStoreStorage
      ->getQuery()
      ->condition('status', AmwsStoreInterface::STATUS_PUBLISHED)
      ->execute();

    if (!$amws_store_ids) {
      return;
    }

    // Get published products per store and submit them for synchronization.
    $amws_stores = $this->amwsStoreStorage->loadMultiple($amws_store_ids);
    foreach ($amws_stores as $amws_store) {
      $load_options = ['store_id' => $amws_store->id()];
      if ($options['product-limit']) {
        $load_options['limit'] = $options['product-limit'];
      }
      $amws_product_ids = $this->amwsProductStorage
        ->getQuery()
        ->condition('status', TRUE)
        ->execute();

      if (!$amws_product_ids) {
        continue;
      }

      $amws_products = $this->amwsStoreStorage->loadMultiple($amws_product_ids);
      $amws_product_remote_storage = new AmwsProductRemoteStorage();
      $amws_product_remote_storage->export($amws_products);
      unset($amws_product_remote_storage);
    }
  }

}
