<?php

namespace Drupal\commerce_amws_product;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * The default storage for Amazon MWS product entities.
 */
class ProductStorage extends SqlContentEntityStorage implements ProductStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadQueued(array $options = []) {
    $query = $this->getQuery()->condition('state', 'queued');

    if (!empty($options['store_id'])) {
      $query->condition('stores', $options['store_id']);
    }

    if (!empty($options['limit'])) {
      $query->range(0, $options['limit']);
    }

    $ids = $query->execute();

    if (!$ids) {
      return [];
    }

    return $this->loadMultiple($ids);
  }

}
