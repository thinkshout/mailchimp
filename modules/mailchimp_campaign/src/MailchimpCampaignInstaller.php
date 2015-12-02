<?php

/**
 * @file
 * Contains \Drupal\mailchimp_campaign\MailchimpCampaignInstaller.
 */
namespace Drupal\mailchimp_campaign;

use Drupal\Core\Extension\ModuleInstaller;
use Drupal\filter\Entity\FilterFormat;

/**
 * Remove filter preventing MailChimp Campaign uninstall.
 */
class MailChimpCampaignInstaller extends ModuleInstaller {
  /**
   * {@inheritdoc}
   */
  public function validateUninstall(array $module_list) {
    $reasons = array();
    foreach ($module_list as $module) {
      if ($module == 'mailchimp_campaign') {
        $this->removeFilterConfig();
      }
      foreach ($this->uninstallValidators as $validator) {
        $validation_reasons = $validator->validate($module);
        if (!empty($validation_reasons)) {
          if (!isset($reasons[$module])) {
            $reasons[$module] = array();
          }
          $reasons[$module] = array_merge($reasons[$module], $validation_reasons);
        }
      }
    }
    return $reasons;
  }

  /**
   * Deletes config.
   *
   */
  protected function removeFilterConfig() {
    foreach (FilterFormat::loadMultiple() as $filter) {
      $filter->filters();
      $filter->removeFilter('mailchimp_campaign');
      $filter->save();
    }
    $mailchimp_campaign_filter = \Drupal::configFactory()->getEditable('filter.format.mailchimp_campaign');
    $mailchimp_campaign_filter->delete();
    // Clear cache.
    drupal_flush_all_caches();
  }
}