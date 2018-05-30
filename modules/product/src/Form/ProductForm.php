<?php

namespace Drupal\commerce_amws_product\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the Amazon MWS product add/edit form.
 */
class ProductForm extends ContentEntityForm {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new ProductForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(
    EntityManagerInterface $entity_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    RequestStack $request_stack,
    DateFormatterInterface $date_formatter
  ) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);

    $this->request = $request_stack->getCurrentRequest();
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('request_stack'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_amws\Entity\ProductInterface $amws_product */
    $amws_product = $this->getEntity();

    // For new products, we may be given the corresponding product ID via
    // query parameters.
    if ($amws_product->isNew()) {
      $product_id = $this->request->query->get('commerce-product');
      if ($product_id) {
        $amws_product->set('product_id', $product_id);
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\commerce_amws\Entity\ProductInterface $amws_product */
    $amws_product = $this->getEntity();

    // Do not allow altering the product ID if it has been defined in the URL.
    if ($amws_product->isNew() && $amws_product->get('product_id')->target_id) {
      $form['product_id']['#disabled'] = TRUE;
    }

    // Following the way the form is build for commerce product entities so that
    // we provide a familiar user and developer experience.
    $form['#tree'] = TRUE;
    $form['#theme'] = ['commerce_amws_product_form'];
    $form['#attached']['library'][] = 'commerce_product/form';

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $amws_product->getChangedTime(),
    ];

    $last_saved = t('Not saved yet');
    if (!$amws_product->isNew()) {
      $last_saved = $this->dateFormatter->format(
        $amws_product->getChangedTime(),
        'short'
      );
    }
    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
      'published' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $amws_product->getState()->getLabel(),
        '#access' => !$amws_product->isNew(),
        '#attributes' => [
          'class' => ['entity-meta__title'],
        ],
      ],
      'changed' => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['entity-meta__last-saved', 'container-inline'],
        ],
        '#markup' => '<h4 class="label inline">' . $this->t('Last saved') . '</h4> ' . $last_saved,
      ],
      'author' => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['author', 'container-inline'],
        ],
        '#markup' => '<h4 class="label inline">' . $this->t('Author') . '</h4> ' . $amws_product->getOwner()->getDisplayName(),
      ],
    ];
    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form['visibility_settings'] = [
      '#type' => 'details',
      '#title' => t('Visibility settings'),
      '#open' => TRUE,
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['product-visibility-settings'],
      ],
      '#weight' => 30,
    ];
    $form['author'] = [
      '#type' => 'details',
      '#title' => t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['product-form-author'],
      ],
      '#attached' => [
        'library' => ['commerce_product/drupal.commerce_product'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];

    $form['uid']['#group'] = 'author';
    $form['status']['#group'] = 'visibility_settings';
    $form['stores']['#group'] = 'visibility_settings';
    $form['created']['#group'] = 'author';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_amws\Entity\ProductInterface $amws_product */
    $amws_product = $this->getEntity();
    $amws_product->save();

    $message = $this->t(
      'The Amazon MWS product %label has been successfully saved.',
      ['%label' => $amws_product->label()]
    );
    drupal_set_message($message);

    $form_state->setRedirect(
      'entity.commerce_amws_product.canonical',
      [
        'commerce_amws_product' => $amws_product->id(),
      ]
    );
  }

}
