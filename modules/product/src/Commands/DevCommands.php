<?php

namespace Drupal\commerce_amws_product\Commands;

use Drupal\commerce_amws\Commands\DevCommandsBase;

/**
 * Drush commands that provide operations related to Amazon MWS products.
 *
 * DEVELOPMENT USE ONLY!
 */
class DevCommands extends DevCommandsBase {

  /**
   * Deletes all Amazon MWS products.
   *
   * @command commerce-amws-product:dev-delete-products
   *
   * @validate-module-enabled commerce_amws_product
   *
   * @aliases camwsp:dev-delete-products, camwsp-dev-dp
   */
  public function deleteProducts() {
    $this->doDeleteEntities(['commerce_amws_product']);
  }

  /**
   * Deletes all Amazon MWS product types.
   *
   * @command commerce-amws-product:dev-delete-product-types
   *
   * @validate-module-enabled commerce_amws_product
   *
   * @aliases camwsp:dev-delete-product-types, camwsp-dev-dpt
   *
   * @I Accept Amazon MWS order IDs as parameter for deleting orders
   */
  public function deleteProductTypes() {
    $this->doDeleteEntities(['commerce_amws_product_type']);
  }

  /**
   * Deletes all entities managed by the Amazon MWS product module.
   *
   * @command commerce-amws-product:dev-delete-entities
   *
   * @validate-module-enabled commerce_amws_product
   *
   * @aliases camwsp:dev-delete-entities, camwsp-dev-de
   */
  public function deleteEntities() {
    $this->deleteProducts();
    $this->deleteProductTypes();
  }

}
