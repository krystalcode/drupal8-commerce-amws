<?php

namespace Drupal\commerce_amazon_mws\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Base class for Drush commands intended for development use only.
 *
 * DEVELOPMENT USE ONLY!
 */
class DevCommandsBase extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DevCommandsBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Deletes all entities of the given entity types.
   *
   * @param string[] $entity_type_ids
   *   The IDs of the entity types for which to delete all entities.
   */
  protected function doDeleteEntities(array $entity_type_ids) {
    foreach ($entity_type_ids as $entity_type_id) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entities = $storage->loadMultiple();
      $storage->delete($entities);
    }
  }

}
