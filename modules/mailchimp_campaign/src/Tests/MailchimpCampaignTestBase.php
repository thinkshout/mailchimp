<?php

namespace Drupal\mailchimp_campaign\Tests;

use Drupal\mailchimp_campaign_test\MailchimpCampaignConfigOverrider;
use Drupal\simpletest\WebTestBase;

$path = drupal_get_path('module', 'mailchimp');

include_once $path . "/lib/mailchimp-api-php/tests/src/Client.php";
include_once $path . "/lib/mailchimp-api-php/tests/src/Mailchimp.php";
include_once $path . "/lib/mailchimp-api-php/tests/src/Response.php";
include $path . "/lib/mailchimp-api-php/tests/src/MailchimpCampaigns.php";


/**
 * Sets up MailChimp Campaign module tests.
 */
abstract class MailchimpCampaignTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Use a profile that contains required modules:
    $this->profile = $this->originalProfile;

    parent::setUp();

    \Drupal::configFactory()->addOverride(new MailchimpCampaignConfigOverrider());
  }

}
