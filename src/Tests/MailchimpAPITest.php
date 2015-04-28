<?php

/**
 * @file
 * Contains Drupal\mailchimp\Tests\MailchimpAPITest.
 */

namespace Drupal\mailchimp\Tests;

/**
 * Tests core API functionality.
 *
 * @group mailchimp
 */
class MailchimpAPITest extends MailchimpTestBase {

  public static $modules = array('mailchimp_test');

  /**
   * Tests that the test API has been loaded.
   */
  function testAPI() {
    $this->assertEqual(1, 1);

    $mailchimp_api = mailchimp_get_api_object();

    $this->assertNotNull($mailchimp_api);
  }

}
