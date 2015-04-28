<?php

/**
 * @file
 * Contains \Drupal\mailchimp_lists_test\ConfigOverrider.
 */

namespace Drupal\mailchimp_lists_test;

use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Tests module overrides for configuration.
 */
class ConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = array(
      'mailchimp.settings' => array(
        'api_key' => 'MAILCHIMP_TEST_API_KEY',
        'cron' => FALSE,
        'batch_limit' => 100,
        'api_classname' => 'Drupal\mailchimp_lists_test\DrupalMailchimp',
        'test_mode' => TRUE,
      ),
    );

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'mailchimp_lists_test_cache';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
