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
 * @group mailchimp
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
  function testGetList() {
    $list = mailchimp_get_list(DrupalMailchimpLists::TEST_LIST_A);

    $this->assertEqual($list['id'], DrupalMailchimpLists::TEST_LIST_A, 'List can be loaded.');
  }

  /**
   * Tests retrieval of a specific set of lists.
   */
  function testMultiListRetrieval() {
    $list_ids = array(
      DrupalMailchimpLists::TEST_LIST_A,
      DrupalMailchimpLists::TEST_LIST_B,
    );

    $lists = mailchimp_get_lists($list_ids);

    $this->assertEqual(count($lists), 2, 'Tested correct list count on retrieval.');

    foreach ($list_ids as $list_id) {
      $this->assertTrue((isset($lists[$list_id])), 'Tested valid list ID retrieved: ' . $list_id);
      unset($lists[$list_id]);
    }

    $this->assertEqual(count($lists), 0, 'Tested all lists retrieved.');
  }

  /**
   * Tests retrieval of mergevars for a set of lists.
   */
  function testGetMergevars() {
    $list_ids = array(
      DrupalMailchimpLists::TEST_LIST_A,
    );

    $lists = mailchimp_get_mergevars($list_ids);

    $this->assertTrue(is_array($lists), 'Tested valid lists array returned.');
    $this->assertTrue(!empty($lists), 'Tested valid lists returned.');

    foreach($lists as $list) {
      $this->assertTrue(in_array($list['id'], $list_ids), 'Tested valid list ID retrieved: ' . $list['id']);

      $this->assertTrue(is_array($list['merge_vars']), 'Tested list contains merge vars array.');

      foreach ($list['merge_vars'] as $merge_var) {
        $this->assertTrue(isset($merge_var['name']), 'Tested valid merge var.');
      }
    }
  }

}
