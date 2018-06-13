<?php

namespace Drupal\commerce_amws_feed;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for Amazon MWS feed storages.
 */
interface FeedStorageInterface extends ContentEntityStorageInterface {

  /**
   * Load feeds submitted to Amazon MWS.
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

}
