<?php

namespace Drupal\commerce_amws_product;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for Amazon MWS product storages.
 */
interface ProductStorageInterface extends ContentEntityStorageInterface {

  /**
   * Load products that are queued for submission to Amazon MWS.
   *
   * @param array $options
   *   An array of options that will determine which queued products to
   *   load. Supported options are:
   *   - store_id: The Amazon MWS store ID for which to load products.
   *   - limit: An integer number limiting the number of product to load.
   *
   * @return \Drupal\commerce_amws_product\Entity\ProductInterface[]
   *   The queued products.
   */
  public function loadQueued(array $options = []);

}
