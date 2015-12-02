<?php

/**
 * @file
 * Contains Drupal\mailchimp_lists\Tests\MailchimpListsTestBase.
 */

namespace Drupal\mailchimp_lists\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\mailchimp_lists_test\ConfigOverrider;

/**
 * Sets up MailChimp Lists module tests.
 */
abstract class MailchimpListsTestBase extends WebTestBase {

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
