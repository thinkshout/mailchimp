<?php

/**
 * @file
 * Contains Drupal\mailchimp_campaign\Tests\MailchimpCampaignTest.
 */

namespace Drupal\mailchimp_campaign\Tests;

use Drupal\mailchimp_campaign_test\DrupalMailchimpCampaigns;

/**
 * Tests core campaign functionality.
 *
 * @group mailchimp
 */
class MailchimpCampaignTest extends MailchimpCampaignTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('mailchimp', 'mailchimp_campaign', 'mailchimp_test');

  /**
   * Tests retrieval of a specific campaign.
   */
  public function testGetCampaign() {
    $campaign_id = DrupalMailchimpCampaigns::TEST_CAMPAIGN_A;

    $campaign = mailchimp_get_campaign_data($campaign_id);

    $this->assertTrue(is_array($campaign), 'Tested retrieval of campaign data.');

    $this->assertEqual($campaign['id'], $campaign_id);
  }

}
