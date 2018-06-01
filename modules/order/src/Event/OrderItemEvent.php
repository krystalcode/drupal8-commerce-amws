<?php

namespace Drupal\commerce_amws_order\Event;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order item event.
 *
 * @see \Drupal\commerce_amws_order\Event\OrderItemEvents
 */
class OrderItemEvent extends Event {

  /**
   * The order item.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  protected $orderItem;

  /**
   * The Amazon MWS order item data.
   *
   * @var array
   */
  protected $amwsOrderItem;

  /**
   * Constructs a new OrderItemEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param array $amws_order_item
   *   The Amazon MWS order item data.
   */
  public function __construct(
    OrderItemInterface $order_item,
    array $amws_order_item
  ) {
    $this->orderItem = $order_item;
    $this->amwsOrderItem = $amws_order_item;
  }

  /**
   * Gets the order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item.
   */
  public function getOrderItem() {
    return $this->orderItem;
  }

  /**
   * Gets the Amazon MWS order item.
   *
   * @return array
   *   The Amazon MWS order item data.
   */
  public function getAmwsOrderItem() {
    return $this->amwsOrderItem;
  }

}
