<?php
/**
 * @file
 * A virtual MailChimp API implementation for use in testing.
 */

class MailChimpTest extends MailChimp {

  /**
   * @var Mailchimp_ListsTest $lists
   */
  public $lists;

  /**
   * @var Mailchimp CampaignsTest $campaigns
   */
  public $campaigns;

  public function __construct($apikey=null, $opts=array()) {
    parent::_construct($apikey, $opts);

    $this->lists = new MailChimp_ListsTest($this);
    $this->campaigns = new MailChimp_CampaignsTest($this);
  }

}