<?php

namespace Drupal\commerce_product_tax\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;

/**
 * Represents a list of tax rate item field values.
 */
class TaxRateItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    $constraints[] = $this->getTypedDataManager()
      ->getValidationConstraintManager()
      ->create('TaxRate', [
        'singleTaxRatePerZoneMessage' => t('%name: cannot select more than one tax rate per zone.', ['%name' => $this->getFieldDefinition()->getLabel()]),
      ]);

    return $constraints;
  }

}
