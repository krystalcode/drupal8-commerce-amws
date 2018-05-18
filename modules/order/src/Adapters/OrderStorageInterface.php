<?php

namespace Drupal\commerce_amazon_mws_order\Adapters;

/**
 * Defines the interface for Amazon MWS order storage adapters.
 *
 * The scope of the storage is defined by the implementing class; in most cases
 * it would be a Amazon MWS store e.g. only orders placed on that store would be
 * fetched.
 */
interface OrderStorageInterface {

  /**
   * The Amazon MWS order status for canceled orders.
   */
  const STATUS_CANCELED = 'Canceled';

  /**
   * The Amazon MWS order status for partially shipped orders.
   */
  const STATUS_PARTIALLY_SHIPPED = 'PartiallyShipped';

  /**
   * The Amazon MWS order status for orders that cannot be fulfilled.
   */
  const STATUS_UNFULFILLABLE = 'Unfulfillable';

  /**
   * The Amazon MWS order status for orders that haven't shipped yet.
   */
  const STATUS_UNSHIPPED = 'Unshipped';

  /**
   * The mode for filtering Amazon MWS orders based on creation time.
   */
  const FILTER_TIME_MODE_CREATED = 'created';

  /**
   * The mode for filtering Amazon MWS orders based on last update time.
   */
  const FILTER_TIME_MODE_UPDATED = 'updated';

  /**
   * The mode for returning both previously imported and not imported orders.
   */
  const POST_FILTER_IMPORT_STATE_ALL = 0;

  /**
   * The mode for returning only not already imported orders.
   */
  const POST_FILTER_IMPORT_STATE_NOT_IMPORTED = 1;

  /**
   * The mode for returning only already imported orders.
   */
  const POST_FILTER_IMPORT_STATE_IMPORTED = 2;

  /**
   * Fetches orders from Amazon MWS.
   *
   * @param array $options
   *   An array of options defining which orders to fetch, with the ability to
   *   filter the results.
   *   - created: An associative array containing 'after' and 'before'
   *     timestamps or time strings defining the time span of orders to request,
   *     based on the orders' created time.
   *     Either ['created']['after'] or ['updated']['after'] is required.
   *   - updated: An associative array containing 'after' and 'before'
   *     timestamps or time strings defining the time span of orders to request,
   *     based on the orders' last updated time.
   *     Either ['created']['after'] or ['updated']['after'] is required.
   *   - statuses: A list of Amazon MWS order statuses; only orders with such
   *     statuses will be returned from Amazon MWS API.
   *   - post_filters: An associative array defining any post filtering the
   *     fetched orders.
   *     - statuses: An array of Amazon MWS order statuses to restrict the
   *       results to. Amazon MWS API requires 'Unshipped' and
   *       'PartiallyShipped' orders to always be requested together otherwise
   *       it returns no results. We may want to return only 'Unshipped' orders
   *       though.
   *     - import_state: Whether to return all Amazon MWS orders, only orders
   *       that have not been imported yet (i.e. do not have corresponding
   *       Drupal Commerce orders), or only orders that have already been
   *       imported. See self::POST_FILTER_IMPORT_STATE_* constants.
   *
   * @return \AmazonOrder[]
   *   An array containing the fetched and filtered Amazon MWS order objects.
   *
   * @I Use and return our own Amazon MWS order object.
   * @I Add option for limiting the number of orders to fetch.
   */
  public function loadMultiple(array $options);

  /**
   * Imports orders from Amazon MWS.
   *
   * Importing orders in this context means to create corresponding Drupal
   * Commerce order entities.
   */
  public function import();

}
