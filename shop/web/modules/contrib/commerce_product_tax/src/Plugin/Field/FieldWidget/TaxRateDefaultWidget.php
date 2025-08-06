<?php

namespace Drupal\commerce_product_tax\Plugin\Field\FieldWidget;

use Drupal\commerce_price\Calculator;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\commerce_tax\Entity\TaxTypeInterface;
use Drupal\commerce_tax\Resolver\TaxRateResolverInterface;
use Drupal\commerce_tax\TaxZone;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'commerce_tax_rate_default' widget.
 *
 * @FieldWidget(
 *   id = "commerce_tax_rate_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "commerce_tax_rate"
 *   },
 *   multiple_values = TRUE
 * )
 */
class TaxRateDefaultWidget extends OptionsSelectWidget {

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (isset($this->options)) {
      return $this->options;
    }
    $tax_type = $this->getFieldSetting('tax_type');
    $this->options = [];

    // Add an empty option if the widget needs one.
    if ($empty_label = $this->getEmptyLabel()) {
      $this->options = ['_none' => $empty_label];
    }
    $no_applicable_tax = TaxRateResolverInterface::NO_APPLICABLE_TAX_RATE;
    // Avoid instantiating the same labels dozens of times.
    $no_applicable_tax_label = $this->t('No tax');

    if (!$tax_type) {
      return $this->options;
    }
    $tax_type = TaxType::load($tax_type);
    /** @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface $tax_type_plugin */
    $tax_type_plugin = $tax_type->getPlugin();

    foreach ($tax_type_plugin->getZones() as $zone) {
      if (!$this->isAllowed($tax_type, $zone)) {
        continue;
      }
      $label = (string) $zone->getLabel();
      $this->options[$label] = [$zone->getId() . '|' . $no_applicable_tax => $no_applicable_tax_label];

      foreach ($zone->getRates() as $rate) {
        $rate_label = $this->t('@label', ['@label' => $rate->getLabel()]);
        if ($percentage = $rate->getPercentage()) {
          $rate_label = $this->t('@label (@percentage%)', [
            '@label' => $rate->getLabel(),
            '@percentage' => Calculator::multiply($percentage->getNumber(), '100'),
          ]);
        }
        $this->options[$label][$zone->getId() . '|' . $rate->getId()] = $rate_label;
      }
    }

    return $this->options;
  }

  /**
   * Checks whether the given tax type and zone are allowed in the widget.
   *
   * @param \Drupal\commerce_tax\Entity\TaxTypeInterface $tax_type
   *   The tax type.
   * @param \Drupal\commerce_tax\TaxZone $zone
   *   The tax zone.
   *
   * @return bool
   *   TRUE if the given tax type and zone are allowed, FALSE otherwise.
   */
  protected function isAllowed(TaxTypeInterface $tax_type, TaxZone $zone) {
    if ($tax_type->getPluginId() == 'european_union_vat' && $zone->getId() == 'ic') {
      // The EU Intra-Community Supply zone is special and never displayed.
      return FALSE;
    }
    $allowed_zones = $this->getFieldSetting('allowed_zones');
    if (empty($allowed_zones)) {
      return TRUE;
    }

    return in_array($zone->getId(), $allowed_zones);
  }

}
