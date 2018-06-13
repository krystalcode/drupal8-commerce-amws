<?php

namespace Drupal\commerce_amws_feed;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the feed entity type.
 *
 * @see \Drupal\node\Entity\Feed
 */
class FeedAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Feeds are always added or updated programmatically; we therefore only allow
   * view access.
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
