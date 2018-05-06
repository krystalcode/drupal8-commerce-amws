<?php

namespace Drupal\commerce_amazon_mws_order\Event;

/**
 * Defines the names of events related to Amazon MWS order profiles.
 */
final class ProfileEvents {

  /**
   * Name of the event fired after creating a new profile.
   *
   * Fired before the profile is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_amazon_mws_order\Event\ProfileEvent
   */
  const PROFILE_CREATE = 'commerce_amazon_mws_order.profile.create';

}
