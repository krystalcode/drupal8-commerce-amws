<?php

namespace Drupal\commerce_amazon_mws_order\EventSubscriber;

use Drupal\commerce_amazon_mws_order\Event\OrderEvent as AmwsOrderEvent;
use Drupal\commerce_amazon_mws_order\Event\OrderEvents as AmwsOrderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generates the order number for Amazon MWS orders.
 *
 * Modules wishing to override this logic can register their
 * own event subscriber with a higher weight (e.g. -10).
 */
class OrderNumberSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      AmwsOrderEvents::ORDER_INSERT => ['setOrderNumber', -100],
    ];
    return $events;
  }

  /**
   * Sets the order number, if not already set explicitly, to the order ID.
   *
   * @param \Drupal\commerce_amazon_mws_order\Event\OrderEvent $event
   *   The order event.
   */
  public function setOrderNumber(AmwsOrderEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();

    if ($order->getOrderNumber()) {
      return;
    }

    $order->setOrderNumber($order->id());
    $event->setSaveOrder(TRUE);
  }

}
