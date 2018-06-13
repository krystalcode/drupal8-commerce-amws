<?php

namespace Drupal\commerce_amws_feed\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Amazon MWS feed submission type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_amws_feed_type",
 *   label = @Translation("Amazon MWS feed type"),
 *   label_collection = @Translation("Amazon MWS feed types"),
 *   label_singular = @Translation("Amazon MWS feed type"),
 *   label_plural = @Translation("Amazon MWS feed types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Amazon MWS feed type",
 *     plural = "@count Amazon MWS feed types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce_amws_feed\FeedTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\commerce_amws_feed\Form\FeedTypeForm",
 *       "edit" = "Drupal\commerce_amws_feed\Form\FeedTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\commerce_amws_feed\FeedTypeListBuilder",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_amws_feed_type",
 *   admin_permission = "administer commerce_amws_feed_type",
 *   bundle_of = "commerce_amws_feed",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/amazon-mws/feeds/types/add",
 *     "edit-form" = "/admin/commerce/config/amazon-mws/feeds/types/{commerce_amws_feed_type}/edit",
 *     "delete-form" = "/admin/commerce/config/amazon-mws/feeds/types/{commerce_amws_feed_type}/delete",
 *     "collection" = "/admin/commerce/config/amazon-mws/feeds/types"
 *   }
 * )
 */
class FeedType extends ConfigEntityBundleBase implements FeedTypeInterface {

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
   * The feed type description.
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
