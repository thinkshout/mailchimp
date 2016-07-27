<?php

namespace Drupal\mailchimp_lists\Tests;

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
    $list_id = '57afe96172';

    $list = mailchimp_get_list($list_id);

    $this->assertEqual($list->id, $list_id);
    $this->assertEqual($list->name, 'Test List One');
  }

  /**
   * Tests retrieval of a specific set of lists.
   */
  function testMultiListRetrieval() {
    $list_ids = array(
      '57afe96172',
      'f4b7b26b2e',
    );

    $lists = mailchimp_get_lists($list_ids);

    $this->assertEqual(count($lists), 2, 'Tested correct list count on retrieval.');

    $this->assertEqual($lists[$list_ids[0]]->id, $list_ids[0]);
    $this->assertEqual($lists[$list_ids[0]]->name, 'Test List One');

    $this->assertEqual($lists[$list_ids[1]]->id, $list_ids[1]);
    $this->assertEqual($lists[$list_ids[1]]->name, 'Test List Two');
  }

  /**
   * Tests retrieval of mergevars for a set of lists.
   */
  function testGetMergevars() {
    $list_ids = array(
      '57afe96172',
    );

    $mergevars = mailchimp_get_mergevars($list_ids);

    $this->assertEqual(count($mergevars[$list_ids[0]]), 3, 'Tested correct mergevar count on retrieval.');

    $this->assertEqual($mergevars[$list_ids[0]][0]->tag, 'EMAIL');
    $this->assertEqual($mergevars[$list_ids[0]][1]->tag, 'FNAME');
    $this->assertEqual($mergevars[$list_ids[0]][2]->tag, 'LNAME');
  }

}
