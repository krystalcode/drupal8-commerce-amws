<?php

namespace Drupal\commerce_amws_feed;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the feed type entity type.
 *
 * Feed types are fixed and they are only added or updated programmatically; we
 * therefore only allow view access.
 *
 * @see \Drupal\node\Entity\FeedType
 */
class FeedTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(
    EntityInterface $entity,
    $operation,
    AccountInterface $account
  ) {
    if ($operation === 'view') {
      return parent::checkAccess($entity, $operation, $account);
    }

    return AccessResult::forbidden()->addCacheableDependency($entity);
  }

}
