<?php

namespace Drupal\mailchimp_lists\Tests;

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
    $list_id = '57afe96172';

    $webhooks = mailchimp_webhook_get($list_id);

    $this->assertEqual($webhooks[0]->list_id, $list_id);
    $this->assertEqual($webhooks[0]->id, '37b9c73a88');
    $this->assertEqual($webhooks[0]->url, 'http://example.org');
    $this->assertEqual($webhooks[0]->events->subscribe, TRUE);
    $this->assertEqual($webhooks[0]->events->unsubscribe, FALSE);
    $this->assertEqual($webhooks[0]->sources->user, TRUE);
    $this->assertEqual($webhooks[0]->sources->api, FALSE);
  }

  /**
   * Tests adding a webhook to a list.
   */
  public function testAddWebhook() {
    $list_id = '57afe96172';
    $url = 'http://example.org/web-hook-new';
    $events = array(
      'subscribe' => TRUE,
    );
    $sources = array(
      'user' => TRUE,
      'admin' => TRUE,
      'api' => FALSE,
    );

    $webhook_id = mailchimp_webhook_add($list_id, $url, $events, $sources);

    $this->assertEqual($webhook_id, 'ab24521a00');
  }

  /**
   * Tests deletion of a webhook.
   */
  public function testDeleteWebhook() {
    $list_id = '57afe96172';
    $url = 'http://example.org';

    $webhook_deleted = mailchimp_webhook_delete($list_id, $url);

    $this->assertTrue($webhook_deleted, 'Tested webhook deletion.');
  }

}
