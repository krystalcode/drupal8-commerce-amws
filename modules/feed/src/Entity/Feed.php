<?php

namespace Drupal\commerce_amws_feed\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Amazon MWS feed submission entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_amws_feed",
 *   label = @Translation("Amazon MWS feed submission"),
 *   label_collection = @Translation("Amazon MWS feed submissions"),
 *   label_singular = @Translation("Amazon MWS feed submission"),
 *   label_plural = @Translation("Amazon MWS feed submissions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Amazon MWS feed submission",
 *     plural = "@count Amazon MWS feed submissions",
 *   ),
 *   bundle_label = @Translation("Feed submission type"),
 *   handlers = {
 *     "access" = "Drupal\commerce_amws_feed\FeedAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_amws_feed\FeedListBuilder",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\EntityForm",
 *       "edit" = "Drupal\Core\Entity\EntityForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "storage" = "Drupal\commerce_amws_feed\FeedStorage",
 *   },
 *   admin_permission = "administer commerce_amws_feed",
 *   permission_granularity = "bundle",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   base_table = "commerce_amws_feed",
 *   data_table = "commerce_amws_feed_field_data",
 *   entity_keys = {
 *     "id" = "feed_id",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/amazon-mws/feed/{commerce_amws_feed}",
 *     "add-form" = "/admin/commerce/amazon-mws/feed/add/{commerce_amws_feed_type}",
 *     "edit-form" = "/admin/commerce/amazon-mws/feed/{commerce_amws_feed}/edit",
 *     "delete-form" = "/admin/commerce/amazon-mws/feed/{commerce_amws_feed}/delete",
 *     "collection" = "/admin/commerce/amazon-mws/feeds"
 *   },
 *   bundle_entity_type = "commerce_amws_feed_type",
 *   field_ui_base_route = "entity.commerce_amws_feed_type.edit_form",
 * )
 */
class Feed extends ContentEntityBase implements FeedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getSubmissionId() {
    return $this->get('submission_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmissionId($submission_id) {
    $this->set('submission_id', $submission_id);
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
  public function getSubmittedDate() {
    return $this->get('submitted_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmittedDate($timestamp) {
    $this->set('submitted_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartedProcessingDate() {
    return $this->get('started_processing_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartedProcessingDate($timestamp) {
    $this->set('started_processing_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletedProcessingDate() {
    return $this->get('completed_processing_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompletedProcessingDate($timestamp) {
    $this->set('completed_processing_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessingStatus() {
    return $this->get('processing_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessingStatus($status) {
    $this->set('processing_status', $status);
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the feed submission was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the feed submission was last edited.'))
      ->setTranslatable(TRUE);

    // Submission fields.
    $fields['submission_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Submission ID'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['submitted_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Submitted'))
      ->setDescription(t('The time when the feed was submitted.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['started_processing_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Started processing'))
      ->setDescription(t('The time when the feed processing started.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_processing_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Completed processing'))
      ->setDescription(t('The time when the feed processing completed.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['processing_status'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Processing status'))
      ->setDescription(t('The processing status of the feed submission.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('allowed_values', [
        FeedInterface::PROCESSING_STATUS_AWAITING_ASYNCHRONOUS_REPLY => 'The request is being processed, but is waiting for external information before it can complete',
        FeedInterface::PROCESSING_STATUS_CANCELLED => 'The request has been aborted due to a fatal error',
        FeedInterface::PROCESSING_STATUS_DONE => 'The request has been processed',
        FeedInterface::PROCESSING_STATUS_IN_PROGRESS => 'The request is being processed',
        FeedInterface::PROCESSING_STATUS_IN_SAFETY_NET => 'The request is being processed, but the system has determined that there is a potential error with the feed',
        FeedInterface::PROCESSING_STATUS_SUBMITTED => 'The request has been received, but has not yet started processing',
        FeedInterface::PROCESSING_STATUS_UNCONFIRMED => 'The request is pending',
      ])
      ->setDisplayOptions('view', [
        'type' => 'commerce_amws_feed_processing_status',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['result'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Result'))
      ->setDescription(t('The raw result of the feed processing'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // The Amazon MWS stores that the feed belongs to.
    $fields['amws_stores'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Amazon MWS Stores'))
      ->setDescription(t('The Amazon MWS stores that the feed belongs to.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_amws_store')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
