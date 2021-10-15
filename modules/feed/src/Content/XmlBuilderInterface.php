<?php

namespace Drupal\commerce_amws_feed\Content;

/**
 * Provides the interface for the XML feed content builder.
 *
 * It facilitates building the XML content that is included in a feed
 * submission.
 */
interface XmlBuilderInterface {

  /**
   * Builds the XML feed content for the given array content.
   *
   * @param array $content
   *   An associative array containing the feed content.
   *
   * @return string
   *   The XML content as a string.
   *
   * @throws \InvalidArgumentException
   *   When required elements, such as the header or the messages, are missing.
   */
  public function build(array $content);

}
