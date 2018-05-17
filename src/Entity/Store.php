<?php

namespace Drupal\commerce_amazon_mws\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the configuration entity for an Amazon MWS store.
 *
 * @ConfigEntityType(
 *   id = "commerce_amws_store",
 *   label = @Translation("Amazon MWS store"),
 *   label_collection = @Translation("Amazon MWS stores"),
 *   label_singular = @Translation("Amazon MWS store"),
 *   label_plural = @Translation("Amazon MWS stores"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Amazon MWS store",
 *     plural = "@count Amazon MWS stores",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_amazon_mws\StoreListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_amazon_mws\Form\StoreForm",
 *       "edit" = "Drupal\commerce_amazon_mws\Form\StoreForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_amazon_mws",
 *   config_prefix = "commerce_amws_store",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/amazon-mws/stores/add",
 *     "edit-form" = "/admin/commerce/config/amazon-mws/stores/{commerce_amws_store}/edit",
 *     "collection" = "/admin/commerce/config/amazon-mws/stores",
 *   }
 * )
 */
class Store extends ConfigEntityBase implements StoreInterface {

  /**
   * The configuration entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * A human-friendly label for the store.
   *
   * @var string
   */
  protected $label;

  /**
   * A description for the store.
   *
   * @var string
   */
  protected $description;

  /**
   * The seller ID.
   *
   * @var string
   */
  protected $seller_id;

  /**
   * The marketplace ID.
   *
   * @var string
   */
  protected $marketplace_id;

  /**
   * The AWS access key ID.
   *
   * @var string
   */
  protected $aws_access_key_id;

  /**
   * The secret key.
   *
   * @var string
   */
  protected $secret_key;

  /**
   * The MWS authentication token.
   *
   * @var string
   */
  protected $mws_auth_token;

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSellerId() {
    return $this->seller_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setSellerId($seller_id) {
    $this->seller_id = $seller_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMarketplaceId() {
    return $this->marketplace_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setMarketplaceId($marketplace_id) {
    $this->marketplace_id = $marketplace_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAwsAccessKeyId() {
    return $this->aws_access_key_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setAwsAccessKeyId($aws_access_key_id) {
    $this->aws_access_key_id = $aws_access_key_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecretKey() {
    return $this->secret_key;
  }

  /**
   * {@inheritdoc}
   */
  public function setSecretKey($secret_key) {
    $this->secret_key = $secret_key;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMwsAuthToken() {
    return $this->mws_auth_token;
  }

  /**
   * {@inheritdoc}
   */
  public function setMwsAuthToken($mws_auth_token) {
    $this->mws_auth_token = $mws_auth_token;
    return $this;
  }

}
