<?php

namespace Drupal\mailchimp\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\mailchimp_test\MailchimpConfigOverrider;

$path = drupal_get_path('module', 'mailchimp');

include_once $path . "/lib/mailchimp-api-php/tests/src/Client.php";
include_once $path . "/lib/mailchimp-api-php/tests/src/Mailchimp.php";
include_once $path . "/lib/mailchimp-api-php/tests/src/Response.php";

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

    \Drupal::configFactory()->addOverride(new MailchimpConfigOverrider());
  }

}
