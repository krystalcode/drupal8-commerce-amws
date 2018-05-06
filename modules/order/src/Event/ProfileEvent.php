<?php

namespace Drupal\commerce_amazon_mws_order\Event;

use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the profile event.
 *
 * @see \Drupal\commerce_amazon_mws_order\Event\ProfileEvents
 */
class ProfileEvent extends Event {

  /**
   * The profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $profile;

  /**
   * The Amazon MWS address.
   *
   * @var array
   */
  protected $amwsAddress;

  /**
   * The Amazon MWS buyer name.
   *
   * @var string
   */
  protected $amwsBuyerName;

  /**
   * Constructs a new ProfileEvent.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   * @param array $amws_address
   *   The Amazon MWS address.
   * @param string $amws_buyer_name
   *   The Amazon MWS buyer name.
   */
  public function __construct(
    ProfileInterface $profile,
    array $amws_address,
    $amws_buyer_name
  ) {
    $this->profile = $profile;
    $this->amwsAddress = $amws_address;
    $this->amwsBuyerName = $amws_buyer_name;
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
   * Gets the Amazon MWS address.
   *
   * @return array
   *   Gets the Amazon MWS address.
   */
  public function getAmwsAddress() {
    return $this->amwsAddress;
  }

  /**
   * Gets the Amazon MWS buyer name.
   *
   * @return string
   *   Gets the Amazon MWS buyer name.
   */
  public function getAmwsBuyerName() {
    return $this->amwsBuyerName;
  }

}
