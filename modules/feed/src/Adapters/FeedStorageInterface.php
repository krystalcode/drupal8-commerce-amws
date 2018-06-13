<?php

namespace Drupal\commerce_amws_feed\Adapters;

/**
 * Defines the interface for Amazon MWS feed storage adapters.
 *
 * The feed storage adapter is responsible for updating the processing details
 * for feeds from Amazon MWS.
 */
interface FeedStorageInterface {

  /**
   * Updates the given feeds with the processing results fetched from Amazon.
   *
   * @param \Drupal\commerce_amws_feed\Entity\FeedInterface[] $feeds
   *   The feeds for which to fetch the results.
   */
  public function update(array $feeds);

}
