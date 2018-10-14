<?php

namespace Drupal\mailchimp_signup\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Subscribe' block.
 *
 * @Block(
 *   id = "mailchimp_signup_subscribe_block",
 *   admin_label = @Translation("Subscribe Block"),
 *   category = @Translation("Mailchimp Signup"),
 *   module = "mailchimp_signup",
 *   deriver = "Drupal\mailchimp_signup\Plugin\Derivative\MailchimpSignupSubscribeBlock"
 * )
 */
class MailchimpSignupSubscribeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $signup_id = $this->getDerivativeId();

    /* @var $signup \Drupal\mailchimp_signup\Entity\MailchimpSignup */
    $signup = mailchimp_signup_load($signup_id);

    $form = new \Drupal\mailchimp_signup\Form\MailchimpSignupPageForm();

    $form_id = 'mailchimp_signup_subscribe_block_' . $signup->id . '_form';
    $form->setFormID($form_id);
    $form->setSignup($signup);

    $content = \Drupal::formBuilder()->getForm($form);

    return $content;
  }

}
