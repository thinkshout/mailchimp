<?php

namespace Drupal\mailchimp_campaign\Tests;

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
    $campaign_id = '42694e9e57';

    $campaign = mailchimp_get_campaign_data($campaign_id);

    $this->assertTrue(is_object($campaign), 'Tested retrieval of campaign data.');

    $this->assertEqual($campaign->id, $campaign_id);
    $this->assertEqual($campaign->type, 'regular');
    $this->assertEqual($campaign->recipients->list_id, '57afe96172');
    $this->assertEqual($campaign->settings->subject_line, 'Test Campaign');
    $this->assertTrue($campaign->tracking->html_clicks);
    $this->assertFalse($campaign->tracking->text_clicks);
  }

}
