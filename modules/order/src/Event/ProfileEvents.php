<?php

namespace Drupal\commerce_amws_order\Event;

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
   * @see \Drupal\commerce_amws_order\Event\ProfileEvent
   */
  const PROFILE_CREATE = 'commerce_amws_order.profile.create';

  /**
   * Name of the event fired after saving a new profile.
   *
   * Event subscribers can set the `saveProfile` flag to indicate that the
   * profile has changed and should be saved again after all subscribers for
   * this event have run.
   *
   * @Event
   *
   * @see \Drupal\commerce_amws_order\Event\ProfileEvent
   */
  const PROFILE_INSERT = 'commerce_amws_order.profile.insert';

}
