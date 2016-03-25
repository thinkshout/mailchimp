<?php

/**
 * @file
 * Contains \Drupal\mailchimp_campaign_test\MailchimpCampaignConfigOverrider.
 */

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

    $overrides['mailchimp.settings']['api_classname'] = 'Drupal\mailchimp_campaign_test\DrupalMailchimp';

    return $overrides;
  }

}
