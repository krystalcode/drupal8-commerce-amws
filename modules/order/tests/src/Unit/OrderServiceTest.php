<?php

namespace Drupal\Tests\commerce_amws_order\Unit;

use AmazonOrder;
use AmazonOrderItemList;

use Drupal\commerce_amws_order\OrderService;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\OrderItemStorageInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\ProductVariationStorageInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class OrderServiceTest.
 *
 * Tests the OrderService functions.
 *
 * @coversDefaultClass \Drupal\commerce_amws_order\OrderService
 * @group commerce_amws_order
 * @package Drupal\Tests\commerce_amws_order\Unit
 */
class OrderServiceTest extends UnitTestCase {

  /**
   * The Amazon MWS order service.
   *
   * @var \Drupal\commerce_amws_order\OrderService
   */
  protected $amwsOrderService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a new Order Service class.
    $this->createOrderServiceClass();
  }

  /**
   * Call protected/private method of a class.
   *
   * We'll need this special function to test the protected validate functions.
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
  public function invokeMethod(&$object, $methodName, array $parameters = []) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(TRUE);

    return $method->invokeArgs($object, $parameters);
  }

  /**
   * @covers ::validateAmwsOrder
   */
  public function testValidateAmwsOrder() {
    // Fetch sample order data first.
    $order_data_1 = $order_data_2 = $this->getOrderData();

    // Test that validate will fail.
    // Unset OrderStatus from the order data so that we can confirm the validate
    // will fail.
    unset($order_data_1['OrderStatus']);
    // Create a mock Amazon order class.
    $amazonOrder = $this->prophesize(AmazonOrder::class);
    $amazonOrder->getAmazonOrderId()->willReturn('101');
    $amazonOrder->getData()->willReturn($order_data_1);
    $amazonOrder = $amazonOrder->reveal();

    $validated = $this->invokeMethod(
      $this->amwsOrderService,
      'validateAmwsOrder',
      [$amazonOrder]
    );
    $this->assertEquals(FALSE, $validated);

    // Test that validate will pass.
    // Create a mock Amazon order class.
    $amazonOrder = $this->prophesize(AmazonOrder::class);
    $amazonOrder->getAmazonOrderId()->willReturn('101');
    $amazonOrder->getData()->willReturn($order_data_2);
    $amazonOrder = $amazonOrder->reveal();

    $validated = $this->invokeMethod(
      $this->amwsOrderService,
      'validateAmwsOrder',
      [$amazonOrder]
    );
    $this->assertEquals(TRUE, $validated);
  }

  /**
   * @covers ::validateAmwsOrderItem
   */
  public function testValidateAmwsOrderItem() {
    // Fetch sample order item data first.
    $order_item_data = $this->getOrderItemData();
    $order_item_data_1 = $order_item_data_2 = reset($order_item_data);

    // Test that validate will fail.
    // Unset the SellerSKU from the order item data so that we can confirm the
    // validate will fail.
    unset($order_item_data_1['SellerSKU']);
    $validated = $this->invokeMethod(
      $this->amwsOrderService,
      'validateAmwsOrderItem',
      [$order_item_data_1]
    );
    $this->assertEquals(FALSE, $validated);

    // Test that validate will pass.
    $validated = $this->invokeMethod(
      $this->amwsOrderService,
      'validateAmwsOrderItem',
      [$order_item_data_2]
    );
    $this->assertEquals(TRUE, $validated);
  }

  /**
   * @covers ::validateAmwsOrderItems
   */
  public function testValidateAmwsOrderItems() {
    // Test that validate will fail.
    // Create a mock Amazon order item class which will return NULL for
    // getItems().
    $order_items_class = $this->prophesize(AmazonOrderItemList::class);
    $order_items_class->getItems()->willReturn(NULL);
    $order_items_class = $order_items_class->reveal();

    // Create a mock Amazon order class.
    $amazonOrder = $this->prophesize(AmazonOrder::class);
    $amazonOrder->getData()->willReturn($this->getOrderData());
    $amazonOrder->getAmazonOrderId()->willReturn('101');
    $amazonOrder->fetchItems()->willReturn($order_items_class);
    $amazonOrder = $amazonOrder->reveal();

    $validated = $this->invokeMethod(
      $this->amwsOrderService,
      'validateAmwsOrderItems',
      [$amazonOrder]
    );
    $this->assertEquals(FALSE, $validated);

    // Test that validate will pass.
    // Create a mock Amazon order item class which will return correct order
    // item data for getItems().
    $order_items_class = $this->prophesize(AmazonOrderItemList::class);
    $order_items_class->getItems()->willReturn($this->getOrderItemData());
    $order_items_class = $order_items_class->reveal();

    // Create a mock Amazon order class.
    $amazonOrder = $this->prophesize(AmazonOrder::class);
    $amazonOrder->getData()->willReturn($this->getOrderData());
    $amazonOrder->getAmazonOrderId()->willReturn('101');
    $amazonOrder->fetchItems()->willReturn($order_items_class);
    $amazonOrder = $amazonOrder->reveal();

    $validated = $this->invokeMethod(
      $this->amwsOrderService,
      'validateAmwsOrderItems',
      [$amazonOrder]
    );
    $this->assertEquals(TRUE, $validated);
  }

  /**
   * @covers ::createOrder
   */
  public function testCreateOrder() {
    // Create a mock Amazon order item class.
    $order_items_class = $this->prophesize(AmazonOrderItemList::class);
    $order_items_class->getItems()->willReturn($this->getOrderItemData());
    $order_items_class = $order_items_class->reveal();

    // Create a mock Amazon order class.
    $amazonOrder = $this->prophesize(AmazonOrder::class);
    $amazonOrder->getData()->willReturn($this->getOrderData());
    $amazonOrder->getAmazonOrderId()->willReturn('101');
    $amazonOrder->getPurchaseDate()
      ->willReturn('2010-10-05T00:06:07.000Z');
    $amazonOrder->getLastUpdateDate()
      ->willReturn('2010-10-05T12:43:16.000Z');
    $amazonOrder->getBuyerEmail()
      ->willReturn('test_buyer@marketplace.amazon.com');
    $amazonOrder->fetchItems()->willReturn($order_items_class);
    $amazonOrder = $amazonOrder->reveal();

    // Now, test createOrder().
    $order = $this->amwsOrderService->createOrder($amazonOrder);

    // Test that the returned order contains what we're expecting.
    $this->assertEquals(1001, $order->id());
  }

  /**
   * Creates a mock OrderService class.
   */
  protected function createOrderServiceClass() {
    // First, let's mock some classes needed for the OrderService class.
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->prophesize(ProductInterface::class);
    $product->getStoreIds()->willReturn([1]);
    $product = $product->reveal();

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
    $product_variation = $this->prophesize(ProductVariationInterface::class);
    $product_variation->id()->willReturn(202);
    $product_variation->getTitle()->willReturn('A Really Good Book');
    $product_variation->getProduct()->willReturn($product);
    $product_variation = $product_variation->reveal();
    /** @var \Drupal\commerce_product\ProductVariationStorageInterface $variation_storage */
    $variation_storage = $this->prophesize(ProductVariationStorageInterface::class);
    $variation_storage->loadBySku('PROD-BOO-01')
      ->willReturn($product_variation);
    $variation_storage = $variation_storage->reveal();

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->id()->willReturn(2001);
    $order_item->set('amws_remote_id', '10')->willReturn(TRUE);
    $price = new Price('10', 'USD');
    $order_item->setUnitPrice($price)->willReturn(TRUE);
    $order_item->save()->willReturn($order_item);
    $order_item = $order_item->reveal();

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->prophesize(OrderItemStorageInterface::class);
    $order_item_storage->load('commerce_order_item')->willReturn($order_item);
    $order_item_storage->create([
      'type' => 'commerce_amws',
      'order_id' => 1001,
      'amws_order_item_id' => '10',
      'purchased_entity' => 202,
      'title' => 'A Really Good Book',
      'quantity' => '2',
    ])->willReturn($order_item);
    $order_item_storage = $order_item_storage->reveal();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->prophesize(OrderInterface::class);
    $order->id()->willReturn(1001);
    $order->set('amws_remote_id', '101')->willReturn(TRUE);
    $order->addItem($order_item)->willReturn($order_item);
    $order->save()->willReturn($order);
    $order = $order->reveal();

    /** @var \Drupal\Core\Entity\EntityStorageInterface $order_storage */
    $order_storage = $this->prophesize(EntityStorageInterface::class);
    $order_storage->load('commerce_order')->willReturn($order);
    $purchase_date = strtotime('2010-10-05T00:06:07.000Z');
    $order_storage->create([
      'type' => 'commerce_amws',
      'uid' => 0,
      'mail' => 'test_buyer@marketplace.amazon.com',
      'created' => $purchase_date,
      'changed' => strtotime('2010-10-05T12:43:16.000Z'),
      'placed' => $purchase_date,
      'completed' => $purchase_date,
      'state' => 'completed',
      'store_id' => 1,
    ])->willReturn($order);
    $order_storage = $order_storage->reveal();

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('commerce_product_variation')
      ->willReturn($variation_storage);
    $entity_type_manager->getStorage('commerce_order')
      ->willReturn($order_storage);
    $entity_type_manager->getStorage('commerce_order_item')
      ->willReturn($order_item_storage);
    $time = $this->prophesize(TimeInterface::class);
    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $logger = $this->prophesize(LoggerInterface::class);
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $logger_factory->get('commerce_amws_order')->willReturn($logger->reveal());

    $this->amwsOrderService = new OrderService(
      $entity_type_manager->reveal(),
      $time->reveal(),
      $event_dispatcher->reveal(),
      $logger_factory->reveal()
    );
  }

  /**
   * Returns sample order data.
   *
   * @return array
   *   An array of order data.
   */
  protected function getOrderData() {
    return [
      'AmazonOrderId' => '101',
      'OrderStatus' => 'completed',
      'ShippingAddress' => $this->getShippingAddress(),
      'BuyerName' => 'John Smith',
      'BuyerEmail' => 'john@example.com',
    ];
  }

  /**
   * Return a sample shipping address.
   *
   * @return array
   *   An array of address info.
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
   * Returns sample order item data.
   *
   * @return array
   *   An array of order item data.
   */
  protected function getOrderItemData() {
    return [
      [
        'ASIN' => 'BOOK-10101',
        'SellerSKU' => 'PROD-BOO-01',
        'OrderItemId' => '10',
        'Title' => 'A Really Good Book',
        'QuantityOrdered' => '2',
        'ItemPrice' => [
          'CurrencyCode' => 'USD',
          'Amount' => '20.00',
        ],
      ],
    ];
  }

}
