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
   * Deletes all orders that correspond to imported Amazon MWS stores.
   *
   * @command commerce-amazon-mws-order:dev-delete-orders
   *
   * @validate-module-enabled commerce_amazon_mws_order
   *
   * @aliases camwso:dev-delete-orders, camwso-dev-do
   */
  public function deleteOrders() {
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');

    $order_ids = $order_storage
      ->getQuery()
      ->condition('type', 'amazon_mws')
      ->execute();
    if (!$order_ids) {
      return;
    }

    $orders = $order_storage->loadMultiple($order_ids);
    $order_storage->delete($orders);
  }

}
