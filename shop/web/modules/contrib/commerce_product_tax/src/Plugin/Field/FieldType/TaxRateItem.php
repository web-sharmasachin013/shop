<?php

namespace Drupal\commerce_product_tax\Plugin\Field\FieldType;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\commerce_tax\Entity\TaxTypeInterface;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'commerce_tax_rate' field type.
 *
 * @FieldType(
 *   id = "commerce_tax_rate",
 *   label = @Translation("Tax rate"),
 *   category = "commerce",
 *   description = @Translation("Stores tax rates."),
 *   default_formatter = "string",
 *   default_widget = "commerce_tax_rate_default",
 *   list_class = "\Drupal\commerce_product_tax\Plugin\Field\FieldType\TaxRateItemList",
 * )
 */
class TaxRateItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'tax_type' => '',
      'allowed_zones' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Tax rate'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => '64',
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $field = $form_state->getFormObject()->getEntity();
    $element = [
      '#type' => 'container',
      '#id' => 'form-settings-wrapper',
    ];

    $tax_types = TaxType::loadMultiple();
    $tax_types = array_filter($tax_types, function (TaxTypeInterface $tax_type) {
      $tax_type_plugin = $tax_type->getPlugin();
      return ($tax_type_plugin instanceof LocalTaxTypeInterface);
    });

    $options = EntityHelper::extractLabels($tax_types);
    $default_value = $field->getSetting('tax_type') ?: key($options);
    $element['tax_type'] = [
      '#type' => 'select',
      '#title' => t('Tax type'),
      '#default_value' => $default_value,
      '#options' => $options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => 'form-settings-wrapper',
      ],
    ];
    if ($default_value) {
      $tax_type = $tax_types[$default_value];
      /** @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface $tax_type_plugin */
      $tax_type_plugin = $tax_type->getPlugin();
      $tax_type_plugin_id = $tax_type->getPluginId();
      $allowed_zones = [];
      foreach ($tax_type_plugin->getZones() as $zone) {
        if ($tax_type_plugin_id == 'european_union_vat' && $zone->getId() == 'ic') {
          // The EU Intra-Community Supply zone is special and never displayed.
          continue;
        }
        $allowed_zones[$zone->getId()] = $zone->getLabel();
      }
      $element['allowed_zones'] = [
        '#type' => 'select',
        '#title' => t('Allowed zones'),
        '#default_value' => $field->getSetting('allowed_zones'),
        '#options' => $allowed_zones,
        '#multiple' => TRUE,
      ];
    }

    return $element;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form['settings'];
  }

}
