<?php

namespace Drupal\mailchimp_lists\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Clear MailChimp lists cache.
 */
class MailchimpListsClearCacheForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mailchimp_lists_admin_clear_cache';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailchimp_lists.clear_cache'];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Reset Mailchimp List Cache');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('mailchimp_lists.overview');
  }

  public function getDescription() {
    return t('Confirm clearing of Mailchimp list cache.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    mailchimp_get_lists(array(), TRUE);
    drupal_set_message(t('MailChimp lists cache cleared.'));
  }

}
