<?php

namespace Drupal\mailchimp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Mailchimp Webhook controller.
 */
class MailchimpWebhookController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function endpoint($hash) {
    $return = 0;

    if (!empty($_POST)) {
      $data = $_POST['data'];
      $type = $_POST['type'];
      switch ($type) {
        case 'unsubscribe':
        case 'profile':
        case 'cleaned':
          mailchimp_get_memberinfo($data['list_id'], $data['email'], TRUE);
          break;

        case 'upemail':
          mailchimp_cache_clear_member($data['list_id'], $data['old_email']);
          mailchimp_get_memberinfo($data['list_id'], $data['new_email'], TRUE);
          break;

        case 'campaign':
          mailchimp_cache_clear_list_activity($data['list_id']);
          mailchimp_cache_clear_campaign($data['id']);
          break;
      }

      // Allow other modules to act on a webhook.
      \Drupal::moduleHandler()->invokeAll('mailchimp_process_webhook', array($type, $data));

      // Log event.
      \Drupal::logger('mailchimp')->info('Webhook type {type} has been processed.', array(
        'type' => $type));

      $return = 1;
    }

    // TODO: There should be a better way of doing this.
    // D8 routing doesn't seem to allow us to return a single character
    // or string from a controller.
    echo $return;
    exit();
  }

}
