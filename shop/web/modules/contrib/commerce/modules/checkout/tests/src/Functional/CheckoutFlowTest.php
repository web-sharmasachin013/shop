<?php

namespace Drupal\Tests\commerce_checkout\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\commerce_checkout\Entity\CheckoutFlow;

/**
 * Tests the checkout flow UI.
 *
 * @group commerce
 */
class CheckoutFlowTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_checkout',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_checkout_flow',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a checkout flow.
   */
  public function testCheckoutFlowCreation() {
    $this->drupalGet('admin/commerce/config/checkout-flows');
    $this->getSession()->getPage()->clickLink('Add checkout flow');
    $edit = [
      'label' => 'Test checkout flow',
      'id' => 'test_checkout_flow',
      'plugin' => 'multistep_default',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($this->t('Saved the @name checkout flow.', ['@name' => $edit['label']]));

    $checkout_flow = CheckoutFlow::load('test_checkout_flow');
    $this->assertEquals('Test checkout flow', $checkout_flow->label());
    $this->assertEquals('multistep_default', $checkout_flow->getPluginId());
  }

  /**
   * Tests editing a checkout flow.
   */
  public function testCheckoutFlowEditing() {
    $this->createEntity('commerce_checkout_flow', [
      'label' => 'Test checkout flow',
      'id' => 'test_checkout_flow',
      'plugin' => 'multistep_default',
    ]);
    $this->drupalGet('admin/commerce/config/checkout-flows/manage/test_checkout_flow');

    $edit = [
      'label' => 'Name has changed',
      'configuration[panes][billing_information][weight]' => 1,
      'configuration[panes][contact_information][weight]' => 2,
      'configuration[panes][review][step_id]' => '_disabled',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($this->t('Saved the @name checkout flow.', ['@name' => $edit['label']]));

    $checkout_flow = CheckoutFlow::load('test_checkout_flow');
    $this->assertEquals('Name has changed', $checkout_flow->label());
    $this->assertEquals(1, $checkout_flow->get('configuration')['panes']['billing_information']['weight']);
    $this->assertEquals(2, $checkout_flow->get('configuration')['panes']['contact_information']['weight']);
    $this->assertEquals('_disabled', $checkout_flow->get('configuration')['panes']['review']['step']);
  }

  /**
   * Tests deleting a checkout flow via the admin.
   */
  public function testCheckoutFlowDeletion() {
    $checkout_flow = $this->createEntity('commerce_checkout_flow', [
      'label' => 'Test checkout flow',
      'id' => 'test_checkout_flow',
      'plugin' => 'multistep_default',
    ]);
    $this->drupalGet('admin/commerce/config/checkout-flows/manage/' . $checkout_flow->id() . '/delete');
    $this->assertSession()->pageTextContains($this->t("Are you sure you want to delete the checkout flow @flow?", ['@flow' => $checkout_flow->label()]));
    $this->assertSession()->pageTextContains($this->t('This action cannot be undone.'));
    $this->submitForm([], 'Delete');

    $checkout_flow_exists = (bool) CheckoutFlow::load($checkout_flow->id());
    $this->assertEmpty($checkout_flow_exists, 'The checkout flow has been deleted from the database.');
  }

  /**
   * Tests changing pane settings.
   */
  public function testCheckoutPaneSettings() {
    $this->createEntity('commerce_checkout_flow', [
      'label' => 'Test checkout flow',
      'id' => 'test_checkout_flow',
      'plugin' => 'multistep_default',
    ]);
    $this->drupalGet('admin/commerce/config/checkout-flows/manage/test_checkout_flow');
    $this->assertSession()->pageTextContains('Require double entry of email: No');
    $this->click('#edit-configuration-panes-contact-information-configuration-edit');

    // Enable required double entry.
    $edit = ['configuration[panes][contact_information][configuration][double_entry]' => 1];
    $this->submitForm($edit, 'Update');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains($this->t('Saved the @name checkout flow.', ['@name' => 'Test checkout flow']));

    // Go back to the edit page, and check that the text has changed.
    $this->drupalGet('admin/commerce/config/checkout-flows/manage/test_checkout_flow');
    $this->assertSession()->pageTextContains('Require double entry of email: Yes');

    // Double check by using the api to see if the changes are saved.
    $checkout_flow = CheckoutFlow::load('test_checkout_flow');
    $this->assertEquals(1, $checkout_flow->get('configuration')['panes']['contact_information']['double_entry']);
  }

  /**
   * Tests admin description of a pane.
   */
  public function testPaneAdminDescription() {
    $this->drupalGet('admin/commerce/config/checkout-flows/manage/default');
    // Make sure that the default admin description of the login pane is present.
    $this->assertSession()->elementExists('css', 'div .checkout-pane-overview .pane-configuration-admin-description');
    $this->assertSession()->elementContains('css', 'div .checkout-pane-overview .pane-configuration-admin-description', 'Presents customers with the choice to log in or proceed as a guest during checkout.');
    // Make sure that the default step description of the login pane is present.
    $this->assertSession()->elementExists('css', 'div .checkout-pane-overview .pane-configuration-default-step');
    $this->assertSession()->elementContains('css', 'div .checkout-pane-overview .pane-configuration-default-step__label', 'Default Step:');
    $this->assertSession()->elementContains('css', 'div .checkout-pane-overview .pane-configuration-default-step__title', 'Log in');
  }

  /**
   * Tests that removing a dependency doesn't remove the checkout flow.
   */
  public function testCheckoutFlowDependencies() {
    $this->container->get('module_installer')->install(['commerce_checkout_test']);
    $this->container = $this->kernel->rebuildContainer();
    $this->createEntity('commerce_checkout_flow', [
      'label' => 'Test checkout flow',
      'id' => 'test_checkout_flow',
      'plugin' => 'multistep_default',
    ]);
    $this->drupalGet('admin/commerce/config/checkout-flows/manage/test_checkout_flow');
    $edit = [
      'configuration[panes][checkout_test][weight]' => 1,
    ];
    $this->submitForm($edit, 'Save');
    $checkout_flow = CheckoutFlow::load('test_checkout_flow');
    $this->assertTrue(in_array('commerce_checkout_test', $checkout_flow->getDependencies()['module']));
    $this->assertEquals(1, $checkout_flow->get('configuration')['panes']['checkout_test']['weight']);

    $this->container->get('module_installer')->uninstall(['commerce_checkout_test']);
    $checkout_flow = $this->reloadEntity($checkout_flow);
    $this->assertNotNull($checkout_flow);
    $this->assertArrayNotHasKey('checkout_test', $checkout_flow->get('configuration')['panes']);
    $this->assertEmpty($checkout_flow->getDependencies());

    // Reinstall the module to ensure the dependency is re-added.
    $this->container->get('module_installer')->install(['commerce_checkout_test']);
    $this->container = $this->kernel->rebuildContainer();
    $this->drupalGet('admin/commerce/config/checkout-flows/manage/test_checkout_flow');
    $edit = [
      'configuration[panes][checkout_test][weight]' => 1,
    ];
    $this->submitForm($edit, 'Save');
    $checkout_flow = CheckoutFlow::load('test_checkout_flow');
    $this->assertTrue(in_array('commerce_checkout_test', $checkout_flow->getDependencies()['module']));
  }

}
