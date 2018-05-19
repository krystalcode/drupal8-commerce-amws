<?php

namespace Drupal\commerce_amws_shipping\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Amazon MWS shipping settings form.
 */
class SettingsForm extends ConfigFormBase {

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
    return 'commerce_amws_shipping_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_amws_shipping.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_amws_shipping.settings');

    // Shipping method.
    $method_options = [];
    $methods = $this->shippingMethodStorage->loadMultiple();
    foreach ($methods as $method) {
      $method_options[$method->id()] = $method->label();
    }
    $form['shipping_method_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Shipping method'),
      '#description' => $this->t('The shipping method that will be used for imported Amazon MWS orders.'),
      '#default_value' => $config->get('shipping_method_id'),
      '#options' => $method_options,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('commerce_amws_shipping.settings')
      ->set(
        'shipping_method_id',
        $form_state->getValue('shipping_method_id')
      )
      ->save();

    parent::submitForm($form, $form_state);
  }

}
