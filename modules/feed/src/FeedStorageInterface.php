<?php

namespace Drupal\commerce_amws_feed;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for Amazon MWS feed storages.
 */
interface FeedStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads feeds based on the given options.
   *
   * @param array $options
   *   An array of options that will determine which feeds to load. Supported
   *   options are:
   *   - limit: An integer number limiting the number of feeds to load.
   *   - statuses: A list of processing statuses; if provided, only feeds with
   *     the given statuses will be loaded.
   *   - store_id: The Amazon MWS store ID for which to load feeds.
   *
   * @return \Drupal\commerce_amws_product\Entity\FeedInterface[]|null
   *   The feeds, or NULL if there is none.
   */
  public function loadFeeds(array $options = []);

  /**
   * Loads feeds submitted to Amazon MWS.
   *
   * @param array $options
   *   An array of options that will determine which submitted feeds to
   *   load. Supported options are:
   *   - limit: An integer number limiting the number of feeds to load.
   *   - statuses: A list of statuses that are considered as submitted. For the
   *     purposes of this module, submitted are all feeds that have been
   *     submitted AND waiting to be processed. By default all feeds apart from
   *     cancelled and completed are loaded.
   *   - store_id: The Amazon MWS store ID for which to load feeds.
   *
   * @return \Drupal\commerce_amws_product\Entity\FeedInterface[]|null
   *   The submitted feeds, or NULL if there is none.
   */
  public function loadSubmitted(array $options = []);

  /**
   * Loads feeds that have already been processed.
   *
   * @param array $options
   *   An array of options that will determine which processed feeds to
   *   load. Supported options are:
   *   - limit: An integer number limiting the number of feeds to load.
   *   - statuses: A list of statuses that are considered as processed. By
   *     default feeds that are marked by Amazon MWS as Done or Cancelled are
   *     considered as processed.
   *   - store_id: The Amazon MWS store ID for which to load feeds.
   *
   * @return \Drupal\commerce_amws_product\Entity\FeedInterface[]|null
   *   The processed feeds, or NULL if there is none.
   */
  public function loadProcessed(array $options = []);

}
