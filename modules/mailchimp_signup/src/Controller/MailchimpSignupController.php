<?php

namespace Drupal\mailchimp_signup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mailchimp_signup\Entity\MailchimpSignup;

/**
 * MailChimp Signup controller.
 */
class MailchimpSignupController extends ControllerBase {

  /**
   * View a MailChimp signup form as a page.
   *
   * @param string $signup_id
   *   The ID of the MailchimpSignup entity to view.
   *
   * @return array
   *   Renderable array of page content.
   */
  public function page($signup_id) {
    $content = array();

    $signup = mailchimp_signup_load($signup_id);

    $form = new \Drupal\mailchimp_signup\Form\MailchimpSignupPageForm();

    $form_id = 'mailchimp_signup_subscribe_page_' . $signup->id . '_form';
    $form->setFormID($form_id);
    $form->setSignup($signup);

    $content = \Drupal::formBuilder()->getForm($form);

    return $content;
  }

}
