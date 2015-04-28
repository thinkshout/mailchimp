<?php

/**
 * @file
 * Contains Drupal\mailchimp\Tests\MailchimpTestBase.
 */

namespace Drupal\mailchimp\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\mailchimp_test\ConfigOverrider;

/**
 * Sets up MailChimp Campaign module tests.
 */
abstract class MailchimpTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('mailchimp', 'mailchimp_test');

  /**
   * Drupal configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Use a profile that contains required modules:
    $this->profile = $this->originalProfile;

    parent::setUp();

    \Drupal::configFactory()->addOverride(new ConfigOverrider());
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
  }

}
