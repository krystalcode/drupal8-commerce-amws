<?php

namespace Drupal\commerce_amws_feed\Plugin\Field\FieldFormatter;

use Drupal\commerce_amws_feed\Entity\FeedInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Formatter for feeds' processing status fields.
 *
 * @FieldFormatter(
 *   id = "commerce_amws_feed_processing_status",
 *   label = @Translation("Feed processing status"),
 *   field_types = {
 *     "list_integer"
 *   }
 * )
 */
class ProcessingStatusFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $this->statusText($item->value),
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
          ],
        ],
      ];
    }

    return $element;
  }

  /**
   * Returns the translated text representing the given processing status.
   *
   * @param int $status
   *   The processing status.
   *
   * @return string
   *   The status's textual representation.
   */
  protected function statusText($status) {
    $value = '';
    switch ($status) {
      case FeedInterface::PROCESSING_STATUS_AWAITING_ASYNCHRONOUS_REPLY:
        $value = $this->t('Awaiting asynchronous reply');
        break;

      case FeedInterface::PROCESSING_STATUS_CANCELLED:
        $value = $this->t('Cancelled');
        break;

      case FeedInterface::PROCESSING_STATUS_DONE:
        $value = $this->t('Done');
        break;

      case FeedInterface::PROCESSING_STATUS_IN_PROGRESS:
        $value = $this->t('In progress');
        break;

      case FeedInterface::PROCESSING_STATUS_IN_SAFETY_NET:
        $value = $this->t('In safety net');
        break;

      case FeedInterface::PROCESSING_STATUS_SUBMITTED:
        $value = $this->t('Submitted');
        break;

      case FeedInterface::PROCESSING_STATUS_UNCONFIRMED:
        $value = $this->t('Unconfirmed');
        break;
    }

    return $value;
  }

}
