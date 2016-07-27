<?php

namespace Drupal\mailchimp\Tests;

/**
 * Tests core API functionality.
 *
 * @group mailchimp
 */
class MailchimpAPITest extends MailchimpTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('mailchimp', 'mailchimp_test');

  /**
   * Tests that the test API has been loaded.
   */
  function testAPI() {
    $mailchimp_api = mailchimp_get_api_object();

    $this->assertNotNull($mailchimp_api);

    $this->assertEqual(get_class($mailchimp_api), 'Mailchimp\Tests\Mailchimp');
  }

}
