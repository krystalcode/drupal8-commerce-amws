<?php

namespace Drupal\commerce_amazon_mws;

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
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}
