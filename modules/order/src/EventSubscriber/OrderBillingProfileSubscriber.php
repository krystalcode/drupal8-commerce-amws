<?php

namespace Drupal\commerce_amazon_mws_order\EventSubscriber;

use Drupal\commerce_amazon_mws_order\Event\OrderEvent as AmwsOrderEvent;
use Drupal\commerce_amazon_mws_order\Event\OrderEvents as AmwsOrderEvents;
use Drupal\commerce_amazon_mws_order\Event\ProfileEvent as AmwsProfileEvent;
use Drupal\commerce_amazon_mws_order\Event\ProfileEvents as AmwsProfileEvents;
use Drupal\commerce_amazon_mws_order\Form\SettingsForm as OrderConfig;
use Drupal\commerce_amazon_mws_order\HelperService as OrderHelperService;

use Drupal\commerce_order\Entity\OrderInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generates the billing profile for Amazon MWS orders.
 *
 * Modules wishing to override this logic can register their
 * own event subscriber with a higher weight (e.g. -10).
 */
class OrderBillingProfileSubscriber implements EventSubscriberInterface {

  /**
   * The profile type to be used for creating the order's billing profile.
   */
  const DEFAULT_BILLING_PROFILE_TYPE = 'customer';

  /**
   * The name of the logger channel to use.
   */
  const LOGGER_CHANNEL = 'commerce_amazon_mws_order';

  /**
   * The profile storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The Amazon MWS order configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $orderConfig;

  /**
   * The Amazon MWS order helper service.
   *
   * @var \Drupal\commerce_amazon_mws_order\HelperService
   */
  protected $orderHelper;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new OrderBillingProfileSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\commerce_amazon_mws_order\HelperService $order_helper
   *   The Amazon MWS order helper service for converting address information to
   *   profile entities.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EventDispatcherInterface $event_dispatcher,
    ConfigFactoryInterface $config_factory,
    OrderHelperService $order_helper,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->profileStorage = $entity_type_manager->getStorage('profile');
    $this->eventDispatcher = $event_dispatcher;
    $this->orderConfig = $config_factory->get('commerce_amazon_mws_order.settings');
    $this->orderHelper = $order_helper;
    $this->logger = $logger_factory->get(self::LOGGER_CHANNEL);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      AmwsOrderEvents::ORDER_CREATE => ['setBillingProfile', -100],
    ];
    return $events;
  }

  /**
   * Sets the billing profile for the order, if not already set explicitly.
   *
   * @param \Drupal\commerce_amazon_mws_order\Event\OrderEvent $event
   *   The order event.
   */
  public function setBillingProfile(AmwsOrderEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();
    /** @var \AmazonOrder $amws_order */
    $amws_order = $event->getAmwsOrder();

    if ($order->getBillingProfile()) {
      return;
    }

    if (!$this->orderConfig->get('billing_profile.status')) {
      return;
    }

    $source = $this->orderConfig->get('billing_profile.source');
    if (!$source) {
      $message = 'Adding a billing profile to Amazon MWS orders is enabled but there is no source defined.';
      $this->logger->error($message);
      return;
    }

    // Create the billing profile depending on the configured source.
    $profile = NULL;
    switch ($source) {
      case OrderConfig::BILLING_PROFILE_SOURCE_SHIPPING_INFORMATION:
        $profile = $this->profileFromShipping($order, $amws_order);
        break;

      case OrderConfig::BILLING_PROFILE_SOURCE_CUSTOM:
        $profile = $this->profileFromCustom($order, $amws_order);
        break;
    }

    if (!$profile) {
      return;
    }

    $order->setBillingProfile($profile);

    // Dispatch an event that allows subscribers to modify the profile entity
    // after it has been saved. This allows the subscriber to know whether the
    // profile is a shipping or billing profile by knowing the profile's ID and
    // comparing it with the order's profile IDs.
    $event = new AmwsProfileEvent($profile, $order, $amws_order);
    $this->eventDispatcher->dispatch(AmwsProfileEvents::PROFILE_INSERT, $event);

    if ($event->getSaveProfile()) {
      $profile->save();
    }
  }

  /**
   * Creates a profile based on the Amazon MWS order's shipping information.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The Drupal Commerce order being created.
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order being imported.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The created profile, or NULL if there is no shipping information
   *   available.
   */
  protected function profileFromShipping(
    OrderInterface $order,
    \AmazonOrder $amws_order
  ) {
    $amws_address = $amws_order->getShippingAddress();
    if (!$amws_address) {
      $message = sprintf(
        'Cannot create a billing profile from shipping information for Amazon MWS order with ID "%s" because the shipping information is not available.',
        $amws_order->getAmazonOrderId()
      );
      $this->logger->warning($message);
      return;
    }

    return $this->orderHelper->amwsAddressToCustomerProfile(
      $order,
      $amws_order,
      self::DEFAULT_BILLING_PROFILE_TYPE
    );
  }

  /**
   * Creates a profile from custom information defined in the module's settings.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The Drupal Commerce order being created.
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order being imported.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The created profile, or NULL if there is no custom information
   *   available.
   */
  protected function profileFromCustom(
    OrderInterface $order,
    \AmazonOrder $amws_order
  ) {
    $address = $this->orderConfig->get('billing_profile.custom_address');
    if (!$address) {
      $message = sprintf(
        'Cannot create a billing profile from custom information for Amazon MWS order with ID "%s" because the custom information is not available.',
        $amws_order->getAmazonOrderId()
      );
      $this->logger->warning($message);
      return;
    }

    $profile = $this->profileStorage->create([
      'type' => self::DEFAULT_BILLING_PROFILE_TYPE,
      'uid' => $order->getCustomerId(),
      'address' => $address,
    ]);

    // Dispatch an event that allows subscribers to modify the profile entity
    // before saved and returned. Can be used to set the values of custom
    // fields.
    $event = new AmwsProfileEvent($profile, $order, $amws_order);
    $this->eventDispatcher->dispatch(AmwsProfileEvents::PROFILE_CREATE, $event);

    $profile->save();

    return $profile;
  }

}
