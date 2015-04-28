<?php

/**
 * @file
 * Contains Drupal\mailchimp_lists\Tests\MailchimpListsTest.
 */

namespace Drupal\mailchimp_lists\Tests;

use Drupal\mailchimp_lists_test\DrupalMailchimpLists;

/**
 * Tests core lists functionality.
 *
 * @group mailchimp_lists
 */
class MailchimpListsTest extends MailchimpListsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('mailchimp', 'mailchimp_lists', 'mailchimp_test');

  /**
   * Tests that a list can be loaded.
   */
  function testgetList() {
    $list = mailchimp_get_list(DrupalMailchimpLists::TEST_LIST_A);

    $this->assertEqual($list['id'], DrupalMailchimpLists::TEST_LIST_A, 'List can be loaded.');
  }

}
