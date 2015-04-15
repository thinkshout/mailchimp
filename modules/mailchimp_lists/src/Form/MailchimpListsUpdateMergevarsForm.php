<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists\Form\MailchimpListsUpdateMergevarsForm.
 */

namespace Drupal\mailchimp_lists\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Batch update MailChimp lists mergevars.
 */
class MailchimpListsUpdateMergevarsForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mailchimp_lists_admin_update_mergevars';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailchimp_lists.update_mergevars'];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Update mergevars on all entities?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('mailchimp_lists.fields');
  }

  public function getDescription() {
    return t('This can overwrite values configured directly on your Mailchimp Account.');
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

    // TODO: Update mergevars.

    drupal_set_message(t('Mergevars updated.'));
  }

}
