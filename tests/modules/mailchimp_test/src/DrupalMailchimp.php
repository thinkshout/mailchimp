<?php
/**
 * @file
 * Contains \Drupal\mailchimp_test\DrupalMailchimp.
 *
 * A virtual MailChimp API implementation for use in testing.
 */

namespace Drupal\mailchimp_test;

class DrupalMailchimp {

  /**
   * @var Mailchimp_ListsTest $lists
   */
  //public $lists;

  /**
   * @var Mailchimp CampaignsTest $campaigns
   */
  //public $campaigns;

  public function __construct($apikey = NULL, $opts = array()) {
    //$this->lists = new MailChimp_ListsTest($this);
    //$this->campaigns = new MailChimp_CampaignsTest($this);
  }

}
