<?php

namespace Drupal\commerce_amazon_mws\Commands;

/**
 * Drush commands that provide operations related to Amazon MWS.
 *
 * DEVELOPMENT USE ONLY!
 */
class DevCommands extends DevCommandsBase {

  /**
   * Deletes all Amazon MWS stores.
   *
   * @command commerce-amazon-mws:dev-delete-stores
   *
   * @validate-module-enabled commerce_amazon_mws
   *
   * @aliases camws:dev-delete-stores, camws-dev-ds
   */
  public function deleteStores() {
    $this->doDeleteEntities(['commerce_amws_store']);
  }

  /**
   * Deletes all entities managed by the Amazon MWS module.
   *
   * @command commerce-amazon-mws:dev-delete-entities
   *
   * @validate-module-enabled commerce_amazon_mws
   *
   * @aliases camws:dev-delete-entities, camws-dev-de
   */
  public function deleteEntities() {
    $this->deleteStores();
  }

}
