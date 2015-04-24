<?php

/**
 * @file
 * MailChimpCampaign controller class.
 */

class MailChimpCampaignController extends EntityAPIController {

  /**
   * Implement to clear MailChimp campaign data caches.
   *
   * @param array $ids
   *   Optional array of campaign IDs to clear cache for.
   */

  // TODO: Move functionality out of entity controller.

  public function resetCache(array $ids = NULL) {
    parent::resetCache($ids);
    $cache = \Drupal::cache('mailchimp');
    if ($cached_campaigns = $cache->get('campaigns')) {
      // Reset granular campaigns.
      if (isset($ids)) {
        foreach ($cached_campaigns->data as $id => $cached_campaign) {
          if (in_array($id, $ids)) {
            unset($cached_campaigns->data[$id]);
          }
        }
      }

      $cache_data = (isset($cached_campaigns)) ? $cached_campaigns->data : NULL;

      $cache->set('campaigns', $cache_data);
    }
  }

  /**
   * Overrides EntityAPIController::buildQuery().
   *
   * Implement to order by created date since our IDs are not numeric.
   */

  // TODO: Move functionality out of entity controller.

  protected function buildQuery($ids, $conditions = array(), $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $conditions, $revision_id);
    $query->orderBy('created', 'DESC');
    return $query;
  }

}
