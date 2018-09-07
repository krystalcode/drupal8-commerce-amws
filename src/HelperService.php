<?php

namespace Drupal\commerce_amws;

use Drupal\commerce_price\Price;

/**
 * Provides helper functions related to Amazon MWS.
 */
class HelperService {

  /**
   * Converts an Amazon MWS price array to a Drupal Price object.
   *
   * @param array $amws_price
   *   The Amazon MWS price array.
   *
   * @return \Drupal\commerce_price\Price
   *   The Drupal price object.
   */
  public function amwsPriceToDrupalPrice(array $amws_price) {
    return new Price($amws_price['Amount'], $amws_price['CurrencyCode']);
  }

}
