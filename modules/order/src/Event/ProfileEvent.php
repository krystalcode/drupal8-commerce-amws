<?php

namespace Drupal\commerce_amws_order\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the profile event.
 *
 * @see \Drupal\commerce_amws_order\Event\ProfileEvents
 */
class ProfileEvent extends Event {

  /**
   * The profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $profile;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The Amazon MWS order.
   *
   * @var \AmazonOrder
   */
  protected $amwsOrder;

  /**
   * Tracks whether the profile has been changed.
   *
   * Provides a way to subscribers to indicate that the profile should be saved
   * after all event subscribers have run.
   *
   * @var bool
   */
  protected $saveProfile;

  /**
   * Constructs a new ProfileEvent.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \AmazonOrder $amws_order
   *   The Amazon MWS order.
   */
  public function __construct(
    ProfileInterface $profile,
    OrderInterface $order,
    \AmazonOrder $amws_order
  ) {
    $this->profile = $profile;
    $this->order = $order;
    $this->amwsOrder = $amws_order;
    $this->saveProfile = FALSE;
  }

  /**
   * Gets the profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   Gets the profile.
   */
  public function getProfile() {
    return $this->profile;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   Gets the order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Gets the Amazon MWS order.
   *
   * @return \AmazonOrder
   *   Gets the Amazon MWS order.
   */
  public function getAmwsOrder() {
    return $this->amwsOrder;
  }

  /**
   * Gets the save profile flag.
   *
   * @return bool
   *   Returns the flag indicating whether the profile should be saved after all
   *   subscribers have run.
   */
  public function getSaveProfile() {
    return $this->saveProfile;
  }

  /**
   * Sets the save profile flag.
   *
   * @param bool $save_profile
   *   The flag indicating whether the profile should be saved after all
   *   subscribers have run.
   */
  public function setSaveProfile($save_profile) {
    return $this->saveProfile = $save_profile;
  }

}
