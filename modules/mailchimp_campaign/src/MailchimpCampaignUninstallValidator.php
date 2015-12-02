<?php

/**
 * @file
 * Contains \Drupal\mailchimp_campaign\MailchimpCampaignUninstallValidator.
 */
namespace Drupal\mailchimp_campaign;

use Drupal\filter\FilterUninstallValidator;

/**
 * Remove filter preventing MailChimp Campaign uninstall.
 */
class MailChimpCampaignUninstallValidator extends FilterUninstallValidator {
  /**
   * Constructs a new FilterUninstallValidator.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $filter_manager
   *   The filter plugin manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(PluginManagerInterface $filter_manager, EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->filterStorage = "";
  }

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