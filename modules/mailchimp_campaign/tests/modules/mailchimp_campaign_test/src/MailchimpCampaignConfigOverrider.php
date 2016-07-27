<?php

namespace Drupal\mailchimp_campaign_test;

use Drupal\mailchimp_test\MailchimpConfigOverrider;

/**
 * Tests module overrides for configuration.
 */
class MailchimpCampaignConfigOverrider extends MailchimpConfigOverrider {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = parent::loadOverrides($names);

    $overrides['mailchimp.settings']['test_mode'] = TRUE;

    return $overrides;
  }

}
