<?php

namespace Drupal\commerce_amws\Commands;

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
   * @param array $properties
   *   An associative array keyed by property/field names and containing
   *   property/field values. Only entities matching these values will be
   *   deleted.
   */
  protected function doDeleteEntities(
    array $entity_type_ids,
    array $properties = []
  ) {
    foreach ($entity_type_ids as $entity_type_id) {
      $entities = [];
      $storage = $this->entityTypeManager->getStorage($entity_type_id);

      // Load all entities if there are no conditions.
      if (!$properties) {
        $entities = $storage->loadMultiple();
      }
      // Otherwise, make an load only entities matching the conditions.
      else {
        $query = $storage->getQuery();
        foreach ($properties as $property => $value) {
          $query->condition($property, $value);
        }
        $entity_ids = $query->execute();
        if (!$entity_ids) {
          continue;
        }

        $entities = $storage->loadMultiple($entity_ids);
      }

      $storage->delete($entities);
    }
  }

}
