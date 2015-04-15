<?php

/**
 * @file
 * Contains \Drupal\mailchimp_campaign\Controller\MailchimpCampaignController.
 */

namespace Drupal\mailchimp_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

use Drupal\Component\Utility\String;
use Drupal\mailchimp_campaign\Entity\MailchimpCampaign;

/**
 * MailChimp Campaign controller.
 */
class MailchimpCampaignController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function overview() {
    $content = array();

    $content['campaigns_table'] = array(
      '#type' => 'table',
      '#header' => array(t('Title'), t('Subject'), t('Status'), t('MailChimp List'), t('MailChimp Template'), t('Created'), t('Actions')),
      '#empty' => '',
    );

    $campaigns = mailchimp_campaign_load_multiple();
    $templates = mailchimp_campaign_list_templates();

    /* @var $campaign \Drupal\mailchimp_campaign\Entity\MailchimpCampaign */
    foreach ($campaigns as $campaign) {
      $campaign_id = $campaign->getMcCampaignId();

      $archive_url = Url::fromUri($campaign->mc_data['archive_url']);
      $campaign_url = Url::fromRoute('mailchimp_campaign.view', array('mailchimp_campaign' => $campaign_id));
      $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $campaign->list['web_id']);

      $actions = array(
        \Drupal::l(t('View Archive'), $archive_url),
        \Drupal::l(t('View'), $campaign_url),
      );

      $content['campaigns_table'][$campaign_id]['title'] = array(
        '#markup' => \Drupal::l($campaign->mc_data['title'], $campaign_url),
      );

      $content['campaigns_table'][$campaign_id]['subject'] = array(
        '#markup' => $campaign->mc_data['subject'],
      );

      $content['campaigns_table'][$campaign_id]['status'] = array(
        '#markup' => $campaign->mc_data['status'],
      );

      $content['campaigns_table'][$campaign_id]['list'] = array(
        '#markup' => \Drupal::l($campaign->list['name'], $list_url, array(
            'attributes' => array('target' => '_blank'),
          )),
      );

      $content['campaigns_table'][$campaign_id]['template'] = array(
        '#markup' => isset($templates[$campaign->mc_data['template_id']]) ? $templates[$campaign->mc_data['template_id']]['name'] : '',
      );

      $content['campaigns_table'][$campaign_id]['created'] = array(
        '#markup' => $campaign->mc_data['create_time'],
      );

      $content['campaigns_table'][$campaign_id]['actions'] = array(
        '#markup' => implode(' | ', $actions),
      );
    }

    return $content;
  }

  /**
   * View a MailChimp campaign
   *
   * @param MailchimpCampaign $mailchimp_campaign
   *   The MailChimp campaign to view.
   *
   * @return array
   *   Renderable array of page content.
   */
  public function view(MailchimpCampaign $mailchimp_campaign) {
    $view_builder = \Drupal::entityManager()->getViewBuilder('mailchimp_campaign');

    $content = $view_builder->view($mailchimp_campaign);

    return $content;
  }

}
