<?php

namespace Drupal\commerce_amws_order\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Amazon MWS order settings form.
 */
class SettingsForm extends ConfigFormBase {

  const BILLING_PROFILE_SOURCE_SHIPPING_INFORMATION = 'shipping_information';
  const BILLING_PROFILE_SOURCE_CUSTOM = 'custom';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_amws_order_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_amws_order.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @I Break down form building to more methods
   *    type     : task
   *    priority : low
   *    labels   : coding-standards
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_amws_order.settings');

    // General settings.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General import settings'),
      '#open' => TRUE,
    ];
    $form['general']['general_address_convert_states'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Convert US states to their 2-digit codes'),
      '#description' => $this->t('Normally, the state in USA shipping addresses is provided by Amazon MWS with its 2-digit code. However, in certain cases the full state name is given instead e.g. NEW MEXICO instead of NM. By default, addresses will be kept as they come through. When this option is check, full state names will be converted to their 2-digit codes before storing them on the website. Conversion of administrative areas in the USA only are supported at the moment.'),
      '#default_value' => $config->get('general.address_convert_states'),
    ];

    // Billing profile.
    $form['billing_profile'] = [
      '#type' => 'details',
      '#title' => $this->t('Billing profile'),
      '#open' => TRUE,
    ];
    $form['billing_profile']['billing_profile_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add a billing profile to the order'),
      '#default_value' => $config->get('billing_profile.status'),
    ];
    $form['billing_profile']['billing_profile_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Billing profile source'),
      '#description' => $this->t("Amazon MWS does not provide detailed billing information for its orders. You can choose to use the available shipping information for creating a billing profile, or manually enter billing information that will be used for the billing profiles of all orders. The latter might be useful if you want to add Amazon's details as the billing information for all orders for accountancy purposes."),
      '#default_value' => $config->get('billing_profile.source'),
      '#options' => [
        'shipping_information' => $this->t('Shipping information'),
        'custom' => $this->t('Custom information'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="billing_profile_status"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['billing_profile']['billing_profile_custom_address'] = [
      '#type' => 'address',
      '#default_value' => $config->get('billing_profile.custom_address'),
      '#states' => [
        'visible' => [
          ':input[name="billing_profile_source"]' => ['value' => self::BILLING_PROFILE_SOURCE_CUSTOM],
        ],
      ],
    ];

    // Cron settings.
    $form['cron'] = [
      '#type' => 'details',
      '#title' => $this->t('Cron'),
    ];
    $form['cron']['cron_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable importing orders during cron'),
      '#default_value' => $config->get('cron.status'),
    ];
    $form['cron']['cron_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit number of orders to import'),
      '#description' => $this->t('You may limit the number of orders that will be imported during each cron run. Leave empty to import all orders provided by Amazon MWS.'),
      '#default_value' => $config->get('cron.limit'),
      '#states' => [
        'visible' => [
          ':input[name="cron_status"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Purge settings.
    $form['purge'] = $this->buildPurgeForm($config);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Builds the form elements for the order purge settings.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The Commerce Amazon MWS module settings configuration object.
   *
   * @return array
   *   The render array containing the form elements.
   */
  public function buildPurgeForm(Config $config) {
    $form = [
      '#type' => 'details',
      '#title' => $this->t('Purge orders'),
    ];

    // Basic purge settings.
    $form['purge_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable purging orders'),
      '#description' => $this->t(
        'When purging orders is enabled, Amazon MWS orders and associated data
        will be deleted after a set period of time after the order was imported.
        This might be required in order to comply with Amazon data privacy and
        security requirements.'
      ),
      '#default_value' => $config->get('purge.status'),
    ];
    $form['purge_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Time period in seconds'),
      '#description' => $this->t(
        'The time period in seconds after the order was imported after which the
        orders will be deleted. Set to 0 to delete all orders.'
      ),
      '#default_value' => $config->get('purge.interval'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="purge_status"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Cron-related settings.
    $form['purge_cron_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable purging orders during cron'),
      '#description' => $this->t(
        'If purging orders during cron is not enabled, you will need to use the
        Drush command via alternative means e.g. manually or via Linux cron.
        Otherwise the orders will not be purged.'
      ),
      '#default_value' => $config->get('purge.cron.status'),
      '#states' => [
        'visible' => [
          ':input[name="purge_status"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['purge_cron_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit number of orders to purge'),
      '#description' => $this->t(
        'You may limit the number of orders that will be purged during each cron
        run. Set to 0 or leave empty to purge all orders that need purging.'
      ),
      '#default_value' => $config->get('purge.cron.limit'),
      '#states' => [
        'visible' => [
          ':input[name="purge_cron_status"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cron_limit = $form_state->getValue('cron_limit');
    if (!ctype_digit($cron_limit) && !empty($cron_limit)) {
      $form_state->setErrorByName(
        'cron_limit',
        $this->t('The limit of orders to import must be a positive integer number or empty.')
      );
    }

    $purge_cron_limit = $form_state->getValue('purge_cron_limit');
    if (!ctype_digit($purge_cron_limit) && !empty($purge_cron_limit)) {
      $form_state->setErrorByName(
        'purge_cron_limit',
        $this->t(
          'The limit of orders to purge must be a positive integer number or
          empty.'
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   *
   * @I Break down form submission to more methods
   *    type     : task
   *    priority : low
   *    labels   : coding-standards
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('commerce_amws_order.settings');

    // General import settings.
    $convert_states = $form_state->getValue('general_address_convert_states');
    $config->set('general.address_convert_states', $convert_states);

    // Billing profile settings.
    $profile_status = $form_state->getValue('billing_profile_status');

    // Unset source if billing profile is disabled.
    $profile_source = NULL;
    if ($profile_status) {
      $profile_source = $form_state->getValue('billing_profile_source');
    }

    // Custom billing information.
    $profile_custom_address = NULL;
    if ($profile_source === self::BILLING_PROFILE_SOURCE_CUSTOM) {
      $profile_custom_address = $form_state->getValue('billing_profile_custom_address');
    }

    $config->set('billing_profile.status', $profile_status)
      ->set('billing_profile.source', $profile_source)
      ->set('billing_profile.custom_address', $profile_custom_address);

    // Cron settings.
    $cron_status = $form_state->getValue('cron_status');

    // Unset number limit if cron is disabled.
    // Also, empty string or 0 might be submitted via the form which would still
    // mean to import all orders. Normalize all empty values to NULL.
    $cron_limit = $form_state->getValue('cron_limit');
    if (!$cron_status || empty($cron_limit)) {
      $cron_limit = NULL;
    }

    $config->set('cron.status', $cron_status)
      ->set('cron.limit', $cron_limit);

    // Purge settings.
    $this->submitPurgeForm($form_state, $config);

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Sets the configuration values related to order purging.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\Core\Config\Config $config
   *   The module configuration object.
   */
  public function submitPurgeForm(
    FormStateInterface $form_state,
    Config $config
  ) {
    // We convert the cron limit to 0 if no value is provided as NULL is
    // interpreted the same i.e. no limit. We do the same for the purge interval
    // as well so that it is stored as an integer in the configuration.
    $config
      ->set('purge.status', $form_state->getValue('purge_status'))
      ->set('purge.interval', (int) $form_state->getValue('purge_interval'))
      ->set('purge.cron.status', $form_state->getValue('purge_cron_status'))
      ->set('purge.cron.limit', (int) $form_state->getValue('purge_cron_limit'));
  }

}
