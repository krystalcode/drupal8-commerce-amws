<?php

namespace Drupal\commerce_amazon_mws_product;

use Drupal\commerce_amazon_mws_product\Entity\ProductType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for Amazon MWS products.
 */
class ProductListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['type'] = t('Type');
    $header['status'] = t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_amazon_mws_product\Entity\ProductInterface $product */
    $product = $entity;

    /** @var \Drupal\commerce_amazon_mws_product\Entity\ProductTypeInterface $product_type */
    $product_type = ProductType::load($product->bundle());
    $product_type = ProductType::load('default');

    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $product->label(),
    ] + $product->toUrl()->toRenderArray();
    $row['type'] = $product_type->label();
    $row['status'] = $product->isPublished() ? $this->t('Published') : $this->t('Unpublished');

    return $row + parent::buildRow($product);
  }

}
