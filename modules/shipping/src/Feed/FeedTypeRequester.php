<?php

namespace Drupal\commerce_amws_shipping\Feed;

use Drupal\commerce_amws_feed\Configure\FeedTypeRequesterInterface;

/**
 * Requests feed types related to shipping.
 */
class FeedTypeRequester implements FeedTypeRequesterInterface {

  /**
   * {@inheritdoc}
   */
  public function request() {
    return [
      // The Order Fulfillment feed is used to send to Amazon MWS shipping
      // information about orders e.g. shipping services and tracking numbers.
      '_POST_ORDER_FULFILLMENT_DATA_',
    ];
  }

}
