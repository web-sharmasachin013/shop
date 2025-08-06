<?php

namespace Drupal\Tests\commerce_product_tax\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_tax\Resolver\TaxRateResolverInterface;

/**
 * Tests the tax rate constraint validator.
 *
 * @group commerce_product_tax
 */
class TaxRateConstraintValidatorTest extends ProductTaxKernelTestBase {

  /**
   * Tests the validator.
   */
  public function testValidator() {
    $variation = ProductVariation::create([
      'type' => 'default',
      'status' => 1,
      'sku' => 'foo',
      'title' => $this->randomString(),
      'price' => new Price('12.00', 'USD'),
      'field_tax_rate' => ['fr|reduced', 'fr|standard'],
    ]);
    $constraints = $variation->validate();
    $this->assertCount(1, $constraints);
    $this->assertEquals(sprintf('<em class="placeholder">%s</em>: cannot select more than one tax rate per zone.', $this->field->getLabel()), (string) $constraints->get(0)->getMessage());

    $variation->set('field_tax_rate', ['fr|reduce']);
    $constraints = $variation->validate();
    $this->assertCount(1, $constraints);
    $this->assertEquals(sprintf('<em class="placeholder">%s</em>: the selected tax rate <em class="placeholder">%s</em> is not valid.', $this->field->getLabel(), 'reduce'), (string) $constraints->get(0)->getMessage());

    $variation->set('field_tax_rate', ['it|reduced']);
    $constraints = $variation->validate();
    $this->assertCount(1, $constraints);
    $this->assertEquals(sprintf('<em class="placeholder">%s</em>: the selected tax zone <em class="placeholder">%s</em> is not allowed.', $this->field->getLabel(), 'Italy'), (string) $constraints->get(0)->getMessage());

    $variation->set('field_tax_rate', ['fr|reduced', 'de|standard']);
    $constraints = $variation->validate();
    $this->assertCount(0, $constraints);

    $variation->set('field_tax_rate', ['fr|' . TaxRateResolverInterface::NO_APPLICABLE_TAX_RATE]);
    $constraints = $variation->validate();
    $this->assertCount(0, $constraints);
  }

}
