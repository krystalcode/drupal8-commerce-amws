<?php

namespace Drupal\commerce_amws_order\Commands;

use Drupal\commerce_amws\Entity\StoreInterface as AmwsStoreInterface;
use Drupal\commerce_amws_order\Adapters\CpigroupPhpAmazonMws\OrderStorage as AmwsRemoteOrderStorage;
use Drupal\commerce_amws_order\OrderService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Commands\DrushCommands;

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
   * Constructs a new Commands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_amws_order\OrderService $amws_order_service
   *   The Amazon MWS order service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    OrderService $amws_order_service,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->amwsStoreStorage = $entity_type_manager->getStorage('commerce_amws_store');
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');

    $this->amwsOrderService = $amws_order_service;
    $this->moduleLogger = $logger_factory->get(COMMERCE_AMWS_ORDER_LOGGER_CHANNEL);
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

}
