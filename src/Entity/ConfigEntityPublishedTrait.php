<?php

namespace Drupal\commerce_amws\Entity;

/**
 * Provides a trait for published status for configuration entities.
 *
 * Provides functions implementing \Drupal\Core\Entity\EntityPublishedInterface.
 */
trait ConfigEntityPublishedTrait {

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    $key = $this->getEntityType()->getKey('published');
    return (bool) $this->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published = NULL) {
    if ($published !== NULL) {
      @trigger_error(
        'The $published parameter is deprecated since core version 8.3.x and will be removed in 9.0.0.',
        E_USER_DEPRECATED
      );
      $value = (bool) $published;
    }
    else {
      $value = TRUE;
    }

    $key = $this->getEntityType()->getKey('published');
    $this->set($key, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpublished() {
    $key = $this->getEntityType()->getKey('published');
    $this->set($key, FALSE);

    return $this;
  }

}
