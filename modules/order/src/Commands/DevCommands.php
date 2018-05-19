<?php

namespace Drupal\commerce_amazon_mws_order\Commands;

use Drupal\commerce_amazon_mws\Commands\DevCommandsBase;

/**
 * Drush commands that provide operations related to Amazon MWS orders.
 *
 * DEVELOPMENT USE ONLY!
 */
class DevCommands extends DevCommandsBase {

  /**
   * Deletes all orders imported from Amazon MWS.
   *
   * @command commerce-amazon-mws-order:dev-delete-orders
   *
   * @validate-module-enabled commerce_amazon_mws_order
   *
   * @aliases camwso:dev-delete-orders, camwso-dev-do
   */
  public function deleteOrders() {
    $this->doDeleteEntities(
      ['commerce_order'],
      ['type' => 'amazon_mws']
    );
  }

  /**
   * Deletes all order items that belong to orders imported from Amazon MWS.
   *
   * @command commerce-amazon-mws-order:dev-delete-order-items
   *
   * @validate-module-enabled commerce_amazon_mws_order
   *
   * @aliases camwso:dev-delete-order-items, camwso-dev-doi
   */
  public function deleteOrderItems() {
    $this->doDeleteEntities(
      ['commerce_order_item'],
      ['type' => 'amazon_mws']
    );
  }

  /**
   * Deletes all entities managed by the Amazon MWS order module.
   *
   * @command commerce-amazon-mws-order:dev-delete-entities
   *
   * @validate-module-enabled commerce_amazon_mws_order
   *
   * @aliases camwso:dev-delete-entities, camwso-dev-de
   */
  public function deleteEntities() {
    $this->deleteOrders();
    $this->deleteOrdersItems();
  }

}
