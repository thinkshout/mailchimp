<?php

/**
 * @file
 * Entity API hooks for mailchimp_campaigns module.
 */

/**
 * Implements hook_entity_info().
 */
function mailchimp_campaigns_entity_info() {
  $return = array(
    'mailchimp_campaigns' => array(
      'label' => t('MailChimp Campaigns'),
      'controller class' => 'EntityAPIController',
      'base table' => 'mailchimp_campaigns',
      'uri callback' => 'mailchimp_campaigns_uri',
      'fieldable' => TRUE,
      'entity keys' => array(
        'id' => 'mc_campaign_id',
      ),
    ),
  );
  return $return;
}

/**
 * Saves a campaign.
 */
function mailchimp_campaigns_save($campaign) {
  $campaign->updated = REQUEST_TIME;
  // Set the creation timestamp if not set.
  if (!isset($campaign->created) || empty($campaign->created)) {
    $campaign->created = REQUEST_TIME;
  }

  return entity_get_controller('mailchimp_campaigns')->save($campaign);
}

/**
 * Load a campaign by ID.
 */
function mailchimp_campaigns_load($cid) {
  $campaign = mailchimp_campaigns_load_multiple(array($cid));
  return $campaign ? $campaign[$cid] : FALSE;
}

/**
 * Loads multiple campaigns.
 */
function mailchimp_campaigns_load_multiple($campaign_ids = array(), $conditions = array(), $reset = FALSE) {
  if (empty($campaign_ids)) {
    $campaign_ids = FALSE;
  }
  $campaigns = entity_load('mailchimp_campaigns', $campaign_ids, $conditions, $reset);

  foreach ($campaigns as $campaign) {
    $cache = $reset ? NULL : cache_get('mailchimp_campaigns_' .
        $campaign->mc_campaign_id
    );
    // return cached lists
    if ($cache) {
      $campaign->data = $cache->data->data;
      $campaign->content = $cache->data->content;
    }
    // Query lists from the MC API and store in cache
    else {
      $mcapi = mailchimp_get_api_object();

      $response = $mcapi->campaigns(
        array('campaign_id' => $campaign->mc_campaign_id)
      );
      if (isset($response['total']) && $response['total'] > 0) {
        $campaign->data = $response['data'][0];
      }

      $response = $mcapi->campaignContent($campaign->mc_campaign_id);
      if (isset($response)) {
        $campaign->content = $response;
      }

      cache_set('mailchimp_campaigns_' . $campaign->mc_campaign_id, $campaign,
        'cache', CACHE_TEMPORARY
      );
    }
  }

  return $campaigns;
}

/**
 * Delete a campaign.
 */
function mailchimp_campaigns_delete($cid) {
  return mailchimp_campaigns_delete_multiple(array($cid));
}

/**
 * Delete multiple campaigns.
 */
function mailchimp_campaigns_delete_multiple($campaign_ids) {
  return entity_get_controller('mailchimp_campaigns')->delete($campaign_ids);
}
