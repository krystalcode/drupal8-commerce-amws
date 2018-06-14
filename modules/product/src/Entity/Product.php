<?php

namespace Drupal\commerce_amws_product\Entity;

use Drupal\user\UserInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Amazon MWS product entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_amws_product",
 *   label = @Translation("Amazon MWS product"),
 *   label_collection = @Translation("Amazon MWS products"),
 *   label_singular = @Translation("Amazon MWS product"),
 *   label_plural = @Translation("Amazon MWS products"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Amazon MWS product",
 *     plural = "@count Amazon MWS products",
 *   ),
 *   bundle_label = @Translation("Product type"),
 *   handlers = {
 *     "storage" = "Drupal\commerce_amws_product\ProductStorage",
 *     "list_builder" = "Drupal\commerce_amws_product\ProductListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_amws_product\Form\ProductForm",
 *       "edit" = "Drupal\commerce_amws_product\Form\ProductForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_amws_product",
 *   permission_granularity = "bundle",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   base_table = "commerce_amws_product",
 *   data_table = "commerce_amws_product_field_data",
 *   entity_keys = {
 *     "id" = "amws_product_id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/amazon-mws/product/{commerce_amws_product}",
 *     "add-form" = "/admin/commerce/amazon-mws/product/add/{commerce_amws_product_type}",
 *     "edit-form" = "/admin/commerce/amazon-mws/product/{commerce_amws_product}/edit",
 *     "delete-form" = "/admin/commerce/amazon-mws/product/{commerce_amws_product}/delete",
 *     "collection" = "/admin/commerce/amazon-mws/products"
 *   },
 *   bundle_entity_type = "commerce_amws_product_type",
 *   field_ui_base_route = "entity.commerce_amws_product_type.edit_form",
 * )
 */
class Product extends ContentEntityBase implements ProductInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    return $this->getTranslatedReferencedEntities('amws_stores');
  }

  /**
   * {@inheritdoc}
   */
  public function setStores(array $amws_stores) {
    $this->set('amws_stores', $amws_stores);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreIds() {
    $amws_store_ids = [];
    foreach ($this->get('amws_stores') as $amws_store_item) {
      $amws_store_ids[] = $amws_store_item->target_id;
    }
    return $amws_store_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreIds(array $amws_store_ids) {
    $this->set('amws_stores', $amws_store_ids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Authoring fields.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The product author.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_amws_product\Entity\Product::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the product was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the product was last edited.'))
      ->setTranslatable(TRUE);

    // Synchronization fields.
    $fields['synced'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Synchronized'))
      ->setDescription(t('The time when the product was last synchronized with Amazon MWS.'))
      ->setTranslatable(TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('Synchronization state'))
      ->setDescription(t("The state of the product\'s current synchronization process."))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('workflow', ProductInterface::WORKFLOW_DEFAULT)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The product that this Amazon MWS product is tied to.
    $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product'))
      ->setDescription(t('The corresponding product.'))
      ->setSetting('target_type', 'commerce_product')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Title.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The product title.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // The Amazon MWS stores that this product belongs to.
    $fields['amws_stores'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Amazon MWS Stores'))
      ->setDescription(t('The Amazon MWS stores that the product belongs to.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_amws_store')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
