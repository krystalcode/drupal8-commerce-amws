<?php

namespace Drupal\commerce_amazon_mws_product\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the Amazon MWS product type add/edit form.
 */
class ProductTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\commerce_amazon_mws_product\Entity\ProductTypeInterface $product_type */
    $product_type = $this->entity;

    // Human label, machine name and description.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $product_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $product_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_amazon_mws\Entity\Store::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $product_type->getDescription(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();

    if ($status === SAVED_NEW) {
      commerce_amws_product_add_stores_field($this->entity);
    }

    drupal_set_message($this->t('Saved the %label product type.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirect('entity.commerce_amws_product_type.collection');
  }

}
