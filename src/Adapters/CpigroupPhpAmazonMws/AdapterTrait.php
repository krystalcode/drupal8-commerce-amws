<?php

namespace Drupal\commerce_amws\Adapters\CpigroupPhpAmazonMws;

use Drupal\commerce_amws_feed\Entity\FeedInterface;
use Drupal\commerce_amws\Entity\StoreInterface as AmwsStoreInterface;
use LSS\Array2XML;

/**
 * Trait providing functionality that is common between remote storage adapters.
 */
trait AdapterTrait {

  /**
   * Prepares the Amazon MWS configuration as expected by `php-amazon-mws`.
   *
   * @param \Drupal\commerce_amws\Entity\StoreInterface $amws_store
   *   The Amazon MWS store that the order list object will be created for.
   *
   * @return array
   *   The configuration array.
   */
  protected function prepareStoreConfig(AmwsStoreInterface $amws_store) {
    return [
      'stores' => [
        $amws_store->id() => [
          'merchantId' => $amws_store->getSellerId(),
          'marketplaceId' => $amws_store->getMarketplaceId(),
          'keyId' => $amws_store->getAwsAccessKeyId(),
          'secretKey' => $amws_store->getSecretKey(),
          'MWSAuthToken' => $amws_store->getMwsAuthToken(),
        ],
      ],
      // @I Make the API endpoint URL customizable per store
      'AMAZON_SERVICE_URL' => 'https://mws.amazonservices.com/',
      'logpath' => '',
      'logfunction' => '',
      'muteLog' => TRUE,
    ];
  }

}
