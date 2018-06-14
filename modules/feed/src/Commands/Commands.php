<?php

namespace Drupal\commerce_amws_feed\Commands;

use Drupal\commerce_amws_feed\FeedServiceInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands that provide operations related to Amazon MWS feeds.
 */
class Commands extends DrushCommands {

  /**
   * The feed service.
   *
   * @var \Drupal\commerce_amws_feed\Service
   */
  protected $feedService;

  /**
   * Constructs a new Commands object.
   *
   * @param \Drupal\commerce_amws_feed\FeedServiceInterface $feed_service
   *   The feed service.
   */
  public function __construct(FeedServiceInterface $feed_service) {
    $this->feedService = $feed_service;
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
    $this->feedService->updateSubmitted($options);
  }

  /**
   * Updates processed feeds for all enabled Amazon AMWS stores.
   *
   * @command commerce-amws-feed:update-processed
   *
   * @option $feed-limit
   *   An integer number limiting the number of feeds to update, per store.
   *
   * @validate-module-enabled commerce_amws_feed
   *
   * @aliases camwsf:update-processed, camwsf-up
   */
  public function updateProcessed(array $options) {
    $this->feedService->updateProcessed($options);
  }

}
