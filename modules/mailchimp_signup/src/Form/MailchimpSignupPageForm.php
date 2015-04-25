<?php
/**
 * @file
 * Contains \Drupal\mailchimp_signup\Form\MailchimpSignupPageForm.
 */

namespace Drupal\mailchimp_signup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mailchimp_lists\Plugin\Field\FieldFormatter\MailchimpListsFieldSubscribeFormatter;
use Drupal\mailchimp_lists\Plugin\Field\FieldType\MailchimpListsSubscription;

/**
 * Subscribe to a MailChimp list.
 */
class MailchimpSignupPageForm extends FormBase {

  /**
   * The ID for this form.
   * Set as class property so it can be overwritten as needed.
   *
   * @var string
   */
  private $formId = 'mailchimp_signup_page';

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return $this->formId;
  }

  public function setFormID($formId) {
    $this->formId = $formId;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailchimp_signup.page'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
