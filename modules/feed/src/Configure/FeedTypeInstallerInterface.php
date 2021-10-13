<?php

namespace Drupal\commerce_amws_feed\Configure;

/**
 * The interface for the feed type installer service.
 *
 * The feed type installer is responsible for collecting and installing the
 * feed types required by other modules.
 *
 * @see \Drupal\commerce_amws_feed\Configure\FeedTypeRequesterInterface
 */
interface FeedTypeInstallerInterface {

  /**
   * Adds the given feed type requester to the list.
   *
   * @param \Drupal\commerce_amws_feed\Configure\FeedTypeRequesterInterface $requester
   *   The feed type requester to add.
   */
  public function addRequester(FeedTypeRequesterInterface $requester);

  /**
   * Collects and install all feed types requested.
   *
   * @throws \InvalidArgumentException
   *   When one or more of the feed types requested by any of the requesters is
   *   invalid i.e. not one of the feed types defined by Amazon MWS.
   */
  public function install();

}
