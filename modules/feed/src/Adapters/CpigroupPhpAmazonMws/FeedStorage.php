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
  public function updateResult(array $feeds) {
    if (!$feeds) {
      return;
    }

    foreach ($feeds as $feed) {
      $result = $this->fetchResult($feed);
      $feed->set('result', $result);
      $feed->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateStatus(array $feeds) {
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

    // Fetch the list of feed statuses.
    $this->amwsFeedList->setFeedIds(array_keys($keyed_feeds));
    $this->amwsFeedList->setUseToken();
    $this->amwsFeedList->fetchFeedSubmissions();
    $results = $this->amwsFeedList->getFeedList();

    if (!$results) {
      return;
    }

    // Save status information on the Drupal feed entity.
    foreach ($results as $result) {
      $submission_id = $result['FeedSubmissionId'];
      /** @var \Drupal\commerce_amws_feed\Entity\FeedInterface $feed */
      $feed = $keyed_feeds[$submission_id];

      // @I Move dynamic status constant generation to a utility class.
      $constant = FeedInterface::class . '::PROCESSING_STATUS_' . trim($result['FeedProcessingStatus'], '_');
      $feed->setProcessingStatus(constant($constant));

      if (!empty($result['StartedProcessingDate'])) {
        $timestamp = strtotime($result['StartedProcessingDate']);
        $feed->setStartedProcessingDate($started_time);
      }

      if (!empty($result['CompletedProcessingDate'])) {
        $timestamp = strtotime($result['CompletedProcessingDate']);
        $feed->setCompletedProcessingDate($completed_time);
      }

      $feed->save();
    }
  }

  /**
   * Fetches the result for the given feed from Amazon MWS.
   *
   * @param \Drupal\commerce_amws_feed\Entity\FeedInterface $feed
   *   The feed for which to fetch the result.
   *
   * @return string
   *   The raw XML result for the feed.
   */
  protected function fetchResult(FeedInterface $feed) {
    $amwsFeedResult = new \AmazonFeedResult(
      $this->amwsStore->id(),
      $feed->getSubmissionId(),
      FALSE,
      NULL,
      $this->prepareStoreConfig($this->amwsStore)
    );
    $amwsFeedResult->fetchFeedResult();

    return $amwsFeedResult->getRawFeed();
  }

}
