<?php

namespace Drupal\mailchimp_lists\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\mailchimp_lists_test\MailchimpListsConfigOverrider;

$path = drupal_get_path('module', 'mailchimp');

include_once $path . "/lib/mailchimp-api-php/tests/src/Client.php";
include_once $path . "/lib/mailchimp-api-php/tests/src/Mailchimp.php";
include_once $path . "/lib/mailchimp-api-php/tests/src/Response.php";
include $path . "/lib/mailchimp-api-php/tests/src/MailchimpLists.php";

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

    \Drupal::configFactory()->addOverride(new MailchimpListsConfigOverrider());
  }

}
