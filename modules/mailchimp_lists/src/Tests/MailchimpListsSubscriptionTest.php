<?php

/**
 * @file
 * Contains Drupal\mailchimp_lists\Tests\MailchimpListsSubscriptionTest.
 */

namespace Drupal\mailchimp_lists\Tests;

use Drupal\mailchimp_lists_test\DrupalMailchimpLists;

/**
 * Tests list subscription functionality.
 *
 * @group mailchimp
 */
class MailchimpListsSubscriptionTest extends MailchimpListsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('mailchimp', 'mailchimp_lists', 'mailchimp_test');

  /**
   * Tests retrieval of member info for a list and email address.
   */
  public function testGetMemberInfo() {
    $list_id = DrupalMailchimpLists::TEST_LIST_A;
    $email = 'user@example.org';
    $subscribed = mailchimp_subscribe($list_id, $email);

    $this->assertTrue($subscribed, 'Tested new user subscription.');

    $member_info = mailchimp_get_memberinfo($list_id, $email);

    $this->assertTrue(is_array($member_info), 'Tested valid member info array returned.');
    $this->assertEqual($member_info['email'], $email, 'Tested valid member email retrieved: ' . $member_info['email']);
  }

  /**
   * Tests the status of a member's subscription to a list.
   */
  public function testIsSubscribed() {
    $list_id = DrupalMailchimpLists::TEST_LIST_A;
    $email = 'user@example.org';
    $subscribed = mailchimp_subscribe($list_id, $email);

    $this->assertTrue($subscribed, 'Tested new user subscription.');

    $subscribed = mailchimp_is_subscribed($list_id, $email);
    $this->assertTrue($subscribed, 'Tested user is subscribed to list.');
  }

  /**
   * Tests subscribing a member to a list.
   */
  public function testSubscribe() {
    $list_id = DrupalMailchimpLists::TEST_LIST_A;
    $email = 'user@example.org';
    $subscribed = mailchimp_subscribe($list_id, $email);

    $this->assertTrue($subscribed, 'Tested new user subscription.');

    $list_id = DrupalMailchimpLists::TEST_LIST_INVALID;
    $subscribed = mailchimp_subscribe($list_id, $email);

    $this->assertFalse($subscribed, 'Tested new user subscription to invalid list.');
  }

  /**
   * Tests updating a list member.
   */
  public function testUpdateMember() {
    $list_id = DrupalMailchimpLists::TEST_LIST_A;
    $email = 'user@example.org';
    $subscribed = mailchimp_subscribe($list_id, $email);

    $this->assertTrue($subscribed, 'Tested new user subscription.');

    $updated = mailchimp_update_member($list_id, $email, NULL, 'text');

    $this->assertTrue($updated, 'Tested user update.');

    $member_info = mailchimp_get_memberinfo($list_id, $email);

    $this->assertEqual($member_info['email'], $email, 'Tested updated member email retrieved: ' . $member_info['email']);
  }

  /**
   * Tests unsubscribing a member from a list.
   */
  public function testUnsubscribe() {
    $list_id = DrupalMailchimpLists::TEST_LIST_A;
    $email = 'user@example.org';
    $subscribed = mailchimp_subscribe($list_id, $email);

    $this->assertTrue($subscribed, 'Tested new user subscription.');

    $unsubscribed = mailchimp_unsubscribe($list_id, $email);

    $this->assertTrue($unsubscribed, 'Tested user unsubscription.');

    $member_info = mailchimp_get_memberinfo($list_id, $email);

    $this->assertEqual($member_info['status'], 'unsubscribed', 'Tested updated subscription state.');

    // Reset subscription.
    mailchimp_subscribe($list_id, $email);

    // Delete member.
    mailchimp_unsubscribe($list_id, $email, TRUE);

    $member_info = mailchimp_get_memberinfo($list_id, $email);

    $this->assertTrue(empty($member_info), 'Tested user deletion on unsubscribe.');
  }

}
