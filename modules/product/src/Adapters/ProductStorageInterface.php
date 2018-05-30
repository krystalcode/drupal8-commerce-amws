<?php

namespace Drupal\commerce_amws_product\Adapters;

/**
 * Defines the interface for Amazon MWS product storage adapters.
 *
 * The order storage adapter is responsible for synchronizing products to and
 * from Amazon MWS.
 */
interface ProductStorageInterface {

  /**
   * Export products to Amazon MWS.
   *
   * @param \Drupal\commerce_amws_product\Entity\ProductInterface[] $amws_products
   *   The Amazon MWS products to export.
   */
  public function export(array $amws_products);

}
