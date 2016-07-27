<?php

namespace Drupal\mailchimp_campaign\Controller;

use Behat\Mink\Exception\Exception;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use \Drupal\mailchimp_campaign\Entity\MailchimpCampaign;

use Symfony\Component\HttpFoundation\JsonResponse;

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

      $archive_url = Url::fromUri($campaign->mc_data->archive_url);
      $campaign_url = Url::fromRoute('entity.mailchimp_campaign.view', array('mailchimp_campaign' => $campaign_id));
      $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $campaign->list->id, array('attributes' => array('target' => '_blank')));
      $send_url = Url::fromRoute('entity.mailchimp_campaign.send', array('mailchimp_campaign' => $campaign_id));

      if ($campaign->mc_data->status === "save") {
        $send_link = Link::fromTextAndUrl(t("Send"), $send_url)->toString();
      }
      // "Sent" campaigns were not being cached, so we needed to reload to get
      // the latest status.
      elseif ($campaign->mc_data->status === "sending") {
        $campaigns = mailchimp_campaign_load_multiple(array($campaign_id), TRUE);
        $campaign = $campaigns[$campaign_id];
        $send_link = t("Sent");
      }
      else {
        $send_link = t("Sent");
      }


      $actions = array(
        Link::fromTextAndUrl(('View Archive'), $archive_url)->toString(),
        Link::fromTextAndUrl(('View'), $campaign_url)->toString(),
        $send_link,
      );

      $content['campaigns_table'][$campaign_id]['title'] = array(
        '#markup' => Link::fromTextAndUrl($campaign->mc_data->settings->title, $campaign_url)->toString(),
      );

      $content['campaigns_table'][$campaign_id]['subject'] = array(
        '#markup' => $campaign->mc_data->settings->subject_line,
      );

      $content['campaigns_table'][$campaign_id]['status'] = array(
        '#markup' => $campaign->mc_data->status,
      );

      $content['campaigns_table'][$campaign_id]['list'] = array(
        '#markup' => Link::fromTextAndUrl($campaign->list->name, $list_url)->toString(),
      );

      if (empty($campaign->mc_data->settings->template_id)) {
        $content['campaigns_table'][$campaign_id]['template'] = array(
          '#markup' => "-- none --",
        );
      }
      else {
        $template_url = Url::fromUri('https://admin.mailchimp.com/templates/edit?id=' . $campaign->mc_data->settings->template_id, array('attributes' => array('target' => '_blank')));
        $category = FALSE;
        // Templates are grouped into categories, so we go hunting for our
        // template ID in each category.
        $template_category = array();
        foreach($templates as $category_name => $template_category) {
          if (isset($template_category[$campaign->mc_data->settings->template_id])) {
            $category = $category_name;
            break;
          }
        }
        if ($category) {
          $content['campaigns_table'][$campaign_id]['template'] = array(
            '#markup' => Link::fromTextAndUrl($template_category[$campaign->mc_data->settings->template_id]->name, $template_url)->toString(),
          );
        }
        else {
          $content['campaigns_table'][$campaign_id]['template'] = array(
            '#markup' => '-- template ' .
                Url::fromRoute($campaign->mc_data->settings->template_id, $template_url, array('attributes' => array('target' => '_blank')))->toString()
                . ' not found --',
          );
        }
      }
      $content['campaigns_table'][$campaign_id]['created'] = array(
        '#markup' => \Drupal::service('date.formatter')->format(strtotime($campaign->mc_data->create_time) ,'custom','F j, Y - g:ia'),
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
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('mailchimp_campaign');

    $content = $view_builder->view($mailchimp_campaign);

    return $content;
  }

  /**
   * View a MailChimp campaign stats.
   *
   * @param MailchimpCampaign $mailchimp_campaign
   *   The MailChimp campaign to view stats for.
   *
   * @return array
   *   Renderable array of page content.
   */
  public function stats(MailchimpCampaign $mailchimp_campaign) {
    $content = array();

    /* @var \Mailchimp\MailchimpReports $mc_reports */
    $mc_reports = mailchimp_get_api_object('MailchimpReports');

    try {
      if (!$mc_reports) {
        throw new MailchimpAPIException('Cannot get campaign stats without MailChimp API. Check API key has been entered.');
      }

      $response = $mc_reports->getCampaignSummary($mailchimp_campaign->getMcCampaignId());
    } catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      \Drupal::logger('mailchimp_campaign')
        ->error('An error occurred getting report data from MailChimp: {message}', array(
        'message' => $e->getMessage()
      ));
    }

    if (!empty($response)) {
      // Attach stats JS.
      $content['#attached']['library'][] = 'mailchimp_campaign/google-jsapi';
      $content['#attached']['library'][] = 'mailchimp_campaign/campaign-stats';

      // Time series chart data.
      $content['#attached']['drupalSettings']['mailchimp_campaign'] = array(
        'stats' => array(),
      );

      foreach ($response->timeseries as $series) {
        $content['#attached']['drupalSettings']['mailchimp_campaign']['stats'][] = array(
          'timestamp' => $series->timestamp,
          'emails_sent' => isset($series->emails_sent) ? $series->emails_sent : 0,
          'unique_opens' => $series->unique_opens,
          'recipients_click' => $series->recipients_click,
        );
      }

      $content['charts'] = array(
        '#prefix' => '<h2>' . t('Hourly stats for the first 24 hours of the campaign') . '</h2>',
        '#markup' => '<div id="mailchimp-campaign-chart"></div>',
      );

      $content['metrics_table'] = array(
        '#type' => 'table',
        '#header' => array(t('Key'), t('Value')),
        '#empty' => '',
        '#prefix' => '<h2>' . t('Other campaign metrics') . '</h2>',
      );

      $stat_groups = array(
        'bounces',
        'forwards',
        'opens',
        'clicks',
        'facebook_likes',
        'list_stats'
      );

      foreach ($stat_groups as $group) {
        $content['metrics_table'][] = array(
          'key' => array(
            '#markup' => '<strong>' . ucfirst(str_replace('_', ' ', $group)) . '</strong>',
          ),
          'value' => array(
            '#markup' => ''
          ),
        );

        foreach ($response->{$group} as $key => $value) {
          if ($key == "last_open" && !empty($value)) {
            $value = \Drupal::service('date.formatter')->format(strtotime($value) ,'custom','F j, Y - g:ia') ;
          }

          $content['metrics_table'][] = array(
            'key' => array(
              '#markup' => $key,
            ),
            'value' => array(
              '#markup' => $value
            ),
          );
        }
      }
    }
    else {
      $content['unavailable'] = array(
        '#markup' => 'The campaign stats are unavailable at this time.',
      );
    }

    return $content;
  }

  /**
   * Callback for entity title autocomplete field.
   *
   * @param string $entity_type
   *   The entity type to search by title.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing matched entity data.
   */
  public function entityAutocomplete($entity_type) {
    $q = \Drupal::request()->get('q');

    $query = \Drupal::entityQuery($entity_type)
      ->condition('title', $q, 'CONTAINS')
      ->range(0, 10);

    $entity_ids = $query->execute();

    $entities = array();

    if (!empty($entity_ids)) {
      $entities_data = entity_load_multiple($entity_type, $entity_ids);

      if (!empty($entities_data)) {
        /* @var $entity \Drupal\Core\Entity\EntityInterface */
        foreach ($entities_data as $id => $entity) {
          $title = $entity->getTypedData()->getString('title');

          $entities[] = array(
            'value' => $title . ' [' . $id . ']',
            'label' => Html::escape($title),
          );
        }
      }
    }

    return new JsonResponse($entities);
  }

}
