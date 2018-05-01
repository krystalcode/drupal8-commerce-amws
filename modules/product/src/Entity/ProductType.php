<?php

namespace Drupal\commerce_amazon_mws_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Amazon MWS product type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_amws_product_type",
 *   label = @Translation("Amazon MWS product type"),
 *   label_collection = @Translation("Amazon MWS product types"),
 *   label_singular = @Translation("Amazon MWS product type"),
 *   label_plural = @Translation("Amazon MWS product types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Amazon MWS product type",
 *     plural = "@count Amazon MWSproduct types",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_amazon_mws_product\ProductTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_amazon_mws_product\Form\ProductTypeForm",
 *       "edit" = "Drupal\commerce_amazon_mws_product\Form\ProductTypeForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_amws_product_type",
 *   admin_permission = "administer commerce_amws_product_type",
 *   bundle_of = "commerce_amws_product",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/amazon-mws/product-types/add",
 *     "edit-form" = "/admin/commerce/config/amazon-mws/product-types/{commerce_amws_product_type}/edit",
 *     "delete-form" = "/admin/commerce/config/amazon-mws/product-types/{commerce_amws_product_type}/delete",
 *     "collection" = "/admin/commerce/config/amazon-mws/product-types"
 *   }
 * )
 */
class ProductType extends ConfigEntityBundleBase implements ProductTypeInterface {

  /**
   * The bundle ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The bundle label.
   *
   * @var string
   */
  protected $label;

  /**
   * The product type description.
   *
   * @var string
   */
  protected $description;

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

}
