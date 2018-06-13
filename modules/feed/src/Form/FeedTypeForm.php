<?php

namespace Drupal\commerce_amws_feed\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the Amazon MWS feed type add/edit form.
 *
 * Access to the UI for adding/editing feed types is blocked by the access
 * handler; the form still needs to exist though as the corresponding routes
 * are created by default by core.
 */
class FeedTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\commerce_amws_feed\Entity\FeedTypeInterface $feed_type */
    $feed_type = $this->entity;

    // Human label, machine name and description.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $feed_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $feed_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_amws_feed\Entity\FeedType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $feed_type->getDescription(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();

    drupal_set_message($this->t('Saved the %label feed type.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirect('entity.commerce_amws_feed_type.collection');
  }

}
