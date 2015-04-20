<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists\Form\MailchimpListsSubscribeForm.
 */

namespace Drupal\mailchimp_lists\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Subscribe to a MailChimp list.
 */
class MailchimpListsSubscribeForm extends FormBase {

  /**
   * The ID for this form.
   * Set as class property so it can be overwritten as needed.
   *
   * @var string
   */
  private $form_id = 'mailchimp_lists_subscribe';

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return $this->form_id;
  }

  public function setFormID($form_id) {
    $this->form_id = $form_id;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailchimp_lists.subscribe'];
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
