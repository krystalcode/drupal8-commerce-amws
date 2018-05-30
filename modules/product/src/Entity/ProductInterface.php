<?php

namespace Drupal\commerce_amws_product\Entity;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the interface for Amazon MWS product entities.
 */
interface ProductInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * The default synchronization workflow for Amazon MWS product entities.
   */
  const WORKFLOW_DEFAULT = 'amws_product_default';

  /**
   * Gets the product title.
   *
   * @return string
   *   The product title
   */
  public function getTitle();

  /**
   * Sets the product title.
   *
   * @param string $title
   *   The product title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Gets the product creation timestamp.
   *
   * @return int
   *   The product creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the product creation timestamp.
   *
   * @param int $timestamp
   *   The product creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the stores.
   *
   * @return \Drupal\commerce_amws\Entity\StoreInterface[]
   *   The stores.
   */
  public function getStores();

  /**
   * Sets the stores.
   *
   * @param \Drupal\commerce_amws\Entity\StoreInterface[] $stores
   *   The stores.
   *
   * @return $this
   */
  public function setStores(array $stores);

  /**
   * Gets the store IDs.
   *
   * @return int[]
   *   The store IDs.
   */
  public function getStoreIds();

  /**
   * Sets the store IDs.
   *
   * @param int[] $store_ids
   *   The store IDs.
   *
   * @return $this
   */
  public function setStoreIds(array $store_ids);

}
