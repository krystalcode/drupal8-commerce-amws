<?php

namespace Drupal\commerce_amws_order;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The default implementation of the order purger service.
 */
class OrderPurger implements OrderPurgerInterface {

  /**
   * The Amazon MWS Order module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $orderStorage;

  /**
   * The order item storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The system time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new OrderPurger object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The system time.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EventDispatcherInterface $event_dispatcher,
    LoggerInterface $logger,
    TimeInterface $time
  ) {
    $this->config = $config_factory->get('commerce_amws_order.settings');
    $this->eventDispatcher = $event_dispatcher;
    $this->logger = $logger;
    $this->time = $time;

    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->orderItemStorage = $entity_type_manager
      ->getStorage('commerce_order_item');
  }

  /**
   * {@inheritdoc}
   */
  public function purge(array $options = [], $access_check = TRUE) {
    $options = array_merge(
      [
        'interval' => 0,
        'limit' => 0,
        'force' => FALSE,
      ],
      $options
    );

    $status = $this->config->get('purge.status');
    if (!$status && $options['force'] === FALSE) {
      $this->logger->warning(
        'An order purge was requested while purging order is disabled in the configuration; ignoring.'
      );
      return;
    }

    $this->validateOptions($options);

    $query = $this->orderStorage
      ->getQuery()
      ->condition('type', OrderService::DEFAULT_ORDER_TYPE)
      ->condition('created', $this->calculateCreatedEnd($options), '<=')
      ->sort('created', 'ASC')
      ->accessCheck($access_check);

    if ($options['limit']) {
      $query->range(0, $options['limit']);
    }

    $order_ids = $query->execute();

    if (!$order_ids) {
      return;
    }

    // We load orders one by one to prevent problems in case there is no limit
    // set and there is a large number of orders.
    foreach ($order_ids as $order_id) {
      $this->deleteOrder($order_id);
    }
  }

  /**
   * Deletes the order and associated data for the given ID.
   *
   * @param string $order_id
   *   The ID of the order to be deleted.
   */
  protected function deleteOrder($order_id) {
    $order = $this->orderStorage->load($order_id);

    // Delete billing and shipping profiles. We do not need to delete the user
    // as the order is always assigned to the anonymous user i.e. there's no
    // Drupal user to delete.
    $billing_profile = $order->getBillingProfile();
    if ($billing_profile) {
      $billing_profile->delete();
    }

    foreach ($order->get('shipments')->referencedEntities() as $shipment) {
      $shipping_profile = $shipment->getShippingProfile();
      if ($shipping_profile) {
        $shipping_profile->delete();
      }
    }

    $order->delete();
  }

  /**
   * Validates the purging options.
   *
   * @param array $options
   *   An associative array containing the purging options. See the `purge`
   *   method for supported options.
   *
   * @throws \InvalidArgumentException
   *   When an invalid option is given.
   */
  protected function validateOptions(array $options) {
    $interval = $options['interval'];
    if ($interval !== NULL && !$this->isPositiveIntegerOrZero($interval)) {
      throw new \InvalidArgumentException(
        sprintf(
          'The time interval after which orders are purged must be a positive integer or zero, "%s" given.',
          $interval
        )
      );
    }

    if (!$this->isPositiveIntegerOrZero($options['limit'])) {
      throw new \InvalidArgumentException(
        sprintf(
          'The limit that indicates how many orders will be purged must be a positive integer or zero, "%s" given.',
          $options['limit']
        )
      );
    }

    if (!is_bool($options['force'])) {
      throw new \InvalidArgumentException(
        sprintf(
          'The option that indicates whether to force a purge or not must be a boolean, "%s" given.',
          $options['force']
        )
      );
    }
  }

  /**
   * Checks whether the variable contains a positive integer or zero value.
   *
   * @param mixed $integer
   *   The variable to check.
   *
   * @return bool
   *   TRUE if the variable is a positive integer or zero, FALSE otherwise.
   */
  protected function isPositiveIntegerOrZero($integer) {
    return is_int($integer) && $integer >= 0;
  }

  /**
   * Returns the time that determines which orders will be deleted.
   *
   * If a time interval is provided by the purging options, that will be
   * used. Otherwise the time interval set in the module configuration will be
   * used.
   *
   * @param array $options
   *   An associative array containing the purging options. See the `purge`
   *   method for supported options.
   *
   * @return int
   *   The time as a Unix timestamp.
   *
   * @throws \InvalidArgumentException
   *   When forcing a purge without explicitly providing a time interval in the
   *   options and order purging is disabled in the configuration.
   * @throws \InvalidArgumentException
   *   When an invalid interval is given in the options.
   */
  protected function calculateCreatedEnd(array $options) {
    $interval = $options['interval'];
    if ($interval === NULL) {
      // If order purging is disabled we must be here using the `--force`
      // option. When order purging is disabled in configuration we consider the
      // configuration not applicable and we need to be explicitly given an
      // interval.
      if (!$this->config->get('purge.status')) {
        throw new \InvalidArgumentException(
          'A time interval after which orders are purged must be provided when forcing order purging.'
        );
      }

      $interval = $this->config->get('purge.interval');
      // Convert to integer since it comes as a text in configuration; not when
      // it is not set though so that undefined can be correctly validated
      // below.
      if ($interval !== NULL) {
        $interval = (int) $interval;
      }
    }

    // We should be taking care via the settings form that when the purge is
    // enabled, the interval should be 0 or positive. However, since this is
    // configuration that could be saved in other ways it is possible that it is
    // set to a different value. We therefore validate here as well.
    if (!$this->isPositiveIntegerOrZero($interval)) {
      throw new \InvalidArgumentException(
        'The time interval after which orders are purged must be a positive integer or zero.'
      );
    }

    return $this->time->getRequestTime() - $interval;
  }

}
