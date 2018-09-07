<?php

namespace Drupal\Tests\commerce_amws_order\Unit;

use \AmazonOrder;

use Drupal\commerce_amws_order\HelperService;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\ProfileStorageInterface;
use Drupal\Tests\UnitTestCase;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * An Amazon order object.
   *
   * @var \AmazonOrder
   */
  protected $amazonOrder;

  /**
   * The Amazon MWS helper service.
   *
   * @var \Drupal\commerce_amws_order\HelperService
   */
  protected $orderHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a new Helper Service class.
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
      "country_code" => "US",
      "administrative_area" => "WA",
      "locality" => "Seattle",
      "postal_code" => "98102",
      "address_line1" => "2700 First Avenue",
      "address_line2" => "Apartment 1, Suite 16",
      "family_name" => "Smith",
      "given_name" => "John",
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

    $this->orderHelper = new HelperService(
      $entity_type_manager->reveal(),
      $event_dispatcher->reveal(),
      $config_factory->reveal(),
      $logger_factory->reveal()
    );

    // Create a mock Amazon order class.
    $shipping_address = [
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

    $this->amazonOrder = $this->prophesize(AmazonOrder::class);
    $this->amazonOrder->getShippingAddress()->willReturn($shipping_address);
    $this->amazonOrder = $this->amazonOrder->reveal();

    // Create a mock commerce order.
    $this->order = $this->prophesize(OrderInterface::class);
    $this->order->id()->willReturn('058-1233752-8214740');
    $this->order->getCustomerId()->willReturn('111');
    $this->order = $this->order->reveal();
  }

  /**
   * @covers ::amwsAddressToCustomerProfile
   */
  public function testAmwsAddressToCustomerProfile() {
    $profile = $this->orderHelper->amwsAddressToCustomerProfile(
      $this->order,
      $this->amazonOrder
    );

    $this->assertEquals(121, $profile->id());
  }

}
