<?php

namespace Drupal\commerce_amws_order;

/**
 * Defines the interface for the Amazon MWS order purger.
 *
 * The order purger provides functionality for purging Amazon MWS orders and
 * related data, as required to comply with Amazon MWS data privacy and security
 * policies.
 */
interface OrderPurgerInterface {

  /**
   * Purges Amazon MWS orders ans associated data after a set period of time.
   *
   * @param array $options
   *   An associative array of options. Supported options are:
   *   - interval: (int|null, optional, default: 0)
   *     The time interval in seconds (i.e. an integer number) counted from the
   *     time orders were created (imported) after which orders will be
   *     deleted. If no interval is provided, all orders will be deleted.
   *     When set to 0, the time interval will be considered as zero i.e. all
   *     orders created up to the request time will be deleted.
   *     When set to NULL, the value will be fetched from the relevant
   *     configuration setting.
   *   - limit: (int, optional, default: 0)
   *     A positive integer number limiting the number of orders to purge.
   *     When set to 0, no limit will be applied.
   * @param bool $access_check
   *   Whether to add access check to the query that loads the orders that will
   *   be deleted. Defaults to TRUE, which should be the desired behavior when
   *   triggering the purge action via the UI i.e. only delete orders that the
   *   user has access to. Set to FALSE when calling from Drush or via Cron, or
   *   any other process that runs as the anonymous users but still should have
   *   access to perform the purge on any order.
   *
   * @throws \InvalidArgumentException
   *   When an invalid option is given e.g. negative interval or limit.
   */
  public function purge(array $options = [], $access_check = TRUE);

}
