<?php

namespace Drupal\commerce_amws\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Defines the interface for an Amazon MWS store.
 *
 * It holds per store configuration related to Amazon MWS integration. A store
 * is essentially a marketplace for a specific seller account.
 */
interface StoreInterface extends ConfigEntityInterface, EntityPublishedInterface {

  /**
   * Indicates that the store is disabled.
   *
   * Disabled stores will not have their orders or products synchronized.
   */
  const STATUS_UNPUBLISHED = 0;

  /**
   * Indicates that the store is enabled.
   *
   * Only enabled stores will not have their orders or products synchronized.
   */
  const STATUS_PUBLISHED = 1;

  /**
   * Sets the label.
   *
   * @param string $label
   *   The label of the store.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Gets the seller ID.
   *
   * @return string
   *   The seller ID.
   */
  public function getSellerId();

  /**
   * Sets the seller ID.
   *
   * @param string $seller_id
   *   The seller ID.
   *
   * @return $this
   */
  public function setSellerId($seller_id);

  /**
   * Gets the marketplace ID.
   *
   * @return string
   *   The marketplace ID.
   */
  public function getMarketplaceId();

  /**
   * Sets the marketplace ID.
   *
   * @param string $marketplace_id
   *   The marketplace ID.
   *
   * @return $this
   */
  public function setMarketplaceId($marketplace_id);

  /**
   * Gets the AWS access key ID.
   *
   * @return string
   *   The AWS access key ID.
   */
  public function getAwsAccessKeyId();

  /**
   * Sets the AWS access key ID.
   *
   * @param string $aws_access_key_id
   *   The AWS access key ID.
   *
   * @return $this
   */
  public function setAwsAccessKeyId($aws_access_key_id);

  /**
   * Gets the secret key.
   *
   * @return string
   *   The secret key.
   */
  public function getSecretKey();

  /**
   * Sets the secret key.
   *
   * @param string $secret_key
   *   The secret key.
   *
   * @return $this
   */
  public function setSecretKey($secret_key);

  /**
   * Gets the MWS authentication token.
   *
   * @return string
   *   The MWS authentication token.
   */
  public function getMwsAuthToken();

  /**
   * Sets the MWS authentication toke.
   *
   * @param string $mws_auth_token
   *   The MWS authentication token.
   *
   * @return $this
   */
  public function setMwsAuthToken($mws_auth_token);

}
