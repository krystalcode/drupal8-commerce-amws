<?php

namespace Drupal\commerce_amws_feed\Commands;

use Drupal\commerce_amws\Entity\StoreInterface as AmwsStoreInterface;
use Drupal\commerce_amws_feed\Adapters\CpigroupPhpAmazonMws\FeedStorage as RemoteFeedStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands that provide operations related to Amazon MWS feeds.
 */
class Commands extends DrushCommands {

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
   * We need to pass the logger to the Amazon MWS remote feed storage. Drush
   * commands objects already have a `logger` property that is not the logger we
   * want.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $moduleLogger;

  /**
   * Constructs a new Commands object.
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

    $this->moduleLogger = $logger_factory->get(COMMERCE_AMWS_FEED_LOGGER_CHANNEL);
  }

  /**
   * Updates submitted feeds for all enabled Amazon AMWS stores.
   *
   * @command commerce-amws-feed:update-submitted
   *
   * @option $feed-limit
   *   An integer number limiting the number of feeds to update, per store.
   *
   * @validate-module-enabled commerce_amws_feed
   *
   * @aliases camwsf:update-submitted, camwsf-us
   */
  public function updateSubmitted(array $options) {
    $options = array_merge(['feed-limit' => NULL], $options);

    // Load enabled stores.
    $amws_store_ids = $this->amwsStoreStorage
      ->getQuery()
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
      $feeds = $this->feedStorage->loadSubmitted($load_options);

      if (!$feeds) {
        continue;
      }

      $remote_feed_storage = new RemoteFeedStorage(
        $amws_store,
        $this->moduleLogger
      );
      $remote_feed_storage->update($feeds);
      unset($remote_feed_storage);
    }
  }

}
