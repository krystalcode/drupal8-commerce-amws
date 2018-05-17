<?php

namespace Drupal\commerce_amazon_mws\Form;

use Drupal\commerce_amazon_mws\Entity\StoreInterface as AmwsStoreInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the Amazon MWS store add/edit form.
 */
class StoreForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\commerce_amazon_mws\Entity\StoreInterface $store */
    $store = $this->entity;

    // Human label, machine name and description.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $store->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $store->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_amazon_mws\Entity\Store::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $store->getDescription(),
    ];

    // Store credentials.
    $form['credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('Credentials'),
      '#open' => TRUE,
    ];
    $form['credentials']['seller_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Seller ID'),
      '#default_value' => $store->getSellerId(),
      '#required' => TRUE,
    ];
    $form['credentials']['marketplace_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Marketplace ID'),
      '#default_value' => $store->getMarketplaceId(),
      '#required' => TRUE,
    ];
    $form['credentials']['aws_access_key_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AWS Access Key ID'),
      '#default_value' => $store->getAwsAccessKeyId(),
      '#required' => TRUE,
    ];
    $form['credentials']['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#default_value' => $store->getSecretKey(),
      '#required' => TRUE,
    ];
    $form['credentials']['mws_auth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MWS Authentication Token'),
      '#default_value' => $store->getMwsAuthToken(),
      '#required' => TRUE,
    ];

    // Publication status.
    $status = AmwsStoreInterface::STATUS_PUBLISHED;
    if (!$store->isNew()) {
      $status = $store->isPublished();
    }
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Only enabled stores will have their orders and products synchronized with Amazon MWS.'),
      '#default_value' => $status,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();

    drupal_set_message($this->t('Saved the %label store.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirect('entity.commerce_amws_store.collection');
  }

}
