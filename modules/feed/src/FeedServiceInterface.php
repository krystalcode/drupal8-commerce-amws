<?php

namespace Drupal\commerce_amws_feed;

/**
 * The interface for the Amazon MWS feed service.
 *
 * It provides functionality that bridges the local with the remote feed
 * storages.
 *
 * Options are using '-' instead of '_' so that they can easily be called from
 * Drush commands.
 */
interface FeedServiceInterface {

  /**
   * Updates submitted feeds for all enabled Amazon AMWS stores.
   *
   * @param array $options
   *   An array with options defining which feeds to process. Available options
   *   are:
   *   - feed-limit: The number of feeds to update per store.
   */
  public function updateSubmitted(array $options = []);

  /**
   * Updates processed feeds for all enabled Amazon AMWS stores.
   *
   * @param array $options
   *   An array with options defining which feeds to process. Available options
   *   are:
   *   - feed-limit: The number of feeds to update per store.
   */
  public function updateProcessed(array $options = []);

}
