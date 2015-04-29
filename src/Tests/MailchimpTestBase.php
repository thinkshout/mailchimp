<?php

/**
 * @file
 * Contains Drupal\mailchimp\Tests\MailchimpTestBase.
 */

namespace Drupal\mailchimp\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\mailchimp_test\ConfigOverrider;

/**
 * Sets up MailChimp module tests.
 */
abstract class MailchimpTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Use a profile that contains required modules:
    $this->profile = $this->originalProfile;

    parent::setUp();

    \Drupal::configFactory()->addOverride(new ConfigOverrider());
  }

}
