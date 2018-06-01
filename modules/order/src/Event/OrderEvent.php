<?php

namespace Drupal\commerce_amws_order\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order event.
 *
 * @see \Drupal\commerce_amws_order\Event\OrderEvents
 */
class OrderEvent extends Event {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The Amazon MWS order.
   *
   * @var \AmazonOrder
   */
  protected $amwsOrder;

  /**
   * Tracks whether the order has been changed.
   *
   * Provides a way to subscribers to indicate that the order should be saved
   * after all event subscribers have run.
   *
   * @var bool
   */
  protected $saveOrder;

  /**
   * Constructs a new OrderEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order.
   */
  public function __construct(OrderInterface $order, \AmazonOrder $amws_order) {
    $this->order = $order;
    $this->amwsOrder = $amws_order;
    $this->saveOrder = FALSE;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Gets the Amazon MWS order.
   *
   * @return \AmazonOrder
   *   The Amazon MWS order.
   */
  public function getAmwsOrder() {
    return $this->amwsOrder;
  }

  /**
   * Gets the save order flag.
   *
   * @return bool
   *   Returns the flag indicating whether the order should be saved after all
   *   subscribers have run.
   */
  public function getSaveOrder() {
    return $this->saveOrder;
  }

  /**
   * Sets the save order flag.
   *
   * @param bool $save_order
   *   The flag indicating whether the order should be saved after all
   *   subscribers have run.
   */
  public function setSaveOrder($save_order) {
    return $this->saveOrder = $save_order;
  }

}
