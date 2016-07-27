<?php

namespace Drupal\mailchimp_campaign;

use Drupal\filter\FilterUninstallValidator;

/**
 * Remove filter preventing MailChimp Campaign uninstall.
 */
class MailChimpCampaignUninstallValidator extends FilterUninstallValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($module == 'mailchimp_campaign') {
      $this->removeFilterConfig();
    }
    return $reasons;
  }

  /**
   * Deletes config.
   *
   */
  protected function removeFilterConfig() {
    $mailchimp_campaign_filter = \Drupal::configFactory()->getEditable('filter.format.mailchimp_campaign');
    $mailchimp_campaign_filter->delete();
    // Clear cache.
    drupal_flush_all_caches();
  }

}