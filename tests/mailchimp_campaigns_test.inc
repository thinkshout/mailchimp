<?php
/**
* @file
* A virtual MailChimp Campaigns API implementation for use in testing.
*/

class MailChimp_CampaignsTest extends Mailchimp_Campaigns {
  public function __construct(MailChimpTest $master) {
    parent::__construct($master);
  }
}