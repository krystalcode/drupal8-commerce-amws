<?php

namespace Drupal\commerce_amws_shipping;

use Drupal\commerce_amws_order\Event\ProfileEvent as AmwsProfileEvent;
use Drupal\commerce_amws_order\Event\ProfileEvents as AmwsProfileEvents;
use Drupal\commerce_amws_order\HelperService;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\ShipmentItem;
use Drupal\physical\WeightUnit;
use Drupal\profile\Entity\ProfileInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides functionality related to importing shipping data from Amazon MWS.
 */
class ShipmentService {

  use StringTranslationTrait;

  /**
   * The shipment type used for new shipments.
   */
  const DEFAULT_SHIPMENT_TYPE = 'default';

  /**
   * The shipment state used for new shipments.
   */
  const DEFAULT_SHIPMENT_STATE = 'draft';

  /**
   * The package type used for new shipments.
   */
  const DEFAULT_PACKAGE_TYPE = 'custom_box';

  /**
   * The profile type to be used for creating the order's shipping profile.
   */
  const DEFAULT_SHIPPING_PROFILE_TYPE = 'customer';

  /**
   * The shipping service to be used for new Amazon MWS orders.
   *
   * Hardcoded for now, it should be replaced with configuration at first stage,
   * and ideally later with mapping between Drupal Commerce shipping methods and
   * Amazon MWS shipping services.
   */
  const DEFAULT_SHIPPING_SERVICE = 'default';

  /**
   * The name of the logger channel to use.
   */
  const LOGGER_CHANNEL = 'commerce_amws_shipping';

  /**
   * The shipment storage.
   *
   * @var \Drupal\commerce_shipping\ShipmentStorageInterface
   */
  protected $shipmentStorage;

  /**
   * The helper service for converting address information to profile entities.
   *
   * @var \Drupal\commerce_amws_order\HelperService
   */
  protected $orderHelper;

  /**
   * The Amazon MWS shipping configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $shippingConfig;

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
   * Constructs a new ShipmentService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_amws_order\HelperService $order_helper
   *   The helper service for converting address information to profile
   *   entities.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    HelperService $order_helper,
    EventDispatcherInterface $event_dispatcher,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->shipmentStorage = $entity_type_manager->getStorage('commerce_shipment');
    $this->orderHelper = $order_helper;
    $this->eventDispatcher = $event_dispatcher;
    $this->shippingConfig = $config_factory->get('commerce_amws_shipping.settings');
    $this->logger = $logger_factory->get(self::LOGGER_CHANNEL);
  }

  /**
   * Creates a shipment for an Amazon MWS being imported.
   *
   * The shipping service is defined at the order level by Amazon MWS, even
   * though shipping costs are defined at the order item level. We therefore
   * always create only one shipment per order. Applications can bundle order
   * items in shipments as they wish by adding their own event
   * `OrderEvents::ORDER_INSERT` subscriber.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The Drupal Commerce order.
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order being improted.
   * @param string $profile_type
   *   The type of the profile that will hold the shipping address.
   */
  public function createShipment(
    OrderInterface $order,
    \AmazonOrder $amws_order,
    $profile_type
  ) {
    $amws_address = $amws_order->getShippingAddress();
    if (!$amws_address) {
      $message = sprintf(
        'Cannot create a shipment for order with Amazon MWS ID "%s" because it does not contain a shipping address.',
        $amws_order->getAmazonOrderId()
      );
      $this->logger->info($message);
      return;
    }

    $shipping_profile = $this->orderHelper->amwsAddressToCustomerProfile(
      $order,
      $amws_order,
      $profile_type
    );
    $shipment = $this->doCreateShipment($order, $amws_order, $shipping_profile);

    $order->set('shipments', [$shipment]);

    // Dispatch an event that allows subscribers to modify the profile entity
    // after it has been saved. This allows the subscriber to know whether the
    // profile is a shipping or billing profile by knowing the profile's ID and
    // comparing it with the order's profile IDs.
    $event = new AmwsProfileEvent($shipping_profile, $order, $amws_order);
    $this->eventDispatcher->dispatch(AmwsProfileEvents::PROFILE_INSERT, $event);

    if ($event->getSaveProfile()) {
      $shipping_profile->save();
    }
  }

  /**
   * Performs the actual shipment creation for an Amazon MWS order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The Drupal Commerce order.
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order being improted.
   * @param \Drupal\profile\Entity\ProfileInterface $shipping_profile
   *   The shipping profile.
   *
   * @return \Drupal\commerce_shipping\Entity\ShipmentInterface
   *   The created shipment.
   */
  protected function doCreateShipment(
    OrderInterface $order,
    \AmazonOrder $amws_order,
    ProfileInterface $shipping_profile
  ) {
    $shipment = $this->shipmentStorage->create([
      'type' => self::DEFAULT_SHIPMENT_TYPE,
      'order_id' => $order->id(),
      'package_type' => self::DEFAULT_PACKAGE_TYPE,
      'state' => self::DEFAULT_SHIPMENT_STATE,
    ]);
    $shipment->set('title', 'Shipment');
    $shipment->setShippingProfile($shipping_profile);

    // Shipping method and service.
    $shipping_method_id = $this->shippingConfig->get('shipping_method_id');
    if ($shipping_method_id) {
      $shipment->setShippingMethodId($shipping_method_id);
      $shipment->setShippingService(self::DEFAULT_SHIPPING_SERVICE);
    }
    else {
      $message = sprintf(
        'No appropriate shipping method was found for Amazon MWS order with ID "%s". The ID of the corresponding Drupal Commerce order is "%s".',
        $amws_order->getAmazonOrderId(),
        $order->id()
      );
      $this->logger->warning($message);
    }

    $shipment->setAmount($this->shippingAmount($amws_order));
    $this->doCreateShippingAdjustment($order, $shipment);

    // Shipment items.
    $shipment_items = [];
    foreach ($order->getItems() as $order_item) {
      $shipment_items[] = $this->doCreateShipmentItem($order_item);
    }

    $shipment->setItems($shipment_items);
    $shipment->save();

    return $shipment;
  }

  /**
   * Creates a shipment item for the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item for which to create the shipment.
   *
   * @return \Drupal\commerce_shipping\ShipmentItem
   *   The created shipment item.
   */
  protected function doCreateShipmentItem(OrderItemInterface $order_item) {
    $quantity = $order_item->getQuantity();

    // Some products may not have weight e.g. gift wraps. We therefore need to
    // create shipment items for them as well with zero weight.
    $purchased_entity = $order_item->getPurchasedEntity();
    if ($purchased_entity->hasField('weight')) {
      $weight = $order_item->getPurchasedEntity()
        ->get('weight')
        ->first()
        ->toMeasurement();
    }
    else {
      $weight = new Weight('0', WeightUnit::POUND);
    }

    return new ShipmentItem([
      'order_item_id' => $order_item->id(),
      'title' => $order_item->getTitle(),
      'quantity' => $quantity,
      'weight' => $weight->multiply($quantity),
      'declared_value' => $order_item->getUnitPrice()->multiply($quantity),
    ]);
  }

  /**
   * Adds a shipping adjustment to the given order and for the given shipment.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to which to add the adjustment.
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment for which the adjustment will be created.
   */
  protected function doCreateShippingAdjustment(
    OrderInterface $order,
    ShipmentInterface $shipment
  ) {
    $amount = $shipment->getAmount();
    if (!$amount || $amount->getNumber() == '0') {
      return;
    }

    $order->addAdjustment(new Adjustment([
      'type' => 'shipping',
      'label' => $this->t('Shipping'),
      'amount' => $amount,
      'source_id' => $shipment->id(),
    ]));
  }

  /**
   * Calculates the shipping amount for the given Amazon MWS order.
   *
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order to calculate the shipping amount for.
   *
   * @return \Drupal\commerce_price\Price
   *   The shipping amount.
   */
  protected function shippingAmount(\AmazonOrder $amws_order) {
    $amws_order_total = $amws_order->getOrderTotal();
    $amount = new Price('0', $amws_order_total['CurrencyCode']);

    // The shipping amount for the order is the sum of the shipping amount for
    // all individual order items.
    $amws_order_item_list = $amws_order->fetchItems();
    $amws_order_items = $amws_order_item_list->getItems();

    foreach ($amws_order_items as $amws_order_item) {
      if (!empty($amws_order_item['ShippingPrice'])) {
        $price = new Price(
          $amws_order_item['ShippingPrice']['Amount'],
          $amws_order_item['ShippingPrice']['CurrencyCode']
        );
        $amount = $amount->add($price);
      }
      if (!empty($amws_order_item['ShippingDiscount'])) {
        $discount = new Price(
          $amws_order_item['ShippingDiscount']['Amount'],
          $amws_order_item['ShippingDiscount']['CurrencyCode']
        );
        $amount = $amount->subtract($discount);
      }
    }

    return $amount;
  }

}
