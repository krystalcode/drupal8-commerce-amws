<?php

namespace Drupal\Tests\commerce_amws_order\Unit\Adapters\CpigroupPhpAmazonMws;

use Drupal\commerce_amws\Entity\StoreInterface as AmwsStoreInterface;
use Drupal\commerce_amws_order\Adapters\CpigroupPhpAmazonMws\OrderStorage;
use Drupal\commerce_amws_order\OrderService;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\UnitTestCase;

use AmazonOrder;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class OrderStorageTest.
 *
 * Tests the OrderStorage class.
 *
 * @coversDefaultClass \Drupal\commerce_amws_order\Adapters\CpigroupPhpAmazonMws\OrderStorage
 * @group commerce_amws_order
 * @package Drupal\Tests\commerce_amws_order\Unit
 */
class OrderStorageTest extends UnitTestCase {

  /**
   * The CpigroupPhpAmazonMws Order Storage.
   *
   * @var \Drupal\commerce_amws_order\Adapters\CpigroupPhpAmazonMws\OrderStorage
   */
  protected $orderStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a new OrderStorage class.
    $this->createOrderStorageClass();
  }

  /**
   * @covers ::filterByStatus
   */
  public function testFilterByStatus() {
    $options = [];

    // Test filterByStatus() will return NULL without a 'statuses' array.
    $this->assertNull($this->invokeMethod(
      $this->orderStorage,
      'filterByStatus',
      [$options]
    ));

    // Test filterByStatus() will throw an exception with an unsupported status.
    $options['statuses'] = [
      'unsupported_status' => 'very_late',
    ];
    try {
      $this->invokeMethod(
        $this->orderStorage,
        'filterByStatus',
        [$options]
      );
    }
    catch (Exception $e) {
      $this->assertEquals(
        $e->getMessage(),
        'You have requested to fetch Amazon MWS orders with one or more unsupported statuses (very_late). Supported statuses are: Canceled, PartiallyShipped, Unfulfillable, Unshipped.'
      );
    }
  }

  /**
   * @covers ::filterByTime
   */
  public function testFilterByTime() {
    $options = [];

    // Test filterByTime() will throw an exception with both a created and
    // updated filter.
    $time = strtotime('-1 day');
    $options['created'] = ['after' => $time];
    $options['updated'] = ['after' => $time];
    try {
      $this->invokeMethod(
        $this->orderStorage,
        'filterByTime',
        [$options]
      );
    }
    catch (Exception $e) {
      $this->assertEquals(
        $e->getMessage(),
        'You may limit the orders fetched from Amazon MWS by creation or update time, but not by both.'
      );
    }

    // Test filterByTime() will throw an exception if the time the orders were
    // created/updated after filter is missing.
    $options = ['updated' => []];
    try {
      $this->invokeMethod(
        $this->orderStorage,
        'filterByTime',
        [$options]
      );
    }
    catch (Exception $e) {
      $this->assertEquals(
        $e->getMessage(),
        'The time the orders were updated after is required in order to filter orders by time.'
      );
    }

    // Test filterByTime() will pass with the correct options.
    $options['updated'] = ['after' => $time];
    // This method doesn't return anything so it not throwing any exception can
    // be considered a pass.
    $this->assertNull($this->invokeMethod(
      $this->orderStorage,
      'filterByTime',
      [$options]
    ));
  }

  /**
   * @covers ::postFilterByStatus
   */
  public function testPostFilterByStatus() {
    $amws_orders = $this->getAmazonOrders();
    $options = [];

    // Test postFilterByStatus() will return back both orders when we don't have
    // the post_filters array.
    $result = $this->invokeMethod(
      $this->orderStorage,
      'postFilterByStatus',
      [$amws_orders, $options]
    );
    $this->assertEquals($amws_orders, $result);

    // Test postFilterByStatus() will only return the matching Amazon order with
    // the unshipped status.
    $options['post_filters']['statuses'] = ['Unshipped'];
    $result = $this->invokeMethod(
      $this->orderStorage,
      'postFilterByStatus',
      [$amws_orders, $options]
    );

    $this->assertEquals($amws_orders[1], reset($result));
  }

  /**
   * @covers ::postFilterLimit
   */
  public function testPostFilterLimit() {
    $amws_orders = $this->getAmazonOrders();

    $options = [];
    // Test postFilterLimit() will return back both orders when we don't have
    // the post_filters array.
    $result = $this->invokeMethod(
      $this->orderStorage,
      'postFilterLimit',
      [$amws_orders, $options]
    );
    $this->assertEquals($amws_orders, $result);

    // Test postFilterLimit() will only return 1 order if we put a limit on
    // it.
    $options['post_filters']['limit'] = 1;
    $result = $this->invokeMethod(
      $this->orderStorage,
      'postFilterLimit',
      [$amws_orders, $options]
    );
    // Assert we only have 1 order returned.
    $this->assertCount(1, $result);
    // Assert it's the first order.
    $this->assertEquals($amws_orders[0], reset($result));
  }

  /**
   * Creates a mock OrderStorage class.
   */
  protected function createOrderStorageClass() {
    // First, let's mock some classes needed for the OrderStorage class.
    /** @var \Drupal\commerce_amws\Entity\StoreInterface $amws_store */
    $amws_store = $this->prophesize(AmwsStoreInterface::class);
    $amws_store->id()->willReturn(101);
    $amws_store->getSellerId()->willReturn('test-amazon-seller-id');
    $amws_store->getMarketplaceId()->willReturn('test-marketplace-id');
    $amws_store->getAwsAccessKeyId()->willReturn('test-key-id');
    $amws_store->getSecretKey()->willReturn('test-secret-key');
    $amws_store->getMwsAuthToken()->willReturn('test-mwsauth-token');

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $amws_order_service = $this->prophesize(OrderService::class);
    $logger = $this->prophesize(LoggerInterface::class);

    $this->orderStorage = new OrderStorage(
      $amws_store->reveal(),
      $entity_storage->reveal(),
      $amws_order_service->reveal(),
      $logger->reveal()
    );
  }

  /**
   * Creates and returns a couple of mock amazon orders.
   *
   * @return array
   *   An array of amazon orders.
   */
  protected function getAmazonOrders() {
    // Create a couple of mock Amazon order classes.
    /** @var \AmazonOrder $amws_order */
    $amws_order_1 = $this->prophesize(AmazonOrder::class);
    $amws_order_1->getOrderStatus()->willReturn('Completed');
    $amws_order_1 = $amws_order_1->reveal();

    $amws_order_2 = $this->prophesize(AmazonOrder::class);
    $amws_order_2->getOrderStatus()->willReturn('Unshipped');
    $amws_order_2 = $amws_order_2->reveal();

    // Add the mock orders into our $amws_orders array.
    $amws_orders = [$amws_order_1, $amws_order_2];

    return $amws_orders;
  }

  /**
   * Call protected/private method of a class.
   *
   * We'll need this special function to test the protected filter functions.
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
