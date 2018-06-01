<?php

namespace Drupal\commerce_amws_order\Event;

/**
 * Defines the names of events related to Amazon MWS order items.
 */
final class OrderItemEvents {

  /**
   * Name of the event fired after creating a new order item.
   *
   * Fired before the order item is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_amws_order\Event\OrderItemEvent
   */
  const ORDER_ITEM_CREATE = 'commerce_amws_order.commerce_order_item.create';

}
