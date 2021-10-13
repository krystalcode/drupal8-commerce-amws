<?php

namespace Drupal\commerce_amws_feed\Configure;

/**
 * The interface for feed type requesters.
 *
 * Feed type requesters declare the feed types that are required by a module to
 * provide its functionality.
 *
 * Feed types are not like bundles on other entity types where applications can
 * define their own. Instead, feed types must be the ones that are defined by
 * Amazon MWS. They therefore need to be declared by modules that will make use
 * of them via API calls, for example.
 *
 * We don't want to install all supported feed types by default because they
 * may not be actually used. We therefore allow modules to declare the feed
 * types they require to be installed via requesters and we install only the
 * feeds that are requested by installed modules.
 *
 * There's still a chance that some feed types are not used even when the
 * requesting module is installed, for example, if a certain feature is not
 * enabled. To install feed types based on module configuration would make
 * the code more complicated for little benefit though. Installing feed types
 * when the requesting module is installed whether they will be used or not is
 * therefore an acceptable compromise.
 */
interface FeedTypeRequesterInterface {

  /**
   * Returns a list of feed types required by the requester.
   *
   * @return array
   *   An array containing the IDs of the feed types.
   */
  public function request();

}
