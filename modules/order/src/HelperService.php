<?php

namespace Drupal\commerce_amws_order;

use Drupal\commerce_amws_order\Event\ProfileEvent as AmwsProfileEvent;
use Drupal\commerce_amws_order\Event\ProfileEvents as AmwsProfileEvents;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides helper functions related to Amazon MWS orders.
 */
class HelperService {

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
  protected $config;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new HelperService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EventDispatcherInterface $event_dispatcher,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->profileStorage = $entity_type_manager->getStorage('profile');
    $this->eventDispatcher = $event_dispatcher;
    $this->config = $config_factory->get('commerce_amws_order.settings');
    $this->logger = $logger_factory->get(COMMERCE_AMWS_ORDER_LOGGER_CHANNEL);
  }

  /**
   * Creates a profile for the given Amazon MWS address data.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The created order that will hold the profile.
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order.
   * @param string $profile_type
   *   The type of the profile that will be created. Defaults to the `customer`
   *   profile type that is the default profile used by Drupal Commerce.
   * @param bool $save
   *   Whether to save the created profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The created profile entity.
   */
  public function amwsAddressToCustomerProfile(
    OrderInterface $order,
    \AmazonOrder $amws_order,
    $profile_type = 'customer',
    $save = TRUE
  ) {
    $amws_address = $amws_order->getShippingAddress();
    // Address.
    $address = $this->parseAmwsAddress($amws_address);
    // Name.
    if (!empty($amws_address['Name'])) {
      $address += $this->parseAmwsName($amws_address['Name']);
    }

    $profile = $this->profileStorage->create([
      'type' => $profile_type,
      'uid' => $order->getCustomerId(),
      'address' => $address,
    ]);

    // Dispatch an event that allows subscribers to modify the profile entity
    // before saved and returned. Can be used to set the values of custom
    // fields.
    $event = new AmwsProfileEvent($profile, $order, $amws_order);
    $this->eventDispatcher->dispatch(AmwsProfileEvents::PROFILE_CREATE, $event);

    if ($save) {
      $profile->save();
    }

    return $profile;
  }

  /**
   * Parses Amazon MWS address data and converts it to Drupal address data.
   *
   * @param array $amws_address
   *   An array containing the Amazon MWS address data.
   *
   * @return array
   *   An array containing the corresponding values for a Drupal address field.
   */
  protected function parseAmwsAddress(array $amws_address) {
    $address = [];

    if (!empty($amws_address['CountryCode'])) {
      $address['country_code'] = $amws_address['CountryCode'];
    }
    if (!empty($amws_address['StateOrRegion'])) {
      if (empty($amws_address['CountryCode'])) {
        $address['administrative_area'] = $amws_address['StateOrRegion'];
      }
      else {
        $address['administrative_area'] = $this->parseAmwsStateOrRegion(
          $amws_address['CountryCode'],
          $amws_address['StateOrRegion']
        );
      }
    }
    if (!empty($amws_address['City'])) {
      $address['locality'] = $amws_address['City'];
    }
    if (!empty($amws_address['PostalCode'])) {
      $address['postal_code'] = $amws_address['PostalCode'];
    }
    if (!empty($amws_address['AddressLine1'])) {
      $address['address_line1'] = $amws_address['AddressLine1'];
    }
    // Amazon MWS address provides 3 address lines while Drupal address supports
    // only 2. We will be entering the concatenation of the 2nd and 3rd Amazon
    // MWS address line as the 2nd Drupal address line.
    if (!empty($amws_address['AddressLine2'])) {
      $address['address_line2'] = $amws_address['AddressLine2'];
    }
    if (!empty($amws_address['AddressLine3'])) {
      if (empty($address['address_line2'])) {
        $address['address_line2'] = $amws_address['AddressLine3'];
      }
      else {
        $address['address_line2'] .= ', ' . $amws_address['AddressLine3'];
      }
    }

    return $address;
  }

  /**
   * Converts an Amazon MWS name into a 3-component Drupal address name.
   *
   * We ignore the additional name and simply split the name into a given name
   * and a family name.
   *
   * @param string $name
   *   The name to parse.
   *
   * @return array
   *   An array containing the components of the name as expected by a Drupal
   *   address field.
   */
  protected function parseAmwsName($name) {
    $name_parts = explode(' ', $name);

    $names = [];
    $names['family_name'] = array_pop($name_parts);
    if (count($name_parts)) {
      $names['given_name'] = implode(' ', $name_parts);
    }

    return $names;
  }

  /**
   * Converts the state or region field to the an administrative area code.
   *
   * In some old addresses Amazon MWS gives us the full USA state name instead
   * of the 2-digit code e.g. NEW MEXICO instead of NM. In such cases, try to
   * identify the code using the JSON resource files from the
   * `commerceguys/addressing` library.
   *
   * @param string $country_code
   *   The address's country code.
   * @param string $state
   *   The state code or name.
   *
   * @return string
   *   The parsed state or region.
   */
  protected function parseAmwsStateOrRegion($country_code, $state) {
    $convert_states = $this->config->get('general.address_convert_states');
    if (!$convert_states) {
      return $state;
    }

    // For now, only USA states are supported.
    if ($country_code !== 'US') {
      return $state;
    }

    $state_lowercase = strtolower($state);

    // Load the list of administrative areas (subdivisions) for the country from
    // the `commerceguys/addressing` library. We expect the library to be under
    // the `vendor` folder in the standard location; but we may make this
    // configurable in the future.
    $subdivisions_dir = realpath(DRUPAL_ROOT . '/../vendor/commerceguys/addressing/resources/subdivision');
    $filename = $subdivisions_dir . '/' . $country_code . '.json';
    if (!file_exists($filename)) {
      $message = sprintf(
        'The file containing the subdivisions for the country with code "%s" was not found at the expected location ("%").',
        $country_code,
        $filename
      );
      $this->logger->warning($message);

      return $state;
    }

    $subdivisions = json_decode(file_get_contents($filename));
    $subdivisions = get_object_vars($subdivisions->subdivisions);

    // First, check if we already have the state/region code.
    $subdivision_keys = array_map('strtolower', array_keys($subdivisions));
    if (in_array($state_lowercase, $subdivision_keys)) {
      return $state;
    }

    // Otherwise, check for a name match.
    foreach ($subdivisions as $code => $subdivision) {
      $has_name = property_exists($subdivision, 'name');
      if ($has_name && strtolower($subdivision->name) === $state_lowercase) {
        return $code;
      }
    }

    return $state;
  }

}
