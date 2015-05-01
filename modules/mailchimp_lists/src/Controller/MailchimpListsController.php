<?php

/**
 * @file
 * Contains \Drupal\mailchimp_lists\Controller\MailchimpListsController.
 */

namespace Drupal\mailchimp_lists\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

use Drupal\Component\Utility\String;

/**
 * MailChimp Lists controller.
 */
class MailchimpListsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function overview() {
    $content = array();

    $lists_admin_url = Url::fromUri('https://admin.mailchimp.com/lists/', array('attributes' => array('target' => '_blank')));

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
    $total_webhook_actions = count(mailchimp_lists_default_webhook_actions());

    foreach ($mc_lists as $mc_list) {
      $enabled_webhook_actions = count(mailchimp_lists_enabled_webhook_actions($mc_list['id']));
      $webhook_url = Url::fromRoute('mailchimp_lists.webhook', array('list_id' => $mc_list['id']));

      $webhook_status = $enabled_webhook_actions . ' of ' . $total_webhook_actions . ' enabled (' . \Drupal::l(t('update'), $webhook_url) . ')';

      $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_list['web_id'], array('attributes' => array('target' => '_blank')));

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

}
