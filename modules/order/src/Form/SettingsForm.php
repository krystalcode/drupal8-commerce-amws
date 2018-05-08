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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $status = $form_state->getValue('billing_profile_status');

    // Unset source if billing profile is disabled.
    $source = NULL;
    if ($status) {
      $source = $form_state->getValue('billing_profile_source');
    }

    // Custom billing information.
    $custom_address = NULL;
    if ($source === self::BILLING_PROFILE_SOURCE_CUSTOM) {
      $custom_address = $form_state->getValue('billing_profile_custom_address');
    }

    $this->config('commerce_amazon_mws_order.settings')
      ->set('billing_profile.status', $status)
      ->set('billing_profile.source', $source)
      ->set('billing_profile.custom_address', $custom_address)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
