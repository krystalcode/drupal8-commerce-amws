<?php

namespace Drupal\Tests\commerce_amws_order\Unit;

use \AmazonOrder;
use \AmazonOrderItemList;

use Drupal\commerce_amws_order\OrderService;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;

use function simplexml_load_file;
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
   * An Amazon order object.
   *
   * @var \AmazonOrder
   */
  protected $amazonOrder;

  /**
   * The Amazon MWS order service.
   *
   * @var \Drupal\commerce_amws_order\OrderService
   */
  protected $amwsOrderService;

  /**
   * The Amazon order item list object.
   *
   * @var \AmazonOrderItemList
   */
  protected $amazonOrderItemList;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a new Order Service class.
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager = $entity_type_manager->reveal();
    $time = $this->prophesize(TimeInterface::class);
    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);

    $this->amwsOrderService = new OrderService(
      $entity_type_manager,
      $time->reveal(),
      $event_dispatcher->reveal(),
      $logger_factory->reveal()
    );

    // Parse the test order xml and order item xml data.
    $xml_order_data = simplexml_load_file(__DIR__ . '/MockData/fetchOrder.xml');
    $xml_order_data = $this->parseOrderXml($xml_order_data->GetOrderResult->Orders->Order);
    $xml_order_item_data = simplexml_load_file(__DIR__ . '/MockData/fetchOrderItems.xml');
    $xml_order_item_data = $this->parseOrderItemXml($xml_order_item_data->ListOrderItemsResult->OrderItems);

    // Create a mock Amazon order item class.
    $order_items_class = $this->prophesize(AmazonOrderItemList::class);
    /** @var \AmazonOrderItemList $order_items_class */
    $order_items_class->getItems()->willReturn($xml_order_item_data);
    $order_items_class = $order_items_class->reveal();

    // Create a mock Amazon order class.
    $this->amazonOrder = $this->prophesize(AmazonOrder::class);
    $this->amazonOrder->getData()->willReturn($xml_order_data);
    $this->amazonOrder->getAmazonOrderId()->willReturn('058-1233752-8214740');
    $this->amazonOrder->fetchItems()->willReturn($order_items_class);
    $this->amazonOrder = $this->amazonOrder->reveal();
  }

  /**
   * @covers ::createOrder
   */
  public function testCreateOrder() {
    $order = $this->amwsOrderService->createOrder($this->amazonOrder);
  }

  /**
   * Parses Amazon Order XML response into array.
   */
  protected function parseOrderXml($xml) {
    if (!$xml) {
      return FALSE;
    }
    $d = [];
    $d['AmazonOrderId'] = (string) $xml->AmazonOrderId;
    if (isset($xml->SellerOrderId)) {
      $d['SellerOrderId'] = (string) $xml->SellerOrderId;
    }
    $d['PurchaseDate'] = (string) $xml->PurchaseDate;
    $d['LastUpdateDate'] = (string) $xml->LastUpdateDate;
    $d['OrderStatus'] = (string) $xml->OrderStatus;
    if (isset($xml->FulfillmentChannel)) {
      $d['FulfillmentChannel'] = (string) $xml->FulfillmentChannel;
    }
    if (isset($xml->SalesChannel)) {
      $d['SalesChannel'] = (string) $xml->SalesChannel;
    }
    if (isset($xml->OrderChannel)) {
      $d['OrderChannel'] = (string) $xml->OrderChannel;
    }
    if (isset($xml->ShipServiceLevel)) {
      $d['ShipServiceLevel'] = (string) $xml->ShipServiceLevel;
    }
    if (isset($xml->ShippingAddress)) {
      $d['ShippingAddress'] = [];
      $d['ShippingAddress']['Name'] = (string) $xml->ShippingAddress->Name;
      $d['ShippingAddress']['AddressLine1'] = (string) $xml->ShippingAddress->AddressLine1;
      $d['ShippingAddress']['AddressLine2'] = (string) $xml->ShippingAddress->AddressLine2;
      $d['ShippingAddress']['AddressLine3'] = (string) $xml->ShippingAddress->AddressLine3;
      $d['ShippingAddress']['City'] = (string) $xml->ShippingAddress->City;
      $d['ShippingAddress']['County'] = (string) $xml->ShippingAddress->County;
      $d['ShippingAddress']['District'] = (string) $xml->ShippingAddress->District;
      $d['ShippingAddress']['StateOrRegion'] = (string) $xml->ShippingAddress->StateOrRegion;
      $d['ShippingAddress']['PostalCode'] = (string) $xml->ShippingAddress->PostalCode;
      $d['ShippingAddress']['CountryCode'] = (string) $xml->ShippingAddress->CountryCode;
      $d['ShippingAddress']['Phone'] = (string) $xml->ShippingAddress->Phone;
    }
    if (isset($xml->OrderTotal)) {
      $d['OrderTotal'] = [];
      $d['OrderTotal']['Amount'] = (string) $xml->OrderTotal->Amount;
      $d['OrderTotal']['CurrencyCode'] = (string) $xml->OrderTotal->CurrencyCode;
    }
    if (isset($xml->NumberOfItemsShipped)) {
      $d['NumberOfItemsShipped'] = (string) $xml->NumberOfItemsShipped;
    }
    if (isset($xml->NumberOfItemsUnshipped)) {
      $d['NumberOfItemsUnshipped'] = (string) $xml->NumberOfItemsUnshipped;
    }
    if (isset($xml->PaymentExecutionDetail)) {
      $d['PaymentExecutionDetail'] = [];

      $i = 0;
      foreach ($xml->PaymentExecutionDetail->children() as $x) {
        $d['PaymentExecutionDetail'][$i]['Amount'] = (string) $x->Payment->Amount;
        $d['PaymentExecutionDetail'][$i]['CurrencyCode'] = (string) $x->Payment->CurrencyCode;
        $d['PaymentExecutionDetail'][$i]['SubPaymentMethod'] = (string) $x->SubPaymentMethod;
        $i++;
      }
    }
    if (isset($xml->PaymentMethod)) {
      $d['PaymentMethod'] = (string) $xml->PaymentMethod;
    }
    if (isset($xml->PaymentMethodDetails)) {
      foreach ($xml->PaymentMethodDetails as $x) {
        $d['PaymentMethodDetails'][] = (string) $x->PaymentMethodDetail;
      }
    }
    if (isset($xml->IsReplacementOrder)) {
      $d['IsReplacementOrder'] = (string) $xml->IsReplacementOrder;
      $d['ReplacedOrderId'] = (string) $xml->ReplacedOrderId;
    }
    $d['MarketplaceId'] = (string) $xml->MarketplaceId;
    if (isset($xml->BuyerName)) {
      $d['BuyerName'] = (string) $xml->BuyerName;
    }
    if (isset($xml->BuyerEmail)) {
      $d['BuyerEmail'] = (string) $xml->BuyerEmail;
    }
    if (isset($xml->BuyerCounty)) {
      $d['BuyerCounty'] = (string) $xml->BuyerCounty;
    }
    if (isset($xml->BuyerTaxInfo)) {
      $d['BuyerTaxInfo'] = [];
      if (isset($xml->BuyerTaxInfo->CompanyLegalName)) {
        $d['BuyerTaxInfo']['CompanyLegalName'] = (string) $xml->BuyerTaxInfo->CompanyLegalName;
      }
      if (isset($xml->BuyerTaxInfo->TaxingRegion)) {
        $d['BuyerTaxInfo']['TaxingRegion'] = (string) $xml->BuyerTaxInfo->TaxingRegion;
      }
      if (isset($xml->BuyerTaxInfo->TaxClassifications)) {
        foreach ($xml->BuyerTaxInfo->TaxClassifications->children() as $x) {
          $temp = [];
          $temp['Name'] = (string) $x->Name;
          $temp['Value'] = (string) $x->Value;
          $d['BuyerTaxInfo']['TaxClassifications'][] = $temp;
        }
      }
    }
    if (isset($xml->ShipmentServiceLevelCategory)) {
      $d['ShipmentServiceLevelCategory'] = (string) $xml->ShipmentServiceLevelCategory;
    }
    if (isset($xml->CbaDisplayableShippingLabel)) {
      $d['CbaDisplayableShippingLabel'] = (string) $xml->CbaDisplayableShippingLabel;
    }
    if (isset($xml->ShippedByAmazonTFM)) {
      $d['ShippedByAmazonTFM'] = (string) $xml->ShippedByAmazonTFM;
    }
    if (isset($xml->TFMShipmentStatus)) {
      $d['TFMShipmentStatus'] = (string) $xml->TFMShipmentStatus;
    }
    if (isset($xml->OrderType)) {
      $d['OrderType'] = (string) $xml->OrderType;
    }
    if (isset($xml->EarliestShipDate)) {
      $d['EarliestShipDate'] = (string) $xml->EarliestShipDate;
    }
    if (isset($xml->LatestShipDate)) {
      $d['LatestShipDate'] = (string) $xml->LatestShipDate;
    }
    if (isset($xml->EarliestDeliveryDate)) {
      $d['EarliestDeliveryDate'] = (string) $xml->EarliestDeliveryDate;
    }
    if (isset($xml->LatestDeliveryDate)) {
      $d['LatestDeliveryDate'] = (string) $xml->LatestDeliveryDate;
    }
    if (isset($xml->IsBusinessOrder)) {
      $d['IsBusinessOrder'] = (string) $xml->IsBusinessOrder;
    }
    if (isset($xml->PurchaseOrderNumber)) {
      $d['PurchaseOrderNumber'] = (string) $xml->PurchaseOrderNumber;
    }
    if (isset($xml->IsPrime)) {
      $d['IsPrime'] = (string) $xml->IsPrime;
    }
    if (isset($xml->IsPremiumOrder)) {
      $d['IsPremiumOrder'] = (string) $xml->IsPremiumOrder;
    }

    return $d;
  }

  /**
   * Parses Amazon Order Item XML response into array.
   */
  protected function parseOrderItemXml($xml) {
    if (!$xml) {
      return FALSE;
    }

    $index = 0;
    foreach ($xml->children() as $item) {
      $n = $index;

      $this->itemList[$n]['ASIN'] = (string) $item->ASIN;
      $this->itemList[$n]['SellerSKU'] = (string) $item->SellerSKU;
      $this->itemList[$n]['OrderItemId'] = (string) $item->OrderItemId;
      $this->itemList[$n]['Title'] = (string) $item->Title;
      $this->itemList[$n]['QuantityOrdered'] = (string) $item->QuantityOrdered;
      if (isset($item->QuantityShipped)) {
        $this->itemList[$n]['QuantityShipped'] = (string) $item->QuantityShipped;
      }
      if (isset($item->BuyerCustomizedInfo->CustomizedURL)) {
        $this->itemList[$n]['BuyerCustomizedInfo'] = (string) $item->BuyerCustomizedInfo->CustomizedURL;
      }
      if (isset($item->PointsGranted)) {
        $this->itemList[$n]['PointsGranted']['PointsNumber'] = (string) $item->PointsGranted->PointsNumber;
        $this->itemList[$n]['PointsGranted']['Amount'] = (string) $item->PointsGranted->PointsMonetaryValue->Amount;
        $this->itemList[$n]['PointsGranted']['CurrencyCode'] = (string) $item->PointsGranted->PointsMonetaryValue->CurrencyCode;
      }
      if (isset($item->PriceDesignation)) {
        $this->itemList[$n]['PriceDesignation'] = (string) $item->PriceDesignation;
      }
      if (isset($item->GiftMessageText)) {
        $this->itemList[$n]['GiftMessageText'] = (string) $item->GiftMessageText;
      }
      if (isset($item->GiftWrapLevel)) {
        $this->itemList[$n]['GiftWrapLevel'] = (string) $item->GiftWrapLevel;
      }
      if (isset($item->ItemPrice)) {
        $this->itemList[$n]['ItemPrice']['Amount'] = (string) $item->ItemPrice->Amount;
        $this->itemList[$n]['ItemPrice']['CurrencyCode'] = (string) $item->ItemPrice->CurrencyCode;
      }
      if (isset($item->ShippingPrice)) {
        $this->itemList[$n]['ShippingPrice']['Amount'] = (string) $item->ShippingPrice->Amount;
        $this->itemList[$n]['ShippingPrice']['CurrencyCode'] = (string) $item->ShippingPrice->CurrencyCode;
      }
      if (isset($item->GiftWrapPrice)) {
        $this->itemList[$n]['GiftWrapPrice']['Amount'] = (string) $item->GiftWrapPrice->Amount;
        $this->itemList[$n]['GiftWrapPrice']['CurrencyCode'] = (string) $item->GiftWrapPrice->CurrencyCode;
      }
      if (isset($item->ItemTax)) {
        $this->itemList[$n]['ItemTax']['Amount'] = (string) $item->ItemTax->Amount;
        $this->itemList[$n]['ItemTax']['CurrencyCode'] = (string) $item->ItemTax->CurrencyCode;
      }
      if (isset($item->ShippingTax)) {
        $this->itemList[$n]['ShippingTax']['Amount'] = (string) $item->ShippingTax->Amount;
        $this->itemList[$n]['ShippingTax']['CurrencyCode'] = (string) $item->ShippingTax->CurrencyCode;
      }
      if (isset($item->GiftWrapTax)) {
        $this->itemList[$n]['GiftWrapTax']['Amount'] = (string) $item->GiftWrapTax->Amount;
        $this->itemList[$n]['GiftWrapTax']['CurrencyCode'] = (string) $item->GiftWrapTax->CurrencyCode;
      }
      if (isset($item->ShippingDiscount)) {
        $this->itemList[$n]['ShippingDiscount']['Amount'] = (string) $item->ShippingDiscount->Amount;
        $this->itemList[$n]['ShippingDiscount']['CurrencyCode'] = (string) $item->ShippingDiscount->CurrencyCode;
      }
      if (isset($item->PromotionDiscount)) {
        $this->itemList[$n]['PromotionDiscount']['Amount'] = (string) $item->PromotionDiscount->Amount;
        $this->itemList[$n]['PromotionDiscount']['CurrencyCode'] = (string) $item->PromotionDiscount->CurrencyCode;
      }
      if (isset($item->CODFee)) {
        $this->itemList[$n]['CODFee']['Amount'] = (string) $item->CODFee->Amount;
        $this->itemList[$n]['CODFee']['CurrencyCode'] = (string) $item->CODFee->CurrencyCode;
      }
      if (isset($item->CODFeeDiscount)) {
        $this->itemList[$n]['CODFeeDiscount']['Amount'] = (string) $item->CODFeeDiscount->Amount;
        $this->itemList[$n]['CODFeeDiscount']['CurrencyCode'] = (string) $item->CODFeeDiscount->CurrencyCode;
      }
      if (isset($item->PromotionIds)) {
        $i = 0;
        foreach ($item->PromotionIds->children() as $x) {
          $this->itemList[$n]['PromotionIds'][$i] = (string) $x;
          $i++;
        }
      }
      if (isset($item->InvoiceData)) {
        if (isset($item->InvoiceData->InvoiceRequirement)) {
          $this->itemList[$n]['InvoiceData']['InvoiceRequirement'] = (string) $item->InvoiceData->InvoiceRequirement;
        }
        if (isset($item->InvoiceData->BuyerSelectedInvoiceCategory)) {
          $this->itemList[$n]['InvoiceData']['BuyerSelectedInvoiceCategory'] = (string) $item->InvoiceData->BuyerSelectedInvoiceCategory;
        }
        if (isset($item->InvoiceData->InvoiceTitle)) {
          $this->itemList[$n]['InvoiceData']['InvoiceTitle'] = (string) $item->InvoiceData->InvoiceTitle;
        }
        if (isset($item->InvoiceData->InvoiceInformation)) {
          $this->itemList[$n]['InvoiceData']['InvoiceInformation'] = (string) $item->InvoiceData->InvoiceInformation;
        }
      }
      if (isset($item->ConditionId)) {
        $this->itemList[$n]['ConditionId'] = (string) $item->ConditionId;
      }
      if (isset($item->ConditionSubtypeId)) {
        $this->itemList[$n]['ConditionSubtypeId'] = (string) $item->ConditionSubtypeId;
      }
      if (isset($item->ConditionNote)) {
        $this->itemList[$n]['ConditionNote'] = (string) $item->ConditionNote;
      }
      if (isset($item->ScheduledDeliveryStartDate)) {
        $this->itemList[$n]['ScheduledDeliveryStartDate'] = (string) $item->ScheduledDeliveryStartDate;
      }
      if (isset($item->ScheduledDeliveryEndDate)) {
        $this->itemList[$n]['ScheduledDeliveryEndDate'] = (string) $item->ScheduledDeliveryEndDate;
      }
      $index++;
    }

  }

}
