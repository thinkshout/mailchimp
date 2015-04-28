<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists_test\DrupalMailchimp.
 *
 * A virtual MailChimp API implementation for use in testing.
 */

namespace Drupal\mailchimp_lists_test;

class DrupalMailchimp {

  /**
   * @var \Drupal\mailchimp_lists_test\DrupalMailchimpLists
   */
  public $lists;

  public function __construct($apikey = NULL, $opts = array()) {
    $this->lists = new \Drupal\mailchimp_lists_test\DrupalMailchimpLists($this);
  }

}
