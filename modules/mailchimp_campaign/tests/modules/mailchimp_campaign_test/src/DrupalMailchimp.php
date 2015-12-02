<?php
/**
 * @file
 * Contains \Drupal\mailchimp_campaign_test\DrupalMailchimp.
 *
 * A virtual MailChimp API implementation for use in testing.
 */

namespace Drupal\mailchimp_campaign_test;

class DrupalMailchimp {

  /**
   * @var \Drupal\mailchimp_campaign_test\DrupalMailchimpCampaigns
   */
  public $campaigns;

  public function __construct($apikey = NULL, $opts = array()) {
    $this->campaigns = new \Drupal\mailchimp_campaign_test\DrupalMailchimpCampaigns($this);
  }

}
