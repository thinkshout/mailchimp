<?php

namespace Drupal\mailchimp_lists\Tests;

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
    $list_id = '57afe96172';
    $email = 'test@example.org';

    $member_info = mailchimp_get_memberinfo($list_id, $email);

    $this->assertEqual($member_info->id, md5($email));
    $this->assertEqual($member_info->email_address, $email);
  }

  /**
   * Tests the status of a member's subscription to a list.
   */
  public function testIsSubscribed() {
    $list_id = '57afe96172';
    $email = 'test@example.org';

    $subscribed = mailchimp_is_subscribed($list_id, $email);

    $this->assertTrue($subscribed, 'Tested user is subscribed to list.');
  }

  /**
   * Tests subscribing a member to a list.
   */
  public function testSubscribe() {
    $list_id = '57afe96172';
    $email = 'test@example.org';
    $interest_category_id = 'a1e9f4b7f6';
    $interest_ids = array(
      '9143cf3bd1',
      '3a2a927344',
    );
    $merge_vars = array(
      'EMAIL' => $email,
    );

    $interests = array();
    $interests[$interest_category_id] = array(
      $interest_ids[0] => 1,
      $interest_ids[1] => 0,
    );

    $member_info = mailchimp_subscribe($list_id, $email, $merge_vars, $interests);

    $this->assertEqual($member_info->id, md5($email), 'Tested new user subscription.');

    $this->assertEqual($member_info->merge_fields->EMAIL, $email);
    $this->assertEqual($member_info->interests->{$interest_ids[0]}, TRUE);
    $this->assertEqual($member_info->interests->{$interest_ids[1]}, FALSE);
  }

  /**
   * Tests updating a list member.
   */
  public function testUpdateMember() {
    $list_id = '57afe96172';
    $email = 'test@example.org';

    $updated = mailchimp_update_member($list_id, $email, NULL, NULL, 'text');

    $this->assertTrue($updated, 'Tested user update.');
  }

  /**
   * Tests unsubscribing a member from a list.
   */
  public function testUnsubscribe() {
    $list_id = '57afe96172';
    $email = 'test@example.org';

    $unsubscribed = mailchimp_unsubscribe($list_id, $email);

    $this->assertTrue($unsubscribed, 'Tested user unsubscription.');
  }
}
