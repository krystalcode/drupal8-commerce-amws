<?php

namespace Drupal\commerce_amws_feed;

use Drupal\commerce_amws_feed\Entity\FeedInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * The default storage for feed entities.
 */
class FeedStorage extends SqlContentEntityStorage implements FeedStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadSubmitted(array $options = []) {
    $options = array_merge(
      [
        'limit' => NULL,
        'statuses' => [
          FeedInterface::PROCESSING_STATUS_AWAITING_ASYNCHRONOUS_REPLY,
          FeedInterface::PROCESSING_STATUS_IN_PROGRESS,
          FeedInterface::PROCESSING_STATUS_IN_SAFETY_NET,
          FeedInterface::PROCESSING_STATUS_SUBMITTED,
          FeedInterface::PROCESSING_STATUS_UNCONFIRMED,
        ],
        'store_id' => NULL,
      ],
      $options
    );

    $query = $this->getQuery()
      ->condition('processing_status', $options['statuses'], 'IN');

    if ($options['store_id']) {
      $query->condition('amws_stores', $options['store_id']);
    }

    if ($options['limit']) {
      $query->range(0, $options['limit']);
    }

    $ids = $query->execute();

    if (!$ids) {
      return [];
    }

    return $this->loadMultiple($ids);
  }

}
