<?php

namespace Drupal\commerce_amws_feed\Adapters\CpigroupPhpAmazonMws;

use Drupal\commerce_amws\Adapters\CpigroupPhpAmazonMws\AdapterTrait;
use Drupal\commerce_amws\Entity\StoreInterface as AmwsStoreInterface;
use Drupal\commerce_amws_feed\Adapters\FeedStorageInterface as FeedRemoteStorageInterface;
use Drupal\commerce_amws_feed\Entity\FeedInterface;
use Psr\Log\LoggerInterface;

/**
 * The feed storage adapter for the `cpigroup/php-amazon/mws` library.
 */
class FeedStorage implements FeedRemoteStorageInterface {

  use AdapterTrait;

  /**
   * The Amazon MWS feed list.
   *
   * @var \AmazonFeedList
   */
  protected $amwsFeedList;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new FeedStorage object.
   *
   * @param \Drupal\commerce_amws\Entity\StoreInterface $amws_store
   *   The Amazon MWS store that the feeds belong to.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    AmwsStoreInterface $amws_store,
    LoggerInterface $logger
  ) {
    $this->amwsStore = $amws_store;

    $this->amwsFeedList = new \AmazonFeedList(
      $amws_store->id(),
      FALSE,
      NULL,
      $this->prepareStoreConfig($amws_store)
    );

    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function update(array $feeds) {
    if (!$feeds) {
      return;
    }

    // The feeds might be keyed by their Drupal IDs. We want them keyed by
    // their Amazon MWS submission IDs.
    $keyed_feeds = array_reduce(
      $feeds,
      function ($carry, $feed) {
        $carry[$feed->getSubmissionId()] = $feed;
        return $carry;
      },
      []
    );

    $this->amwsFeedList->setFeedIds(array_keys($keyed_feeds));
    $this->amwsFeedList->setUseToken();
    $this->amwsFeedList->fetchFeedSubmissions();
    $results = $this->amwsFeedList->getFeedList();

    if (!$results) {
      return;
    }

    foreach ($results as $result) {
      $submission_id = $result['FeedSubmissionId'];
      // @I Move dynamic status constant generation to a utility class.
      $constant = FeedInterface::class . '::PROCESSING_STATUS_' . trim($result['FeedProcessingStatus'], '_');
      $keyed_feeds[$submission_id]->setProcessingStatus(constant($constant));

      if (!empty($result['StartedProcessingDate'])) {
        $keyed_feeds[$submission_id]->setStartedProcessingDate(strtotime($result['StartedProcessingDate']));
      }

      if (!empty($result['CompletedProcessingDate'])) {
        $keyed_feeds[$submission_id]->setCompletedProcessingDate(strtotime($result['CompletedProcessingDate']));
      }

      $keyed_feeds[$submission_id]->save();
    }
  }

}
