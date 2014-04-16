<?php
/**
* @file
* A virtual MailChimp Campaigns API implementation for use in testing.
*/

class MailChimp_CampaignsTest extends Mailchimp_Campaigns {
  public function __construct(MailChimpTest $master) {
    parent::__construct($master);
  }

  /**
   * @see Mailchimp_Campaigns::getList()
   */
  public function getList($filters=array(), $start=0, $limit=25, $sort_field='create_time', $sort_dir='DESC') {

  }
}