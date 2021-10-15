<?php

namespace Drupal\commerce_amws_feed\Content;

use Spatie\ArrayToXml\ArrayToXml;

/**
 * The default implementation of the XML feed content builder.
 */
class XmlBuilder implements XmlBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function build(array $content) {
    $this->validate($content);
    $content = $this->autoFill($content);

    return ArrayToXml::convert(
      $content,
      [
        'rootElementName' => 'AmazonEnvelope',
        '_attributes' => [
          'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
          'xsi:noNamespaceSchemaLocation' => 'amzn-envelope.xsd',
        ],
      ],
      TRUE,
      'UTF-8',
      '1.0'
    );
  }

  /**
   * Validates that all required elements are present in the content array.
   *
   * @param array $content
   *   An associative array containing the content of the feed.
   *
   * @throws \InvalidArgumentException
   *   When required elements, such as the header or the messages, are missing.
   */
  protected function validate(array $content) {
    $required = [
      'Header' => [
        'MerchantIdentifier' => [],
      ],
      'MessageType' => [],
      'Message' => [],
    ];

    foreach ($required as $parent => $children) {
      if (empty($content[$parent])) {
        throw new \InvalidArgumentException(sprintf(
          'The "%s" element is required.',
          $parent
        ));
      }
      foreach ($children as $children_parent => $children_child) {
        if (empty($content[$parent][$children_parent])) {
          throw new \InvalidArgumentException(sprintf(
            'The "%s" element is required.',
            "$parent/$children_parent"
          ));
        }
      }
    }
  }

  /**
   * Auto-fills elements that have a standard format.
   *
   * - It adds the `DocumentVersion` element to the header as it is always
   *   '1.01'.
   * - It adds the `MessageID` to all messages. For our use cases there's no
   *   benefit to provide custom message ID. We therefore automatically add an
   *   incremental ID for each message so that the caller doesn't have to create
   *   the message IDs.
   *
   * @param array $content
   *   An associative array containing the content of the feed.
   *
   * @return array
   *   The updated feed content array.
   */
  protected function autoFill(array $content) {
    $content['Header']['DocumentVersion'] = '1.01';

    $counter = 0;
    foreach ($content['Message'] as &$message) {
      $message['MessageID'] = (string) ++$counter;
    }

    return $content;
  }

}
