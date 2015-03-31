<?php

/**
 * @file
 * Contains \Drupal\mailchimp_lists\Controller\OverviewController.
 */

namespace Drupal\mailchimp_lists\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

use Drupal\Component\Utility\String;

/**
 * Mailchimp Lists overview controller.
 */
class OverviewController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $content = array();

    $lists_admin_url = Url::fromUri('https://admin.mailchimp.com/lists/');

    $lists_empty_message = t('You don\'t have any lists configured in your
      MailChimp account, (or you haven\'t configured your API key correctly on
      the Global Settings tab). Head over to !link and create some lists, then
      come back here and click "Refresh lists from MailChimp"',
      array('!link' => \Drupal::l(t('MailChimp'), $lists_admin_url)));

    $content['lists_table'] = array(
      '#type' => 'table',
      '#header' => array(t('Name'), t('Members'), t('Webhook Status'),),
      '#empty' => $lists_empty_message,
    );

    $mc_lists = mailchimp_get_lists();
    $webhook_url = mailchimp_webhook_url();

    foreach ($mc_lists as $mc_list) {
      $webhooks = mailchimp_webhook_get($mc_list['id']);
      $enabled = FALSE;
      if ($webhooks) {
        foreach ($webhooks as $webhook) {
          if ($webhook_url == $webhook['url']) {
            $enabled = TRUE;
            continue;
          }
        }
      }

      $enable_url = Url::fromRoute('mailchimp_lists.webhook.enable', array('list_id' => $mc_list['id']));
      $disable_url = Url::fromRoute('mailchimp_lists.webhook.disable', array('list_id' => $mc_list['id']));

      if ($enabled) {
        $webhook_status = "ENABLED (" . \Drupal::l(t('disable'), $disable_url) . ')';
      }
      else {
        $webhook_status = "disabled (" . \Drupal::l(t('enable'), $enable_url) . ')';
      }

      $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_list['web_id']);

      $content['lists_table'][$mc_list['id']]['name'] = array(
        '#markup' => \Drupal::l($mc_list['name'], $list_url),
      );
      $content['lists_table'][$mc_list['id']]['member_count'] = array(
        '#markup' => $mc_list['stats']['member_count'],
      );
      $content['lists_table'][$mc_list['id']]['web_id'] = array(
        '#markup' => $webhook_status,
      );
    }

    $refresh_url = Url::fromRoute('mailchimp_lists.refresh', array('destination' => 'admin/config/services/mailchimp/lists'));

    $content['refresh_link'] = array(
      '#markup' => \Drupal::l(t('Refresh lists from Mailchimp'), $refresh_url),
    );

    return $content;
  }

  /**
   * Refreshes lists from MailChimp.
   */
  public function refresh() {
    // TODO: Refresh lists from MailChimp.
  }

}
