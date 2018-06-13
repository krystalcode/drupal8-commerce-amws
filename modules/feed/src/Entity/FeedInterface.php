<?php

namespace Drupal\commerce_amws_feed\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the interface form Amazon MWS feed submission entities.
 */
interface FeedInterface extends ContentEntityInterface, EntityChangedInterface {

  const PROCESSING_STATUS_AWAITING_ASYNCHRONOUS_REPLY = 0;
  const PROCESSING_STATUS_CANCELLED = 1;
  const PROCESSING_STATUS_DONE = 2;
  const PROCESSING_STATUS_IN_PROGRESS = 3;
  const PROCESSING_STATUS_IN_SAFETY_NET = 4;
  const PROCESSING_STATUS_SUBMITTED = 5;
  const PROCESSING_STATUS_UNCONFIRMED = 6;

  /**
   * Gets the feed submission ID.
   *
   * @return string
   *   The feed submission ID.
   */
  public function getSubmissionId();

  /**
   * Sets the feed submission ID.
   *
   * @param string $submission_id
   *   The feed submission ID.
   *
   * @return $this
   */
  public function setSubmissionId($submission_id);

  /**
   * Gets the time when the the feed was submitted.
   *
   * @return int
   *   The feed submission timestamp.
   */
  public function getSubmittedDate();

  /**
   * Sets the timestamp when the feed was submitted.
   *
   * @param int $timestamp
   *   The feed submission timestamp.
   *
   * @return $this
   */
  public function setSubmittedDate($timestamp);

  /**
   * Gets the timestamp when feed processing started.
   *
   * @return int
   *   The feed processing start timestamp.
   */
  public function getStartedProcessingDate();

  /**
   * Sets the timestamp when feed processing started.
   *
   * @param int $timestamp
   *   The feed processing start timestamp.
   *
   * @return $this
   */
  public function setStartedProcessingDate($timestamp);

  /**
   * Gets the timestamp when feed processing completed.
   *
   * @return int
   *   The feed processing completion timestamp.
   */
  public function getCompletedProcessingDate();

  /**
   * Sets the timestamp when feed processing completed.
   *
   * @param int $timestamp
   *   The feed processing completion timestamp.
   *
   * @return $this
   */
  public function setCompletedProcessingDate($timestamp);

  /**
   * Gets the processing status.
   *
   * @return int
   *   The feed processing status.
   */
  public function getProcessingStatus();

  /**
   * Sets the processing status.
   *
   * @param int $status
   *   The feed processing status.
   *
   * @return $this
   */
  public function setProcessingStatus($status);

}
