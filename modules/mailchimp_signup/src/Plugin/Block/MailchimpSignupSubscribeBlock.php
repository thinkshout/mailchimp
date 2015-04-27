<?php
/**
 * @file
 * Contains \Drupal\mailchimp_signup\Plugin\Block\MailchimpSignupSubscribeBlock.
 */

namespace Drupal\mailchimp_signup\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Subscribe' block.
 *
 * @Block(
 *   id = "mailchimp_signup_subscribe_block",
 *   admin_label = @Translation("Subscribe Block"),
 *   category = @Translation("MailChimp Signup"),
 *   module = "mailchimp_signup",
 *   deriver = "Drupal\mailchimp_signup\Plugin\Derivative\MailchimpSignupSubscribeBlock"
 * )
 */
class MailchimpSignupSubscribeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_id = $this->getDerivativeId();

    return array(
      '#markup' => 'Default block content here.',
    );
  }

}
