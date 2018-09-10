<?php

namespace Drupal\Tests\commerce_amws_order\Unit;

use Drupal\commerce_amws_order\HelperService;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\ProfileStorageInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;

use AmazonOrder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

define('COMMERCE_AMWS_ORDER_LOGGER_CHANNEL', 'commerce_amws_order');

/**
 * Class HelperServiceTest.
 *
 * Tests the HelperService functions.
 *
 * @coversDefaultClass \Drupal\commerce_amws_order\HelperService
 * @group commerce_amws_order
 * @package Drupal\Tests\commerce_amws_order\Unit
 */
class HelperServiceTest extends UnitTestCase {

  /**
   * The Amazon MWS helper service.
   *
   * @var \Drupal\commerce_amws_order\HelperService
   */
  protected $amwsHelperService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a new Helper Service class.
    $this->createHelperServiceClass();
  }

  /**
   * @covers ::parseAmwsAddress
   */
  public function testParseAmwsAddress() {
    $address = $this->invokeMethod(
      $this->amwsHelperService,
      'parseAmwsAddress',
      [$this->getShippingAddress()]
    );

    // Assert the address is parsed in the format we expect.
    $this->assertEquals('2700 First Avenue', $address['address_line1']);
    $this->assertEquals('Apartment 1, Suite 16', $address['address_line2']);
    $this->assertEquals('Seattle', $address['locality']);
    $this->assertEquals('WA', $address['administrative_area']);
    $this->assertEquals('98102', $address['postal_code']);
    $this->assertEquals('US', $address['country_code']);
  }

  /**
   * @covers ::parseAmwsName
   */
  public function testParseAmwsName() {
    $name = $this->invokeMethod(
      $this->amwsHelperService,
      'parseAmwsName',
      [$this->getShippingAddress()['Name']]
    );

    // Assert the name is parsed in the format we expect.
    $this->assertEquals('Smith', $name['family_name']);
    $this->assertEquals('John', $name['given_name']);
  }

  /**
   * @covers ::amwsAddressToCustomerProfile
   */
  public function testAmwsAddressToCustomerProfile() {
    // Create a mock Amazon order class.
    $amazonOrder = $this->prophesize(AmazonOrder::class);
    $amazonOrder->getShippingAddress()
      ->willReturn($this->getShippingAddress());
    $amazonOrder = $amazonOrder->reveal();

    // Create a mock commerce order.
    $order = $this->prophesize(OrderInterface::class);
    $order->id()->willReturn('058-1233752-8214740');
    $order->getCustomerId()->willReturn('111');
    $order = $order->reveal();

    // Now, test the amwsAddressToCustomerProfile() function.
    $profile = $this->amwsHelperService->amwsAddressToCustomerProfile(
      $order,
      $amazonOrder
    );

    // Assert that the profile contains what we're expecting.
    $this->assertEquals(121, $profile->id());
    $this->assertEquals('customer', $profile->getEntityTypeId());
    $this->assertEquals(111, $profile->getOwnerId());
  }

  /**
   * Creates a mock helper service class.
   */
  protected function createHelperServiceClass() {
    // First, let's mock some classes needed for the HelperService class.
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->prophesize(ProfileInterface::class);
    $profile->id()->willReturn(121);
    $profile->getEntityTypeId()->willReturn('customer');
    $profile->getOwnerId()->willReturn(111);
    $profile->save()->willReturn($profile);
    $profile = $profile->reveal();

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->prophesize(ProfileStorageInterface::class);
    $address = [
      'country_code' => 'US',
      'administrative_area' => 'WA',
      'locality' => 'Seattle',
      'postal_code' => '98102',
      'address_line1' => '2700 First Avenue',
      'address_line2' => 'Apartment 1, Suite 16',
      'family_name' => 'Smith',
      'given_name' => 'John',
    ];
    $profile_storage->create([
      'type' => 'customer',
      'uid' => 111,
      'address' => $address,
    ])->willReturn($profile);
    $profile_storage = $profile_storage->reveal();

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('profile')->willReturn($profile_storage);
    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('commerce_amws_order.settings')
      ->willReturn($this->prophesize(ImmutableConfig::class)->reveal());
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);

    $this->amwsHelperService = new HelperService(
      $entity_type_manager->reveal(),
      $event_dispatcher->reveal(),
      $config_factory->reveal(),
      $logger_factory->reveal()
    );
  }

  /**
   * Returns a sample array containing the Amazon MWS address data.
   *
   * @return array
   *   The Amazon MWS address data.
   */
  protected function getShippingAddress() {
    return [
      'Name' => 'John Smith',
      'AddressLine1' => '2700 First Avenue',
      'AddressLine2' => 'Apartment 1',
      'AddressLine3' => 'Suite 16',
      'City' => 'Seattle',
      'County' => 'County',
      'District' => 'District',
      'StateOrRegion' => 'WA',
      'PostalCode' => '98102',
      'CountryCode' => 'US',
      'Phone' => '123',
    ];
  }

  /**
   * Call protected/private method of a class.
   *
   * We'll need this special function to test the protected parse functions.
   *
   * @param object &$object
   *   Instantiated object that we will run method on.
   * @param string $methodName
   *   Method name to call.
   * @param array $parameters
   *   Array of parameters to pass into method.
   *
   * @return mixed
   *   Method return.
   */
  protected function invokeMethod(&$object, $methodName, array $parameters = []) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(TRUE);

    return $method->invokeArgs($object, $parameters);
  }

}
