<?php

namespace Drupal\mailchimp_lists\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

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
      the Global Settings tab). Head over to @link and create some lists, then
      come back here and click "Refresh lists from MailChimp"',
      array('@link' => Link::fromTextAndUrl(t('MailChimp'), $lists_admin_url)->toString()));

    $content['lists_table'] = array(
      '#type' => 'table',
      '#header' => array(t('Name'), t('Members'), t('Webhook Status'),),
      '#empty' => $lists_empty_message,
    );

    $mc_lists = mailchimp_get_lists();
    $total_webhook_events = count(mailchimp_lists_default_webhook_events());

    foreach ($mc_lists as $mc_list) {
      $enabled_webhook_events = count(mailchimp_lists_enabled_webhook_events($mc_list->id));
      $webhook_url = Url::fromRoute('mailchimp_lists.webhook', array('list_id' => $mc_list->id));
      $webhook_link = Link::fromTextAndUrl('update', $webhook_url);

      $webhook_status = $enabled_webhook_events . ' of ' . $total_webhook_events . ' enabled (' .  $webhook_link->toString() . ')';

      $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_list->id, array('attributes' => array('target' => '_blank')));

      $content['lists_table'][$mc_list->id]['name'] = array(
        '#title' => $this->t($mc_list->name),
        '#type' => 'link',
        '#url' => $list_url
      );
      $content['lists_table'][$mc_list->id]['member_count'] = array(
        '#markup' => $mc_list->stats->member_count,
      );
      $content['lists_table'][$mc_list->id]['web_id'] = array(
        '#markup' => $webhook_status,
      );
    }

    $refresh_url = Url::fromRoute('mailchimp_lists.refresh', array('destination' => 'admin/config/services/mailchimp/lists'));

    $content['refresh_link'] = array(
      '#title' => 'Refresh lists from Mailchimp',
      '#type' => 'link',
      '#url' => $refresh_url
    );

    return $content;
  }

}
