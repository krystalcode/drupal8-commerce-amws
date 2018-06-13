<?php

namespace Drupal\commerce_amws_feed;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for Amazon MWS feed submissions.
 */
class FeedListBuilder extends EntityListBuilder {

  /**
   * The storage for the feed type entities.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $feedTypeStorage;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatter $date_formatter,
    RendererInterface $renderer
  ) {
    parent::__construct(
      $entity_type,
      $entity_type_manager->getStorage($entity_type->id())
    );

    $this->feedTypeStorage = $entity_type_manager->getStorage($entity_type->getBundleEntityType());
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   *
   * Sort feeds by the day they were submitted.
   */
  public function load() {
    $entities = parent::load();
    uasort(
      $entities,
      function ($a, $b) {
        $a_submitted = $a->getSubmittedDate();
        $b_submitted = $b->getSubmittedDate();
        return ($a_submitted > $b_submitted) ? -1 : 1;
      }
    );
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = t('ID');
    $header['type'] = t('Type');
    $header['submitted_date'] = t('Submitted');
    $header['changed'] = t('Updated');
    $header['processing_status'] = t('Processing status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_amws_feed\Entity\FeedInterface $feed */
    $feed = $entity;

    /** @var \Drupal\commerce_amws_feed\Entity\FeedTypeInterface $feed_type */
    $feed_type = $this->feedTypeStorage->load($feed->bundle());

    $submitted = $feed->getSubmittedDate();
    $changed = $feed->getChangedTime();
    $processing_status = $feed
      ->get('processing_status')
      ->view([
        'label' => 'hidden',
        'type' => 'commerce_amws_feed_processing_status',
      ]);

    $row['id'] = $feed->getSubmissionId();
    $row['type'] = $feed_type->label();
    $row['submitted_date'] = $this->dateFormatter->format($submitted, 'short');
    $row['changed'] = $this->dateFormatter->format($changed, 'short');
    $row['processing_status'] = $this->renderer->render($processing_status);

    return $row + parent::buildRow($feed);
  }

}
