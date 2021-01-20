<?php

namespace Drupal\commerce_amws_feed;

use Drupal\commerce_amws\Entity\StoreInterface as AmwsStoreInterface;
use Drupal\commerce_amws_feed\Adapters\CpigroupPhpAmazonMws\FeedStorage as RemoteFeedStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service with functionality that bridges the local with the remote storages.
 */
class FeedService implements FeedServiceInterface {

  /**
   * The Amazon MWS store storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $amwsStoreStorage;

  /**
   * The feed storage.
   *
   * @var \Drupal\commerce_amws_feed\FeedStorageInterface
   */
  protected $feedStorage;

  /**
   * The logger service for the Amazon MWS Feed module.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new FeedService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->amwsStoreStorage = $entity_type_manager->getStorage('commerce_amws_store');
    $this->feedStorage = $entity_type_manager->getStorage('commerce_amws_feed');

    $this->logger = $logger_factory->get(COMMERCE_AMWS_FEED_LOGGER_CHANNEL);
  }

  /**
   * Updates submitted feeds for all enabled Amazon AMWS stores.
   *
   * @param array $options
   *   An array with options defining which feeds to process. Available options
   *   are:
   *   - feed-limit: The number of feeds to update per store.
   */
  public function updateSubmitted(array $options = []) {
    $options = array_merge(['feed-limit' => NULL], $options);

    // Load enabled stores.
    $amws_store_ids = $this->amwsStoreStorage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', AmwsStoreInterface::STATUS_PUBLISHED)
      ->execute();

    if (!$amws_store_ids) {
      return;
    }

    // Get submitted feeds per store and update them.
    $amws_stores = $this->amwsStoreStorage->loadMultiple($amws_store_ids);
    foreach ($amws_stores as $amws_store) {
      $load_options = ['store_id' => $amws_store->id()];
      if ($options['feed-limit']) {
        $load_options['limit'] = $options['feed-limit'];
      }
      $feeds = $this->feedStorage->loadSubmitted($load_options, FALSE);

      if (!$feeds) {
        continue;
      }

      $remote_feed_storage = new RemoteFeedStorage(
        $amws_store,
        $this->logger
      );
      $remote_feed_storage->updateStatus($feeds);
      unset($remote_feed_storage);
    }
  }

  /**
   * Updates processed feeds for all enabled Amazon AMWS stores.
   *
   * @param array $options
   *   An array with options defining which feeds to process. Available options
   *   are:
   *   - feed-limit: The number of feeds to update per store.
   */
  public function updateProcessed(array $options = []) {
    $options = array_merge(['feed-limit' => NULL], $options);

    // Load enabled stores.
    $amws_store_ids = $this->amwsStoreStorage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', AmwsStoreInterface::STATUS_PUBLISHED)
      ->execute();

    if (!$amws_store_ids) {
      return;
    }

    // Get submitted feeds per store and update them.
    $amws_stores = $this->amwsStoreStorage->loadMultiple($amws_store_ids);
    foreach ($amws_stores as $amws_store) {
      $load_options = ['store_id' => $amws_store->id()];
      if ($options['feed-limit']) {
        $load_options['limit'] = $options['feed-limit'];
      }
      $feeds = $this->feedStorage->loadProcessed($load_options, FALSE);

      if (!$feeds) {
        continue;
      }

      $remote_feed_storage = new RemoteFeedStorage(
        $amws_store,
        $this->logger
      );
      $remote_feed_storage->updateResult($feeds);
      unset($remote_feed_storage);
    }
  }

}
