<?php

namespace Drupal\commerce_amws_product\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Amazon MWS product settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_amws_product_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_amws_product.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_amws_product.settings');

    // Cron settings.
    $form['cron'] = [
      '#type' => 'details',
      '#title' => $this->t('Cron'),
    ];
    $form['cron']['cron_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable exporting products during cron'),
      '#default_value' => $config->get('cron.status'),
    ];
    $form['cron']['cron_store_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit number of stores to process'),
      '#description' => $this->t('You may limit the number of Amazon MWS stores for which products will be exported during each cron run. Leave empty to process all Amazon MWS stores.'),
      '#default_value' => $config->get('cron.store_limit'),
      '#states' => [
        'visible' => [
          ':input[name="cron_status"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['cron']['cron_product_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit number of products per store to export'),
      '#description' => $this->t('You may limit the number of Amazon MWS products that will be exported during each cron run. Leave empty to exported all Amazon MWS products.'),
      '#default_value' => $config->get('cron.product_limit'),
      '#states' => [
        'visible' => [
          ':input[name="cron_status"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validatePositiveInteger(
      'cron_store_limit',
      'The limit of stores to process must be a positive integer number or empty.',
      $form_state
    );
    $this->validatePositiveInteger(
      'cron_product_limit',
      'The limit of products per store to export must be a positive integer number or empty.',
      $form_state
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('commerce_amws_product.settings');

    // Cron settings.
    $cron_status = $form_state->getValue('cron_status');

    $config
      ->set('cron.status', $cron_status)
      ->set(
        'cron.store_limit',
        $this->prepareLimit('cron_store_limit', $form_state, $cron_status)
      )
      ->set(
        'cron.product_limit',
        $this->prepareLimit('cron_product_limit', $form_state, $cron_status)
      )
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Validates that the value for the given element is positive integer number.
   *
   * @param string $element_name
   *   The name of the given form element to validate.
   * @param string $error_message
   *   The error message to set in the form when validation fails.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function validatePositiveInteger(
    $element_name,
    $error_message,
    FormStateInterface $form_state
  ) {
    $value = $form_state->getValue($element_name);
    if (!ctype_digit($value) && !empty($value)) {
      $form_state->setErrorByName(
        $element_name,
        $this->t($error_message)
      );
    }
  }

  /**
   * Prepares the submitted limit value for saving in the config object.
   *
   * Makes sure that product synchronization during cron run is enabled and that
   * we do have a non-empty submitted value. Otherwise, it sets the value to
   * NULL so that nullify the value if synchronization is diabled and so that we
   * have a standardised value if another empty value is submitted e.g. 0.
   *
   * @param string $element_name
   *   The name of the form element that holds the limit.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param bool $cron_status
   *   The status of product synchronization on cron run.
   *
   * @return int|null
   *   The limit as an integer, or NULL if product synchronization is disabled
   *   or if an empty value was given.
   */
  protected function prepareLimit(
    $element_name,
    FormStateInterface $form_state,
    $cron_status
  ) {
    $value = $form_state->getValue($element_name);
    if (!$cron_status || empty($value)) {
      $value = NULL;
    }

    return $value;
  }

}
