<?php

/**
 * @file
 * Contains \Drupal\mailchimp_signup\Plugin\Derivative\MailchimpSignupSubscribeBlock.
 */

namespace Drupal\mailchimp_signup\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Derivative\DeriverInterface;

/**
 * Provides block plugin definitions for MailChimp Signup blocks.
 *
 * @see \Drupal\mailchimp_signup\Plugin\Block\MailchimpSignupSubscribeBlock
 */
class MailchimpSignupSubscribeBlock extends DeriverBase implements DeriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }

    $this->getDerivativeDefinitions($base_plugin_definition);

    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $myblocks = array(
      'mailchimp_subscribe_block_first' => t('MailChimp Subscribe Block: First'),
      'mailchimp_subscribe_block_second' => t('MailChimp Subscribe Block: Second'),
    );

    foreach ($myblocks as $block_id => $block_label) {
      $this->derivatives[$block_id] = $base_plugin_definition;
      $this->derivatives[$block_id]['admin_label'] = $block_label;
      $this->derivatives[$block_id]['cache'] = DRUPAL_NO_CACHE;
    }

    return $this->derivatives;
  }

}
