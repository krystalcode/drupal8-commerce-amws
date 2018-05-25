<?php

namespace Drupal\commerce_amws_product;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Drupal fields available for mapping with Amazon MWS API fields.
 */
class FieldMapping {

  /**
   * Returns the definitions for all fields available for mapping.
   *
   * All field names are prefixed with `amws`. Fields with the additional prefix
   * `dd_` correspond to Amazon MWS API's DescriptionData fields.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An associative array keyed with the fields' names and containing the
   *   corresponding field definitions.
   */
  public function bundleFieldDefinitions() {
    $fields = [];

    $fields['amws_sku'] = $this->stringBundleFieldDefinition('SKU');
    $fields['amws_standard_product_id_type'] = $this->stringBundleFieldDefinition('Standard product ID type');
    $fields['amws_standard_product_id_value'] = $this->stringBundleFieldDefinition('Standard product ID value');
    $fields['amws_launch_date'] = $this->timestampBundleFieldDefinition('Launch date');
    $fields['amws_release_date'] = $this->timestampBundleFieldDefinition('Release date');
    $fields['amws_condition'] = $this->listStringBundleFieldDefinition('Condition', ['New' => 'New']);
    $fields['amws_rebate_start_date'] = $this->timestampBundleFieldDefinition('Rebate start date');
    $fields['amws_rebate_end_date'] = $this->timestampBundleFieldDefinition('Rebate end date');
    $fields['amws_item_package_quantity'] = $this->decimalBundleFieldDefinition('Item package quantity');
    $fields['amws_number_of_items'] = $this->decimalBundleFieldDefinition('Number of items');
    $fields['amws_rebate_message'] = $this->stringBundleFieldDefinition('Rebate message');
    $fields['amws_rebate_name'] = $this->stringBundleFieldDefinition('Rebate name');
    $fields['amws_dd_title'] = $this->stringBundleFieldDefinition('Title');
    $fields['amws_dd_brand'] = $this->stringBundleFieldDefinition('Brand');
    $fields['amws_dd_designer'] = $this->stringBundleFieldDefinition('Designer');
    $fields['amws_dd_description'] = $this->textLongBundleFieldDefinition('Description');
    $fields['amws_dd_bullet_point'] = $this->textLongBundleFieldDefinition('Bullet points', '', TRUE);
    $fields['amws_dd_merchant_catalog_number'] = $this->stringBundleFieldDefinition('Merchant catalog number');
    $fields['amws_dd_msrp'] = $this->stringBundleFieldDefinition('MSRP');
    $fields['amws_dd_max_order_quantity'] = $this->decimalBundleFieldDefinition('Maximum order quantity');
    $fields['amws_dd_serial_number_required'] = $this->booleanBundleFieldDefinition('Serial number required?');
    $fields['amws_dd_prop65'] = $this->booleanBundleFieldDefinition('Proposition 65');
    $fields['amws_dd_legal_disclaimer'] = $this->textLongBundleFieldDefinition('Legal disclaimer');
    $fields['amws_dd_manufacturer'] = $this->stringBundleFieldDefinition('Manufacturer');
    $fields['amws_dd_mfr_part_number'] = $this->stringBundleFieldDefinition('Manufacturer part number');
    $fields['amws_dd_memorabilia'] = $this->booleanBundleFieldDefinition('Memorabilia');
    $fields['amws_dd_autographed'] = $this->booleanBundleFieldDefinition('Autographed');
    $fields['amws_dd_gift_wrap_available'] = $this->booleanBundleFieldDefinition('Gift wrapping available');
    $fields['amws_dd_gift_message_available'] = $this->booleanBundleFieldDefinition('Gift message available');
    $fields['amws_dd_discontinued_by_mfr'] = $this->booleanBundleFieldDefinition('Discontinued by the manufacturer');
    $fields['amws_dd_max_aggregate_quantity'] = $this->decimalBundleFieldDefinition('Maximum aggregate shipping quantity');

    return $fields;
  }

  /**
   * Creates a bundle field definition for a field of type `string`.
   *
   * @param string $label
   *   The field label.
   * @param string $description
   *   The field description.
   * @param bool $multiple
   *   Whether the field should support multiple values or not.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The created field definition.
   */
  protected function stringBundleFieldDefinition(
    $label,
    $description = '',
    $multiple = FALSE
  ) {
    $options = [
      'description' => $description,
      'multiple' => $multiple,
    ];
    return $this->baseBundleFieldDefinition(
      'string',
      'string_textfield',
      'string',
      $label,
      $options
    );
  }

  /**
   * Creates a bundle field definition for a field of type `text_long`.
   *
   * @param string $label
   *   The field label.
   * @param string $description
   *   The field description.
   * @param bool $multiple
   *   Whether the field should support multiple values or not.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The created field definition.
   */
  protected function textLongBundleFieldDefinition(
    $label,
    $description = '',
    $multiple = FALSE
  ) {
    $options = [
      'description' => $description,
      'multiple' => $multiple,
    ];
    return $this->baseBundleFieldDefinition(
      'text_long',
      'text_textarea',
      'text_default',
      $label,
      $options
    );
  }

  /**
   * Creates a bundle field definition for a field of type `boolean`.
   *
   * @param string $label
   *   The field label.
   * @param string $description
   *   The field description.
   * @param bool $multiple
   *   Whether the field should support multiple values or not.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The created field definition.
   */
  protected function booleanBundleFieldDefinition(
    $label,
    $description = '',
    $multiple = FALSE
  ) {
    $options = [
      'description' => $description,
      'multiple' => $multiple,
      'field_formatter_settings' => ['format' => 'yes-no'],
    ];
    return $this->baseBundleFieldDefinition(
      'boolean',
      'boolean_checkbox',
      'boolean',
      $label,
      $options
    );
  }

  /**
   * Creates a bundle field definition for a field of type `list_string`.
   *
   * @param string $label
   *   The field label.
   * @param array $allowed_values
   *   An associative array keyed by the allowed values' machine names and
   *   containing the allowed values' display names.
   * @param string $description
   *   The field description.
   * @param bool $multiple
   *   Whether the field should support multiple values or not.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The created field definition.
   */
  protected function listStringBundleFieldDefinition(
    $label,
    array $allowed_values,
    $description = '',
    $multiple = FALSE
  ) {
    $options = [
      'description' => $description,
      'multiple' => $multiple,
      'field_type_settings' => ['allowed_values' => $allowed_values],
    ];
    return $this->baseBundleFieldDefinition(
      'list_string',
      'options_select',
      'list_default',
      $label,
      $options
    );
  }

  /**
   * Creates a bundle field definition for a field of type `timestamp`.
   *
   * @param string $label
   *   The field label.
   * @param string $description
   *   The field description.
   * @param bool $multiple
   *   Whether the field should support multiple values or not.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The created field definition.
   */
  protected function timestampBundleFieldDefinition(
    $label,
    $description = '',
    $multiple = FALSE
  ) {
    $options = [
      'description' => $description,
      'multiple' => $multiple,
    ];
    return $this->baseBundleFieldDefinition(
      'timestamp',
      'datetime_timestamp',
      'timestamp',
      $label,
      $options
    );
  }

  /**
   * Creates a bundle field definition for a field of type `decimal`.
   *
   * @param string $label
   *   The field label.
   * @param string $description
   *   The field description.
   * @param bool $multiple
   *   Whether the field should support multiple values or not.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The created field definition.
   */
  protected function decimalBundleFieldDefinition(
    $label,
    $description = '',
    $multiple = FALSE
  ) {
    $options = [
      'description' => $description,
      'multiple' => $multiple,
    ];
    return $this->baseBundleFieldDefinition(
      'decimal',
      'number',
      'number_decimal',
      $label,
      $options
    );
  }

  /**
   * Creates a bundle field definition for the given arguments.
   *
   * @param string $field_type
   *   The type of the field to create a definition for.
   * @param string $field_widget
   *   The widget to use in forms.
   * @param string $field_formatter
   *   The formatter to use when rendering the field's value.
   * @param string $label
   *   The label for the field.
   * @param array $options
   *   An array of additional options. Supported options are:
   *   - description: A description for the field.
   *   - multiple: Whether the field should support multiple values or not.
   *   - field_type_settings: Settings specific to the field type.
   *   - field_widget_settings: Settings specific to the field widget.
   *   - field_formatter_settings: Settings specific to the field formatter.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The created field definition.
   */
  protected function baseBundleFieldDefinition(
    $field_type,
    $field_widget,
    $field_formatter,
    $label,
    array $options = []
  ) {
    $options = array_merge(
      [
        'description' => '',
        'multiple' => FALSE,
        'field_type_settings' => [],
        'field_widget_settings' => [],
        'field_formatter_settings' => [],
      ],
      $options
    );

    // Create field definition.
    $field_definition = BaseFieldDefinition::create($field_type)
      ->setLabel(t($label))
      ->setDescription(t($options['description']))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Field type settings.
    if ($options['field_type_settings']) {
      $field_definition->setSettings($options['field_type_settings']);
    }

    // Field widget.
    $field_widget_options = [
      'type' => $field_widget,
      'weight' => 0,
    ];
    if ($options['field_widget_options']) {
      $field_widget_options['settings'] = $options['field_widget_options'];
    }
    $field_definition->setDisplayOptions('form', $field_widget_options);

    // Field formatter.
    $field_formatter_options = [
      'type' => $field_formatter,
      'weight' => 0,
    ];
    if ($options['field_formatter_options']) {
      $field_formatter_options['settings'] = $options['field_formatter_options'];
    }
    $field_definition->setDisplayOptions('view', $field_formatter_options);

    // Cardinality.
    if ($options['multiple']) {
      $field_definition->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    }

    return $field_definition;
  }

}
