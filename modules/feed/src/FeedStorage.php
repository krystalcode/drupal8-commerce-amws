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
  public function loadFeeds(array $options = [], $access_check = TRUE) {
    return $this->buildFeedsQueryAndExecute($options, $access_check);
  }

  /**
   * {@inheritdoc}
   */
  public function loadSubmitted(array $options = [], $access_check = TRUE) {
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

    return $this->buildFeedsQueryAndExecute($options, $access_check);
  }

  /**
   * {@inheritdoc}
   */
  public function loadProcessed(array $options = [], $access_check = TRUE) {
    $options = array_merge(
      [
        'limit' => NULL,
        'statuses' => [
          FeedInterface::PROCESSING_STATUS_CANCELLED,
          FeedInterface::PROCESSING_STATUS_DONE,
        ],
        'store_id' => NULL,
        'with_result' => FALSE,
      ],
      $options
    );

    $query = $this->buildFeedsQuery($options, $access_check);

    if ($options['with_result']) {
      $query->condition('result', NULL, 'IS NOT NULL');
    }
    else {
      $query->condition('result', NULL, 'IS NULL');
    }

    $ids = $query->execute();

    if (!$ids) {
      return [];
    }

    return $this->loadMultiple($ids);
  }

  /**
   * Builds the query for loading feeds.
   *
   * @param array $options
   *   An array of options that will determine which feeds to load.
   *
   * @see self::loadFeeds()
   *   For the list of available options.
   */
  protected function buildFeedsQuery(
    array $options = [],
    $access_check = TRUE
  ) {
    $options = array_merge(
      [
        'limit' => NULL,
        'statuses' => [],
        'store_id' => NULL,
      ],
      $options
    );

    $query = $this->getQuery()->accessCheck($access_check);

    if ($options['statuses']) {
      $query->condition('processing_status', $options['statuses'], 'IN');
    }

    if ($options['store_id']) {
      $query->condition('amws_stores', $options['store_id']);
    }

    if ($options['limit']) {
      $query->range(0, $options['limit']);
    }

    return $query;
  }

  /**
   * Builds and executes the query for loading feeds.
   *
   * @param array $options
   *   An array of options that will determine which feeds to load.
   *
   * @return \Drupal\commerce_amws_feed\Entity\FeedInterface[]|null
   *   An array with the loaded feeds, or NULL if there were none.
   *
   * @see self::loadFeeds()
   *   For the list of available options.
   */
  protected function buildFeedsQueryAndExecute(
    array $options = [],
    $access_check = TRUE
  ) {
    $ids = $this->buildFeedsQuery($options, $access_check)->execute();

    if (!$ids) {
      return [];
    }

    return $this->loadMultiple($ids);
  }

}
