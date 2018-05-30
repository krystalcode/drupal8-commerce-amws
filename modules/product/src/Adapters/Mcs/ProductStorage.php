<?php

namespace Drupal\commerce_amws_product\Adapters\Mcs;

use Drupal\commerce_amws\Entity\StoreInterface as AmwsStoreInterface;
use Drupal\commerce_amws_product\Adapters\ProductStorageInterface as AmwsProductRemoteStorageInterface;
use MCS\MWSClient;
use Psr\Log\LoggerInterface;

/**
 * The product storage adapter for the `mcs\amazon-mws` library.
 */
class ProductStorageInterface implements AmwsProductRemoteStorageInterface {

  /**
   * The Amazon MWS client.
   *
   * @var \MCS\MWSClient
   */
  protected $client;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ProductStorage object.
   *
   * @param \Drupal\commerce_amws\Entity\StoreInterface $amws_store
   *   The Amazon MWS store that the products belong to.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    AmwsStoreInterface $amws_store,
    LoggerInterface $logger
  ) {
    $this->client = new MWSClient($this->prepareStoreConfig($amws_store));
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function export(array $amws_products) {

  }

  /**
   * Prepares the Amazon MWS configuration as expected by `amazon-mws`.
   *
   * @param \Drupal\commerce_amws\Entity\StoreInterface $amws_store
   *   The Amazon MWS store to which the products being exported belong to.
   *
   * @return array
   *   The configuration array.
   */
  protected function prepareStoreConfig(AmwsStoreInterface $amws_store) {
    return [
      'Marketplace_Id' => $amws_store->getMarketplaceId(),
      'Seller_Id' => $amws_store->getSellerId(),
      'Access_Key_ID' => $amws_store->getAwsAccessKeyId(),
      'Secret_Access_Key' => $amws_store->getSecretKey(),
      'MWSAuthToken' => $amws_store->getMwsAuthToken(),
    ];
  }

}
