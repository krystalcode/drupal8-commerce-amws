<?php

namespace Drupal\commerce_amws_order\Commands;

use Drupal\commerce_amws\Entity\StoreInterface as AmwsStoreInterface;
use Drupal\commerce_amws_order\Adapters\CpigroupPhpAmazonMws\OrderStorage as AmwsRemoteOrderStorage;
use Drupal\commerce_amws_order\OrderService;
use Drupal\commerce_amws_order\OrderPurgerInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

use Psr\Log\LoggerInterface;

/**
 * Drush commands that provide operations related to Amazon MWS orders.
 */
class Commands extends DrushCommands {

  /**
   * The Amazon MWS store storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $amwsStoreStorage;

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
   * The logger service for the Amazon MWS Order module.
   *
   * We need to pass the logger to the Amazon MWS order storage. Drush commands
   * objects already have a `logger` property that is not the logger we want.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $moduleLogger;

  /**
   * The Amazon MWS order purger service.
   *
   * @var \Drupal\commerce_amws_order\OrderPurgerInterface
   */
  protected $amwsOrderPurger;

  /**
   * Constructs a new Commands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_amws_order\OrderService $amws_order_service
   *   The Amazon MWS order service.
   * @param \Psr\Log\LoggerInterface $module_logger
   *   The logger factory.
   * @param \Drupal\commerce_amws_order\OrderPurgerInterface $amws_order_purger
   *   The Amazon MWS order purger service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    OrderService $amws_order_service,
    LoggerInterface $module_logger,
    OrderPurgerInterface $amws_order_purger
  ) {
    $this->amwsStoreStorage = $entity_type_manager->getStorage('commerce_amws_store');
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');

    $this->amwsOrderService = $amws_order_service;
    $this->amwsOrderPurger = $amws_order_purger;
    $this->moduleLogger = $module_logger;
  }

  /**
   * Imports orders for all enabled Amazon MWS stores.
   *
   * @command commerce-amws-order:import-orders
   *
   * @option $limit
   *   An integer number limiting the number of orders to import per store.
   *
   * @validate-module-enabled commerce_amws_order
   *
   * @aliases camwso:import-orders, camwso-io
   */
  public function importOrders(array $options) {
    $options = array_merge(['limit' => NULL], $options);

    $amws_store_ids = $this->amwsStoreStorage
      ->getQuery()
      ->condition('status', AmwsStoreInterface::STATUS_PUBLISHED)
      ->execute();

    if (!$amws_store_ids) {
      return;
    }

    $amws_stores = $this->amwsStoreStorage->loadMultiple($amws_store_ids);
    foreach ($amws_stores as $amws_store) {
      $amws_order_storage = new AmwsRemoteOrderStorage(
        $amws_store,
        $this->orderStorage,
        $this->amwsOrderService,
        $this->moduleLogger
      );

      $import_options = [];
      if ($options['limit']) {
        $import_options['post_filters']['limit'] = $options['limit'];
      }

      $amws_order_storage->import($import_options);
      unset($amws_order_storage);
    }
  }

  /**
   * Purges orders after a set period of time.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @command commerce-amws-order:purge-orders
   *
   * @option $interval
   *   The time interval in seconds (i.e. an integer number) counted from the
   *   time orders were created (imported) after which orders will be
   *   deleted. If 0 is provided, all orders will be deleted. If no interval is
   *   provided, the time interval defined in the configuration will be used
   *   which must be defined.
   * @option $limit
   *   A positive integer number limiting the number of orders to purge.
   * @option $force
   *   Indicates that the purge should be run even when order purging is
   *   disabled in the configuration.
   *
   * @validate-module-enabled commerce_amws_order
   *
   * @aliases camwso:purge-orders, camwso-po
   *
   * @usage commerce-amws-order:purge-orders
   *   Purge all Amazon MWS orders older than the time interval defined in the
   *   configuration.
   * @usage commerce-amws-order:purge-orders --limit=12
   *   Purge the oldest 12 Amazon MWS orders older than the time interval
   *   defined in the configuration.
   * @usage commerce-amws-order:purge-orders --interval=86400
   *   Purge all Amazon MWS orders older than 30 days.
   * @usage commerce-amws-order:purge-orders --force --interval=86400
   *   Purge all Amazon MWS orders older than 30 days even if order purging is
   *   disabled in the configuration.
   *
   * @I Make the puge options configurable per store
   *    type     : improvement
   *    priority : low
   *    labels   : order, purge
   *    notes    : We need to store a reference to the AMWS store on an order
   *               field. We can then move the purge settings to each
   *               store. That might be useful if data privacy and security
   *               requirements are different in different countries.
   */
  public function purgeOrders(array $options = [
    'interval' => NULL,
    'limit' => '0',
    'force' => FALSE,
  ]) {
    if ($options['interval'] !== NULL) {
      $options['interval'] = (int) $options['interval'];
    }
    $purge_options = [
      'interval' => $options['interval'],
      'limit' => (int) $options['limit'],
      'force' => $options['force'],
    ];
    $this->amwsOrderPurger->purge($purge_options, FALSE);
  }

}
