<?php

namespace Drupal\commerce_amazon_mws_order\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Amazon MWS shipping settings form.
 */
class SettingsForm extends ConfigFormBase {

  const BILLING_PROFILE_SOURCE_SHIPPING_INFORMATION = 'shipping_information';
  const BILLING_PROFILE_SOURCE_CUSTOM = 'custom';

  /**
   * The shipping method storage.
   *
   * @var \Drupal\commerce_shipping\ShippingMethodStorageInterface
   */
  protected $shippingMethodStorage;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type_manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($config_factory);

    $this->shippingMethodStorage = $entity_type_manager->getStorage('commerce_shipping_method');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_amazon_mws_order_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_amazon_mws_order.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_amazon_mws_order.settings');

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
      '#default' => $config->get('cron.status'),
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

    return parent::buildForm($form, $form_state);
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('commerce_amazon_mws_order.settings');

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

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
