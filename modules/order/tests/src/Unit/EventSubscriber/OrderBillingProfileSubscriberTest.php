<?php

namespace Drupal\Tests\commerce_amws_order\Unit\EventSubscriber;

use Drupal\commerce_amws_order\EventSubscriber\OrderBillingProfileSubscriber;

use Drupal\commerce_amws_order\HelperService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class OrderBillingProfileSubscriberTest.
 *
 * Tests the OrderBillingProfileSubscriber class.
 *
 * @coversDefaultClass \Drupal\commerce_amws_order\EventSubscriber\OrderBillingProfileSubscriber
 * @group commerce_amws_order
 * @package Drupal\Tests\commerce_amws_order\Unit
 */
class OrderBillingProfileSubscriberTest extends UnitTestCase {

  /**
   * The order billing profile subscriber class.
   *
   * @var \Drupal\commerce_amws_order\EventSubscriber\OrderBillingProfileSubscriber
   */
  protected $orderSubscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createSubscriberClass();
  }

  /**
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents() {
    $events = [
      'commerce_amws_order.commerce_order.create' => ['setBillingProfile', -100],
    ];
    $this->assertEquals($events, $this->invokeMethod(
      $this->orderSubscriber,
      'getSubscribedEvents',
      []
    ));
  }

  /**
   * Creates a mock OrderBillingProfileSubscriber class.
   */
  protected function createSubscriberClass() {
    // First, let's mock some classes needed for the subscriber class.
    $profile = $this->prophesize(ProfileInterface::class);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('profile')
      ->willReturn($profile->reveal());
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('commerce_amws_order.settings')
      ->willReturn($this->prophesize(ImmutableConfig::class)->reveal());
    $amws_helper_service = $this->prophesize(HelperService::class);
    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);

    $this->orderSubscriber = new OrderBillingProfileSubscriber(
      $entity_type_manager->reveal(),
      $event_dispatcher->reveal(),
      $config_factory->reveal(),
      $amws_helper_service->reveal(),
      $logger_factory->reveal()
    );
  }

  /**
   * Call protected/private method of a class.
   *
   * We'll need this special function to test the protected functions.
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
