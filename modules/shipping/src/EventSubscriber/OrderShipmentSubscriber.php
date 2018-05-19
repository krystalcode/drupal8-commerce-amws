<?php

namespace Drupal\commerce_amws_shipping\EventSubscriber;

use Drupal\commerce_amws_order\Event\OrderEvent as AmwsOrderEvent;
use Drupal\commerce_amws_order\Event\OrderEvents as AmwsOrderEvents;
use Drupal\commerce_amws_shipping\ShipmentService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generates the shipment for Amazon MWS orders.
 *
 * Modules wishing to override this logic can register their own event
 * subscriber with a higher weight (e.g. -10).
 */
class OrderShipmentSubscriber implements EventSubscriberInterface {

  /**
   * The Amazon MWS shipment service.
   *
   * @var \Drupal\commerce_amws_shipping\ShipmentService
   */
  protected $shipmentService;

  /**
   * Constructs a new OrderShipmentsSubscriber object.
   *
   * @param \Drupal\commerce_amws_shipping\ShipmentService $shipment_service
   *   The Amazon MWS shipment service.
   */
  public function __construct(ShipmentService $shipment_service) {
    $this->shipmentService = $shipment_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      AmwsOrderEvents::ORDER_INSERT => ['setShipment', -100],
    ];
    return $events;
  }

  /**
   * Generates the shipments for the order, if not already set.
   *
   * @param \Drupal\commerce_amws_order\Event\OrderEvent $event
   *   The profile event.
   */
  public function setShipment(AmwsOrderEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();

    $shipments_field = $order->get('shipments');
    if (!$shipments_field->isEmpty()) {
      return;
    }

    $this->shipmentService->createShipment(
      $order,
      $event->getAmwsOrder(),
      ShipmentService::DEFAULT_SHIPPING_PROFILE_TYPE
    );
    $event->setSaveOrder(TRUE);
  }

}
