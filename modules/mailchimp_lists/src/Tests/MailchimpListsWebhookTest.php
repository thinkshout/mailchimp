<?php

/**
 * @file
 * Contains Drupal\mailchimp_lists\Tests\MailchimpListsWebhookTest.
 */

namespace Drupal\mailchimp_lists\Tests;

use Drupal\mailchimp_lists_test\DrupalMailchimpLists;

/**
 * Tests list webhook functionality.
 *
 * @group mailchimp
 */
class MailchimpListsWebhookTest extends MailchimpListsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('mailchimp', 'mailchimp_lists', 'mailchimp_test');

  /**
   * Tests retrieval of webhooks for a list.
   */
  public function testGetWebhook() {
    $list_id = DrupalMailchimpLists::TEST_LIST_A;

    $webhooks = mailchimp_webhook_get($list_id);

    $this->assertFalse(empty($webhooks), 'Tested webhook returned.');

    if (is_array($webhooks)) {
      foreach ($webhooks as $webhook) {
        $this->assertTrue(isset($webhook['url']), 'Tested valid webhook.');
      }
    }
  }

  /**
   * Tests adding a webhook to a list.
   */
  public function testAddWebhook() {
    $list_id = DrupalMailchimpLists::TEST_LIST_A;
    $url = 'http://example.org/web-hook-new';
    $actions = array(
      'subscribe' => TRUE,
    );
    $sources = array(
      'user' => TRUE,
      'admin' => TRUE,
      'api' => TRUE,
    );

    $webhook_added = mailchimp_webhook_add($list_id, $url, $actions, $sources);

    $this->assertTrue($webhook_added, 'Tested webhook addition.');

    $found_webhook = FALSE;
    $webhooks = mailchimp_webhook_get($list_id);
    foreach ($webhooks as $webhook) {
      if ($webhook['url'] == $url) {
        $found_webhook = TRUE;
      }
    }

    $this->assertTrue($found_webhook, 'Tested retrieval of new webhook.');
  }

  /**
   * Tests deletion of a webhook.
   */
  public function testDeleteWebhook() {
    $list_id = DrupalMailchimpLists::TEST_LIST_A;
    $url = 'http://example.org/web-hook-new';
    $actions = array();
    $sources = array();

    mailchimp_webhook_add($list_id, $url, $actions, $sources);

    $webhook_deleted = mailchimp_webhook_delete($list_id, $url);

    $this->assertTrue($webhook_deleted, 'Tested webhook deletion.');

    $found_webhook = FALSE;
    $webhooks = mailchimp_webhook_get($list_id);
    foreach ($webhooks as $webhook) {
      if ($webhook['url'] == $url) {
        $found_webhook = TRUE;
      }
    }

    $this->assertFalse($found_webhook, 'Tested removal of webhook.');
  }

}
