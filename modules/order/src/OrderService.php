<?php

namespace Drupal\commerce_amws_order;

use Drupal\commerce_amws_order\Event\OrderEvent as AmwsOrderEvent;
use Drupal\commerce_amws_order\Event\OrderEvents as AmwsOrderEvents;
use Drupal\commerce_amws_order\Event\OrderItemEvent as AmwsOrderItemEvent;
use Drupal\commerce_amws_order\Event\OrderItemEvents as AmwsOrderItemEvents;
use Drupal\commerce_amws\Utilities;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides functionality related to importing Amazon MWS orders.
 */
class OrderService {

  /**
   * The order type used for new Amazon MWS orders.
   */
  const DEFAULT_ORDER_TYPE = 'commerce_amws';

  /**
   * The ID of the user to whom new Amazon MWS orders will be assigned.
   */
  const DEFAULT_ORDER_UID = 0;

  /**
   * The state which new Amazon MWS orders will be created with.
   */
  const DEFAULT_ORDER_STATE = 'completed';

  /**
   * The state which new Amazon MWS order items will be created with.
   */
  const DEFAULT_ORDER_ITEM_TYPE = 'commerce_amws';

  /**
   * The name of the logger channel to use.
   */
  const LOGGER_CHANNEL = 'commerce_amws_order';

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
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $variationStorage;

  /**
   * The system time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new OrderService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The system time.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TimeInterface $time,
    EventDispatcherInterface $event_dispatcher,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->orderItemStorage = $entity_type_manager->getStorage('commerce_order_item');
    $this->variationStorage = $entity_type_manager->getStorage('commerce_product_variation');

    $this->time = $time;
    $this->eventDispatcher = $event_dispatcher;
    $this->logger = $logger_factory->get(self::LOGGER_CHANNEL);
  }

  /**
   * Creates an order for the given Amazon MWS order.
   *
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order.
   *
   * @return \Drupal\commerce_oder\Entity\OrderInterface|null
   *   The created Drupal Commerce order.
   */
  public function createOrder(\AmazonOrder $amws_order) {
    // Validate that the order contains all required data.
    if (!$this->validateAmwsOrder($amws_order)) {
      return;
    }

    // Validate that all order items contain all required data and they
    // correspond to known product variations.
    if (!$this->validateAmwsOrderItems($amws_order)) {
      return;
    }

    $order = $this->doCreateOrder($amws_order);
    if (!$order) {
      return;
    }

    // Create order items and add them to the order.
    $amws_order_item_list = $amws_order->fetchItems();
    $amws_order_items = $amws_order_item_list->getItems();
    foreach ($amws_order_items as $amws_order_item) {
      $order_item = $this->doCreateOrderItem($order, $amws_order_item);
      $order->addItem($order_item);
    }

    // Allow subscribers to modify the order after it has been saved and an ID
    // has been assigned to it. Save the order if any of the subscribers
    // indicate that we should do so.
    $event = new AmwsOrderEvent($order, $amws_order);
    $this->eventDispatcher->dispatch(AmwsOrderEvents::ORDER_INSERT, $event);

    if ($event->getSaveOrder()) {
      $order->save();
    }

    return $order;
  }

  /**
   * Creates the order object.
   *
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order.
   *
   * @return \Drupal\commerce_oder\Entity\OrderInterface|null
   *   The created order.
   */
  public function doCreateOrder(\AmazonOrder $amws_order) {
    // Determine which store the order should belong to.
    $store_id = $this->resolveStore($amws_order);
    if (!$store_id) {
      return;
    }

    // Timestamps.
    // They should always be available, but let's not break the import if not.
    $created = $changed = $this->time->getRequestTime();
    $purchase_date = $amws_order->getPurchaseDate();
    if ($purchase_date) {
      $created = strtotime($purchase_date);
    }
    $last_update_date = $amws_order->getLastUpdateDate();
    if ($last_update_date) {
      $changed = strtotime($last_update_date);
    }

    $order = $this->orderStorage->create([
      'type' => self::DEFAULT_ORDER_TYPE,
      'uid' => self::DEFAULT_ORDER_UID,
      'mail' => $amws_order->getBuyerEmail(),
      'created' => $created,
      'changed' => $changed,
      'placed' => $created,
      'completed' => $created,
      'state' => self::DEFAULT_ORDER_STATE,
      'store_id' => $store_id,
    ]);

    // Remote ID.
    $amws_order_id = $amws_order->getAmazonOrderId();
    if ($amws_order_id) {
      $order->set('amws_remote_id', $amws_order_id);
    }

    // Allow subscribers to modify the order before being saved.
    // Billing and shipping profiles are optionally added this way.
    $event = new AmwsOrderEvent($order, $amws_order);
    $this->eventDispatcher->dispatch(AmwsOrderEvents::ORDER_CREATE, $event);

    // Save the order.
    $order->save();

    return $order;
  }

  /**
   * Creates an order item for the given Amazon MWS order item data.
   *
   * This function assumes that the order and order items have already been
   * validated and they contain all required data.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order that the created order item will be added to.
   * @param array $data
   *   An array containing the Amazon MWS order item data.
   *
   * @return \Drupal\commerce_oder\Entity\OrderItemInterface|null
   *   The created order item, or NULL if no corresponding product variation was
   *   found.
   */
  protected function doCreateOrderItem(
    OrderInterface $order,
    array $data
  ) {
    $variation = $this->variationStorage->loadBySku($data['SellerSKU']);
    $order_item = $this->orderItemStorage->create([
      'type' => self::DEFAULT_ORDER_ITEM_TYPE,
      'order_id' => $order->id(),
      'amws_order_item_id' => $data['OrderItemId'],
      'purchased_entity' => $variation->id(),
      'title' => isset($data['Title']) ? $data['Title'] : $variation->getTitle(),
      'quantity' => $data['QuantityOrdered'],
    ]);

    // Set the unit price; that will trigger calculating the order item's total
    // price as well.
    $total_price = Utilities::amwsPriceToDrupalPrice($data['ItemPrice']);
    // Subtract any promotion discount as well.
    // We might develop a feature of connecting Drupal promotions with Amazon
    // MWS promotions, but that needs a bit more thinking. For now, don't create
    // an adjustment.
    if (!empty($data['PromotionDiscount']) && $data['PromotionDiscount']['Amount'] != 0) {
      $discount = Utilities::amwsPriceToDrupalPrice($data['PromotionDiscount']);
      $total_price = $total_price->subtract($discount);
    }
    $unit_price = $total_price->divide($data['QuantityOrdered']);
    $order_item->setUnitPrice($unit_price);

    // Remote ID.
    if (!empty($data['OrderItemId'])) {
      $order_item->set('amws_remote_id', $data['OrderItemId']);
    }

    // Allow subscribers to modify the order item before being saved.
    $event = new AmwsOrderItemEvent($order_item, $data);
    $this->eventDispatcher->dispatch(AmwsOrderItemEvents::ORDER_ITEM_CREATE, $event);

    // Save the order item.
    $order_item->save();

    return $order_item;
  }

  /**
   * Determines which store the created order will belong to.
   *
   * Since the order may contain products belonging to different stores, which
   * is not allowed by Drupal Commerce, we are assigning the order to the first
   * store of the first order item's product.
   *
   * This functions assumes that order items for the given order have already
   * been validated and contain all required data.
   *
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order.
   *
   * @return int|string|null
   *   The store ID, or NULL if none could be determined.
   */
  protected function resolveStore(\AmazonOrder $amws_order) {
    $store_id = NULL;

    $amws_order_item_list = $amws_order->fetchItems();
    $amws_order_items = $amws_order_item_list->getItems();

    foreach ($amws_order_items as $amws_order_item) {
      $variation = $this->variationStorage->loadBySku($amws_order_item['SellerSKU']);

      $product = $variation->getProduct();
      if (!$product) {
        $message = sprintf(
          'No product could be determined for the variation with ID "%s" and SKU "%s". A parent product is required for every variation by Drupal Commerce, and it is used by Commerce Amazon MWS to determine the store that the order will be assigned to. Order ID: %s. Order data: %s. Order item data: %s',
          $variation->id(),
          $variation->getSku(),
          $amws_order->getAmazonOrderId(),
          json_encode($amws_order->getData()),
          json_encode($amws_order_item)
        );
        $this->logger->error($message);
        return;
      }

      $store_ids = $product->getStoreIds();
      if (!$store_ids) {
        continue;
      }

      $store_id = current($store_ids);
      break;
    }

    if (!$store_id) {
      $message = sprintf(
        'No store could be determined for order with remote ID "%s" because none of the products corresponding to the order items belong to a store. Order data: %s. Order items data: %s',
        $amws_order->getAmazonOrderId(),
        json_encode($amws_order->getData()),
        json_encode($amws_order_items)
      );
      $this->logger->info($message);
      return;
    }

    return $store_id;
  }

  /**
   * Validates that the given Amazon MWS order contains all required data.
   *
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order to be validated.
   *
   * @return bool
   *   Whether the order is valid or not.
   */
  protected function validateAmwsOrder(\AmazonOrder $amws_order) {
    $mandatory_data = [
      'AmazonOrderId',
      'OrderStatus',
      'ShippingAddress',
      'BuyerName',
      'BuyerEmail',
    ];
    $data = $amws_order->getData();
    $diff = array_diff($mandatory_data, array_keys($data));
    if (count($diff)) {
      $message = sprintf(
        'The order with remote ID "%s" could not be imported because the following data were not provided: "%s". Order data: %s',
        $amws_order->getAmazonOrderId(),
        implode(', ', $diff),
        json_encode($data)
      );
      $this->logger->info($message);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates all items of the given Amazon MWS order.
   *
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order to be validated.
   *
   * @return bool
   *   Whether all order's item are valid or not.
   */
  protected function validateAmwsOrderItems(\AmazonOrder $amws_order) {
    $amws_order_item_list = $amws_order->fetchItems();
    $amws_order_items = $amws_order_item_list->getItems();

    // We don't process orders that do not have order items. That can happen for
    // `Canceled` orders. We currently only import `Unshipped` orders.
    if (!$amws_order_items) {
      return FALSE;
    }

    $success = TRUE;
    foreach ($amws_order_items as $amws_order_item) {
      // We do not stop on the first error because we want validation errors to
      // be logged for all order items.
      if (!$this->validateAmwsOrderItem($amws_order_item)) {
        $success = FALSE;
      }
    }

    return $success;
  }

  /**
   * Validates that the given order item contain all required data.
   *
   * It additionally validates that the given SKU corresponds to a product
   * variation which is required for creating order items.
   *
   * @param array $data
   *   The Amazon MWS order item's data.
   *
   * @return bool
   *   Whether the given order item data is valid or not.
   */
  protected function validateAmwsOrderItem(array $data) {
    $mandatory_data = [
      'OrderItemId',
      'SellerSKU',
      'QuantityOrdered',
      'ItemPrice',
    ];
    $diff = array_diff($mandatory_data, array_keys($data));
    if (count($diff)) {
      $message = sprintf(
        'The order item with remote ID "%s" could not be imported because the following data were not provided: "%s". Order item data: %s',
        empty($data['OrderItemId']) ? '' : $data['OrderItemId'],
        implode(', ', $diff),
        json_encode($data)
      );
      $this->logger->info($message);
      return FALSE;
    }

    // Check that a corresponding product variation does exist.
    $variation = $this->variationStorage->loadBySku($data['SellerSKU']);
    if (!$variation) {
      $message = sprintf(
        'The order item with remote ID "%s" could not be imported because the following SKU is unknown: "%s". Order item data: %s',
        $data['OrderItemId'],
        $data['SellerSKU'],
        json_encode($data)
      );
      $this->logger->info($message);
      return FALSE;
    }

    return TRUE;
  }

}
