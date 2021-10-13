<?php

namespace Drupal\commerce_amws_feed\Configure;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The default feed type installer.
 */
class FeedTypeInstaller implements FeedTypeInstallerInterface {

  /**
   * The feed type requesters.
   *
   * @var \Drupal\commerce_amws_feed\Configure\FeedTypeRequesterInterface[]
   */
  protected $requesters;

  /**
   * The feed type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new FeedTypeInstaller object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_amws_feed_type');
  }

  /**
   * {@inheritdoc}
   */
  public function addRequester(FeedTypeRequesterInterface $requester) {
    $this->requesters[] = $requester;
  }

  /**
   * {@inheritdoc}
   */
  public function install() {
    $type_ids = array_unique(
      array_reduce(
        $this->requesters,
        function ($carry, $requester) {
          return array_merge(
            $carry,
            array_values($requester->request())
          );
        },
        []
      )
    );

    $this->validate($type_ids);
    $this->doInstall($this->filter($type_ids));
  }

  /**
   * Validates that the given feed type IDs are supported.
   *
   * @param array $type_ids
   *   An array containing the IDs of the feed types to validate.
   *
   * @throws \InvalidArgumentException
   *   When one or more of the feed types provided are invalid i.e. not one of
   *   the feed types defined by Amazon MWS.
   */
  protected function validate(array $type_ids) {
    if (!$type_ids) {
      return;
    }

    $invalid_type_ids = array_diff(
      $type_ids,
      array_keys($this->getFeedTypes())
    );
    if (!$invalid_type_ids) {
      return;
    }

    throw new \InvalidArgumentException(sprintf(
      'Invalid feed types requested: %s',
      implode(', ', $invalid_type_ids)
    ));
  }

  /**
   * Returns those of the given feed type IDs that are not installed.
   *
   * @param array $type_ids
   *   An array containing the IDs of the feed types to filter.
   *
   * @return array
   *   An array containing those of the given feed type IDs that are not
   *   installed.
   */
  protected function filter(array $type_ids) {
    if (!$type_ids) {
      return $type_ids;
    }

    return array_diff(
      $type_ids,
      array_keys($this->storage->loadMultiple())
    );
  }

  /**
   * Installs the feed types with the given IDs.
   *
   * Only feed type IDs that are not already installed must be provided
   * i.e. call the `filter` method before this one.
   *
   * @param array $type_ids
   *   An array containing the IDs of the feed types to install.
   */
  protected function doInstall(array $type_ids) {
    if (!$type_ids) {
      return;
    }

    $type_data = $this->getFeedTypes();
    foreach ($type_ids as $type_id) {
      $this->storage->save(
        $this->storage->create($type_data[$type_id])
      );
    }
  }

  /**
   * Returns information about all known Amazon MWS feed type.
   *
   * @return array
   *   An associative array with information about Amazon MWS feed types. Each
   *   array must contain the following elements.
   *   - id: (string, required) The unique identifier of the feed type.
   *   - label: (string, required) The human-friendly label of the feed type.
   *   - description: (string, required) The description of the feed type.
   *
   * @I Add all valid Amazon MWS feed types
   *    type     : bug
   *    priority : low
   *    labels   : feed
   */
  protected function getFeedTypes() {
    return [
      // Product and inventory feeds.
      '_POST_PRODUCT_DATA_' => [
        'id' => '_POST_PRODUCT_DATA_',
        'label' => 'Product',
        'description' => "The Product feed contains descriptive information about the products in your catalog. This information allows Amazon to build a record and assign a unique identifier known as an ASIN (Amazon Standard Item Number) to each product. This feed is always the first step in submitting products to Amazon because it establishes the mapping between the seller's unique identifier (SKU) and Amazon's unique identifier (ASIN).",
      ],
      '_POST_INVENTORY_AVAILABILITY_DATA_' => [
        'id' => '_POST_INVENTORY_AVAILABILITY_DATA_',
        'label' => 'Inventory',
        'description' => 'The Inventory feed allows you to update inventory quantities (stock levels) for your items.',
      ],
      '_POST_PRODUCT_OVERRIDES_DATA_' => [
        'id' => '_POST_PRODUCT_OVERRIDES_DATA_',
        'label' => 'Override',
        'description' => 'The Override feed allows you to set an exception to your account-level shipping settings for an individual product (SKU). This is sometimes used for heavy, oversized, or unusually-shaped items, for example, a kayak or an automotive bumper',
      ],
      '_POST_PRODUCT_PRICING_DATA_' => [
        'id' => '_POST_PRODUCT_PRICING_DATA_',
        'label' => 'Price',
        'description' => 'The Price feed allows you to set the current price and sale price (when applicable) for an item.',
      ],
      '_POST_PRODUCT_IMAGE_DATA_' => [
        'id' => '_POST_PRODUCT_IMAGE_DATA_',
        'label' => 'Product images',
        'description' => 'The Image feed allows you to upload various images for a product.',
      ],
      '_POST_PRODUCT_RELATIONSHIP_DATA_' => [
        'id' => '_POST_PRODUCT_RELATIONSHIP_DATA_',
        'label' => 'Relationships',
        'description' => 'The Relationship feed allows you to set up optional relationships between items in your catalog.',
      ],
      // Order feeds.
      '_POST_ORDER_FULFILLMENT_DATA_' => [
        'id' => '_POST_ORDER_FULFILLMENT_DATA_',
        'label' => 'Order fulfillment',
        'description' => "The Order Fulfillment feed allows your system to update Amazon's system with order fulfillment information. Amazon posts the information in the customer's Amazon account so the customer can check the shipment status.",
      ],
    ];
  }

}
