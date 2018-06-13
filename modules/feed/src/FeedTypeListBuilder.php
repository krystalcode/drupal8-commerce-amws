<?php

namespace Drupal\commerce_amws_feed;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the list builder for Amazon MWS feed types.
 */
class FeedTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Feed type');
    $header['id'] = $this->t('Machine name');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['description'] = $entity->getDescription();
    return $row + parent::buildRow($entity);
  }

}
