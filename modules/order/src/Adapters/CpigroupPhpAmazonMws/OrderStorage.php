<?php

namespace Drupal\commerce_amws_order\Adapters\CpigroupPhpAmazonMws;

use Drupal\commerce_amws\Entity\StoreInterface as AmwsStoreInterface;
use Drupal\commerce_amws_order\Adapters\OrderStorageInterface as AmwsOrderStorageInterface;
use Drupal\commerce_amws_order\OrderService as AmwsOrderService;
use Drupal\Core\Entity\EntityStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * The order storage adapter for the `cpigroup/php-amazon/mws` library.
 */
class OrderStorage implements AmwsOrderStorageInterface {

  /**
   * The order list object responsible for fetching orders from Amazon MWS.
   *
   * @var \AmazonOrderList
   */
  protected $amwsOrderList;

  /**
   * The Drupal Commerce order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * The Amazon MWS order service.
   *
   * @var \Drupal\commerce_amws_order\OrderService
   */
  protected $amwsOrderService;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new OrderStorage object.
   *
   * @param \Drupal\commerce_amws\Entity\StoreInterface $amws_store
   *   The Amazon MWS store that the order list object will be created for.
   * @param \Drupal\Core\Entity\EntityStorageInterface $order_storage
   *   The Drupal Commerce order storage.
   * @param \Drupal\commerce_amws_order\OrderService $amws_order_service
   *   The Amazon MWS order service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    AmwsStoreInterface $amws_store,
    EntityStorageInterface $order_storage,
    AmwsOrderService $amws_order_service,
    LoggerInterface $logger
  ) {
    $this->amwsOrderList = new \AmazonOrderList(
      $amws_store->id(),
      FALSE,
      NULL,
      $this->prepareStoreConfig($amws_store)
    );

    $this->orderStorage = $order_storage;
    $this->amwsOrderService = $amws_order_service;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   *   When an error occurs while fetching the orders from Amazon MWS.
   */
  public function loadMultiple(array $options) {
    $options = array_merge(
      $this->defaultOptions(),
      $options
    );

    // Add filters to the request.
    $this->filterByTime($options);
    $this->filterByStatus($options);

    // Set to fetch all orders in the list. Fetching results in pages is not
    // supported yet.
    $this->amwsOrderList->setUseToken();

    // Fetch the orders.
    // We don't want to stop execution because this may be part of a cron
    // fetching orders for multiple stores. If there is a failure on one of the
    // stores, such as wrong configuration or credentials, we still want to
    // allow the program to continue with the remaining stores.
    try {
      $this->amwsOrderList->fetchOrders();
      $amws_orders = $this->amwsOrderList->getList();
    }
    catch (\Exception $e) {
      $message = sprintf(
        'An error has occurred while fetching the orders from Amazon MWS. The error message was: "%s".',
        $e->getMessage()
      );
      $this->logger->error($message);
      return [];
    }

    // Post filtering.
    if (empty($options['post_filters'])) {
      return $amws_orders;
    }

    $amws_orders = $this->postFilterByStatus($amws_orders, $options);
    $amws_orders = $this->postFilterByImportState($amws_orders, $options);
    $amws_orders = $this->postFilterLimit($amws_orders, $options);

    return $amws_orders;
  }

  /**
   * {@inheritdoc}
   */
  public function import(array $options = []) {
    $options = array_merge(
      $this->defaultOptions(),
      $options
    );
    $amws_orders = $this->loadMultiple($options);

    foreach ($amws_orders as $amws_order) {
      $this->amwsOrderService->createOrder($amws_order);
    }
  }

  /**
   * Adds filtering by statuses to the order list request.
   *
   * @param array $options
   *   The array of options for the request. See self::loadMultiple().
   *
   * @throws \InvalidArgumentException
   *   When statuses not supported are requested.
   */
  protected function filterByStatus(array $options) {
    if (empty($options['statuses'])) {
      return;
    }

    $supported_statuses = [
      AmwsOrderStorageInterface::STATUS_CANCELED,
      AmwsOrderStorageInterface::STATUS_PARTIALLY_SHIPPED,
      AmwsOrderStorageInterface::STATUS_UNFULFILLABLE,
      AmwsOrderStorageInterface::STATUS_UNSHIPPED,
    ];
    $diff = array_diff($options['statuses'], $supported_statuses);
    if (!empty($diff)) {
      throw new \InvalidArgumentException(
        sprintf(
          'You have requested to fetch Amazon MWS orders with one or more unsupported statuses (%s). Supported statuses are: %s.',
          implode(', ', $diff),
          implode(', ', $supported_statuses)
        )
      );
    }

    $this->amwsOrderList->setOrderStatusFilter($options['statuses']);
  }

  /**
   * Adds filtering by creation or update time to the order list request.
   *
   * @param array $options
   *   The array of options for the request. See self::loadMultiple().
   *
   * @throws \InvalidArgumentException
   *   When trying to filter by both creation and update time.
   * @throws \InvalidArgumentException
   *   When the lower time limit is not provided.
   */
  protected function filterByTime(array $options) {
    if (!empty($options['created']) && !empty($options['updated'])) {
      throw new \InvalidArgumentException('You may limit the orders fetched from Amazon MWS by creation or update time, but not by both.');
    }

    $filter_mode = NULL;
    $filter_after = NULL;
    $filter_before = NULL;

    $modes = [
      AmwsOrderStorageInterface::FILTER_TIME_MODE_CREATED,
      AmwsOrderStorageInterface::FILTER_TIME_MODE_UPDATED,
    ];

    foreach ($modes as $mode) {
      if (empty($options[$mode])) {
        continue;
      }

      if (empty($options[$mode]['after'])) {
        throw new \InvalidArgumentException(
          sprintf(
            'The time the orders were %s after is required in order to filter orders by time.',
            $mode
          )
        );
      }

      $filter_mode = $this->formatTimeFilterMode($mode);
      $filter_after = $options[$mode]['after'];
      if (!empty($options[$mode]['before'])) {
        $filter_before = $options[$mode]['before'];
      }
    }

    $this->amwsOrderList->setLimits($filter_mode, $filter_after, $filter_before);
  }

  /**
   * Formats the time filtering mode as required by `php-amazon-mws` library.
   *
   * @param string $mode
   *   The mode to format. See corresponding constants defined at
   *   \Drupal\commerce_amws_order\Adapters\OrderStorageInterface::FILTER_TIME_MODE_*.
   *
   * @return string
   *   The mode in the expected format.
   */
  protected function formatTimeFilterMode($mode) {
    switch ($mode) {
      case AmwsOrderStorageInterface::FILTER_TIME_MODE_CREATED:
        return 'Created';

      case AmwsOrderStorageInterface::FILTER_TIME_MODE_UPDATED:
        return 'Modified';
    }
  }

  /**
   * Filters the given Amazon MWS orders by their status.
   *
   * @param \AmazonOrder[] $amws_orders
   *   An array of Amazon MWS order objects.
   * @param array $options
   *   The array of options for the request.
   *
   * @return \AmazonOrder[]
   *   An array with the filtered Amazon MWS order objects.
   *
   * @see self::loadMultiple()
   *   For a description of the `['post_filters']['statuses']` option and
   *   functionality.
   */
  protected function postFilterByStatus(array $amws_orders, array $options) {
    if (empty($options['post_filters']['statuses'])) {
      return $amws_orders;
    }

    $statuses = $options['post_filters']['statuses'];
    return array_filter(
      $amws_orders,
      function ($amws_order) use ($statuses) {
        if (in_array($amws_order->getOrderStatus(), $statuses)) {
          return TRUE;
        }

        return FALSE;
      }
    );
  }

  /**
   * Filters the given Amazon MWS orders by their import status.
   *
   * Allows to return only orders that have already been imported, that have not
   * been imported yet, or both (i.e. all orders).
   *
   * @param \AmazonOrder[] $amws_orders
   *   An array of Amazon MWS order objects.
   * @param array $options
   *   The array of options for the request.
   *
   * @return \AmazonOrder[]
   *   An array with the filtered Amazon MWS order objects.
   *
   * @see self::loadMultiple()
   *   For a description of the `['post_filters']['import_state']` option and
   *   functionality.
   */
  protected function postFilterByImportState(array $amws_orders, array $options) {
    if (!$amws_orders) {
      return $amws_orders;
    }

    if (!isset($options['post_filters']['import_state'])) {
      return $amws_orders;
    }

    $mode = $options['post_filters']['import_state'];
    $supported_modes = [
      AmwsOrderStorageInterface::POST_FILTER_IMPORT_STATE_ALL,
      AmwsOrderStorageInterface::POST_FILTER_IMPORT_STATE_NOT_IMPORTED,
      AmwsOrderStorageInterface::POST_FILTER_IMPORT_STATE_IMPORTED,
    ];
    if (!in_array($mode, $supported_modes)) {
      throw new \InvalidArgumentException(
        sprintf(
          'The mode "%s" for filtering Amazon MWS orders based on whether there are corresponding `commerce_order` entities is not supported. The supported modes are: %s.',
          $mode,
          implode(', ', $supported_modes)
        )
      );
    }

    // Nothing to do if both imported.
    if ($mode === AmwsOrderStorageInterface::POST_FILTER_IMPORT_STATE_ALL) {
      return $amws_orders;
    }

    // Get the list of IDs of already imported Amazon MWS orders. We do that by
    // loading the Drupal Commerce orders that have matching remote IDs, and
    // creating an array of all remote IDs from those orders.
    $amws_order_ids = array_map(
      function ($amws_order) {
        return $amws_order->getAmazonOrderId();
      },
      $amws_orders
    );

    $order_ids = $this->orderStorage
      ->getQuery()
      ->condition('amws_remote_id', $amws_order_ids, 'IN')
      ->execute();
    if (!$order_ids && $mode === AmwsOrderStorageInterface::POST_FILTER_IMPORT_STATE_NOT_IMPORTED) {
      return $amws_orders;
    }

    $orders = $this->orderStorage->loadMultiple($order_ids);
    $amws_order_ids_imported = array_map(
      function ($order) {
        return $order->get('amws_remote_id')->value;
      },
      $orders
    );

    // Filter the given Amazon MWS orders depending on the requested mode and
    // compared to the IDs of the already imported Amazon MWS orders.
    $amws_orders_filtered = [];
    switch ($mode) {
      case AmwsOrderStorageInterface::POST_FILTER_IMPORT_STATE_NOT_IMPORTED:
        return array_filter(
          $amws_orders,
          function ($amws_order) use ($amws_order_ids_imported) {
            return !in_array(
              $amws_order->getAmazonOrderId(),
              $amws_order_ids_imported
            );
          }
        );

      case AmwsOrderStorageInterface::POST_FILTER_IMPORT_STATE_IMPORTED:
        return array_filter(
          $amws_orders,
          function ($amws_order) use ($amws_order_ids_imported) {
            return in_array(
              $amws_order->getAmazonOrderId(),
              $amws_order_ids_imported
            );
          }
        );
    }
  }

  /**
   * Limits the number of the given Amazon MWS orders.
   *
   * @param \AmazonOrder[] $amws_orders
   *   An array of Amazon MWS order objects.
   * @param array $options
   *   The array of options for the request.
   *
   * @return \AmazonOrder[]
   *   An array with the filtered Amazon MWS order objects.
   *
   * @see self::loadMultiple()
   *   For a description of the `['post_filters']['limit']` option and
   *   functionality.
   */
  protected function postFilterLimit(array $amws_orders, array $options) {
    if (empty($options['post_filters']['limit'])) {
      return $amws_orders;
    }

    return array_slice($amws_orders, 0, $options['post_filters']['limit']);
  }

  /**
   * Prepares the Amazon MWS configuration as expected by `php-amazon-mws`.
   *
   * @param \Drupal\commerce_amws\Entity\StoreInterface $amws_store
   *   The Amazon MWS store that the order list object will be created for.
   *
   * @return array
   *   The configuration array.
   */
  protected function prepareStoreConfig(AmwsStoreInterface $amws_store) {
    return [
      'stores' => [
        $amws_store->id() => [
          'merchantId' => $amws_store->getSellerId(),
          'marketplaceId' => $amws_store->getMarketplaceId(),
          'keyId' => $amws_store->getAwsAccessKeyId(),
          'secretKey' => $amws_store->getSecretKey(),
          'MWSAuthToken' => $amws_store->getMwsAuthToken(),
        ],
      ],
      // @I Make the API endpoint URL customizable per store
      'AMAZON_SERVICE_URL' => 'https://mws.amazonservices.com/',
      'logpath' => '',
      'logfunction' => '',
      'muteLog' => TRUE,
    ];
  }

  /**
   * Provides the default options for the fetching Amazon MWS orders.
   *
   * The default options are for fetching all Unshipped orders updated over the
   * last one day.
   *
   * @return array
   *   An array with the default options.
   *
   * @see self::loadMultiple()
   */
  protected function defaultOptions() {
    return [
      'statuses' => [
        AmwsOrderStorageInterface::STATUS_PARTIALLY_SHIPPED,
        AmwsOrderStorageInterface::STATUS_UNSHIPPED,
      ],
      // @I Make time filter configurable so that it can match cron frequency
      'updated' => [
        'after' => strtotime('-1 day'),
      ],
      'post_filters' => [
        'statuses' => [AmwsOrderStorageInterface::STATUS_UNSHIPPED],
        'import_state' => AmwsOrderStorageInterface::POST_FILTER_IMPORT_STATE_NOT_IMPORTED,
      ],
    ];
  }

}
