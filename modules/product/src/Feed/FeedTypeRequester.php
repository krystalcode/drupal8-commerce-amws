<?php

namespace Drupal\commerce_amws_product\Feed;

use Drupal\commerce_amws_feed\Configure\FeedTypeRequesterInterface;

/**
 * Requests feed types related to products.
 */
class FeedTypeRequester implements FeedTypeRequesterInterface {

  /**
   * {@inheritdoc}
   *
   * @I Review and document product-related feed type requests
   *    type     : task
   *    priority : low
   *    labels   : documentation, feed
   */
  public function request() {
    return [
      '_POST_PRODUCT_DATA_',
      '_POST_PRODUCT_RELATIONSHIP_DATA_',
      '_POST_PRODUCT_IMAGE_DATA_',
      '_POST_PRODUCT_PRICING_DATA_',
    ];
  }

}
