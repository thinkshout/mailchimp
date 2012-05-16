<?php

/**
 * @file
 * Entity API hooks for mailchimp_campaign module.
 */

/**
 * Implements hook_entity_info().
 */
function mailchimp_campaign_entity_info() {
  $return = array(
    'mailchimp_campaign' => array(
      'label' => t('MailChimp Campaign'),
      'controller class' => 'EntityAPIController',
      'entity class' => 'MailChimpCampaign',
      'base table' => 'mailchimp_campaigns',
      'uri callback' => 'mailchimp_campaign_uri',
      'fieldable' => FALSE,
      'entity keys' => array(
        'id' => 'mc_campaign_id',
      ),
      'token type' => 'mailchimp_campaign',
      'module' => 'mailchimp_campaign',
      'label callback' => 'entity_class_label',
      'view modes' => array(
        'full' => array(
          'label' => t('Full Campaign'),
          'custom settings' => FALSE,
        ),
      ),
    ),
  );
  return $return;
}

/**
 * Saves a campaign.
 */
function mailchimp_campaign_save(MailChimpCampaign $campaign) {
  $campaign->updated = REQUEST_TIME;
  // Set the creation timestamp if not set.
  if (!isset($campaign->created) || empty($campaign->created)) {
    $campaign->created = REQUEST_TIME;
  }

  return $campaign->save();
}

/**
 * Load a campaign by ID.
 */
function mailchimp_campaign_load($cid) {
  $campaign = mailchimp_campaign_load_multiple(array($cid));
  return $campaign ? $campaign[$cid] : FALSE;
}

/**
 * Loads multiple campaigns.
 */
function mailchimp_campaign_load_multiple($campaign_ids = array(), $conditions = array(), $reset = FALSE) {
  if (empty($campaign_ids)) {
    $campaign_ids = FALSE;
  }
  $campaigns = entity_load('mailchimp_campaign', $campaign_ids, $conditions, $reset);

  foreach ($campaigns as $campaign) {
    $cache = $reset ? NULL : cache_get('mailchimp_campaign_' . $campaign->mc_campaign_id);

    // return cached lists
    if ($cache) {
      $campaign->mc_data = $cache->data;
    }
    // Query lists from the MC API and store in cache
    else {
      $mcapi = mailchimp_get_api_object();

      $response = $mcapi->campaigns(array('campaign_id' => $campaign->mc_campaign_id));
      if (isset($response['total']) && $response['total'] > 0) {
        $campaign->mc_data = $response['data'][0];
      }

      cache_set('mailchimp_campaign_' . $campaign->mc_campaign_id, $campaign->mc_data, 'cache', CACHE_TEMPORARY);
    }

    // lists are cachced separately, but we want to load them here.
    if (isset($campaign->mc_data['list_id']) && $campaign->mc_data['list_id']) {
      $campaign->list = mailchimp_get_list($campaign->mc_data['list_id']);
    }
  }

  return $campaigns;
}

/**
 * Delete a campaign.
 */
function mailchimp_campaign_delete($cid) {
  return mailchimp_campaign_delete_multiple(array($cid));
}

/**
 * Delete multiple campaigns.
 */
function mailchimp_campaign_delete_multiple($campaign_ids) {
  return entity_get_controller('mailchimp_campaign')->delete($campaign_ids);
}

/**
 * Display a campaign.
 *
 * @param object $campaign
 *   A fully loaded campaign object.
 *
 * @return array
 *   Renderable elements.
 */
function mailchimp_campaign_view($campaign) {
  return $campaign->view();
}

/**
 * Defines MailChimpCampaign entities
 */
class MailChimpCampaign extends Entity {
  public $mc_campaign_id, $template, $created, $updated, $mc_data, $list;

  public function __construct(array $values = array(), $entityType = 'mailchimp_campaign') {
     parent::__construct($values, $entityType);
  }

  public function buildContent($view_mode = 'full', $langcode = NULL) {
    $build = parent::buildContent($view_mode, $langcode);
    $fields = array(
      'subject' => array('label' => t('Subject'), 'value' => $this->mc_data['subject']),
      'list' => array(
        'label' => t('MailChimp List'),
        'value' => l($this->list['name'], 'https://admin.mailchimp.com/lists/dashboard/overview?id=' . $this->list['web_id'])
      ),
      'from_email' => array('label' => t('From Email'), 'value' => $this->mc_data['from_email']),
      'from_name' => array('label' => t('From Name'), 'value' => $this->mc_data['from_name']),
      'template_id' => array('label' => t('Template'), 'value' => $this->mc_data['template_id']),
      'type' => array('label' => t('List type'), 'value' => $this->mc_data['type']),
      'status' => array('label' => t('Status'), 'value' => $this->mc_data['status']),
      'content' => array(
        'label' => t('Rendered template HTML (!archive)',
                  array('!archive' => l('View MailChimp archive', $this->mc_data['archive_url']))),
        'value' => $this->template['html']
      )
    );

    foreach ($fields as $key => $field) {
      $build[$key] = array(
        '#prefix' => "<div class=\"field campaign-{$key}\"><div class=\"field-label\">{$field['label']}</div>",
        '#markup' => $field['value'],
        '#suffix' => '</div>',
      );
    }

    return $build;
  }

  protected function defaultLabel() {
    return $this->mc_data['title'];
  }

}
