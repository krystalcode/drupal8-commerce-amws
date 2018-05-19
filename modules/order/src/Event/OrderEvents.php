<?php

namespace Drupal\commerce_amws_order\Event;

/**
 * Defines the names of events related to Amazon MWS orders.
 */
final class OrderEvents {

  /**
   * Name of the event fired after creating a new order.
   *
   * Fired before the order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_amws_order\Event\OrderEvent
   */
  const ORDER_CREATE = 'commerce_amws_order.commerce_order.create';

  /**
   * Name of the event fired after saving a new order.
   *
   * Event subscribers can set the `saveOrder` flag to indicate that the order
   * has changed and should be saved again after all subscribers for this event
   * have run.
   *
   * @Event
   *
   * @see \Drupal\commerce_amws_order\Event\OrderEvent
   */
  const ORDER_INSERT = 'commerce_amws_order.commerce_order.insert';

}
