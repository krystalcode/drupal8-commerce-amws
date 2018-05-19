<?php

namespace Drupal\commerce_amws;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the list builder for Amazon MWS stores.
 */
class StoreListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Store');
    $header['id'] = $this->t('Machine name');
    $header['status'] = $this->t('Enabled');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['status'] = $entity->isPublished() ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
