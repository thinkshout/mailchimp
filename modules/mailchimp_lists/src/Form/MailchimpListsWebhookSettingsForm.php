<?php
/**
 * @file
 * Contains \Drupal\mailchimp\Form\MailchimpListsWebhookSettingsForm.
 */

namespace Drupal\mailchimp_lists\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure settings for a MailChimp list webhook.
 */
class MailchimpListsWebhookSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mailchimp_lists_webhook_settings';
  }

  protected function getEditableConfigNames() {
    return ['mailchimp_lists.webhook'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $request;

    $list_id = $request->attributes->get('_raw_variables')->get('list_id');

    $list = mailchimp_get_list($list_id);

    $default_webhook_actions = mailchimp_lists_default_webhook_actions();
    $enabled_webhook_actions = mailchimp_lists_enabled_webhook_actions($list_id);

    $form['webhooks'] = array(
      '#type' => 'fieldset',
      '#title' => t('Enabled webhook actions for the !name list',
        array(
          '!name' => $list['name'],
        )),
      '#tree' => TRUE,
    );

    foreach ($default_webhook_actions as $action => $name) {
      $form['webhooks'][$action] = array(
        '#type' => 'checkbox',
        '#title' => $name,
        '#default_value' => in_array($action, $enabled_webhook_actions),
      );
    }

    return parent::buildForm($form, $form_state);
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

    parent::submitForm($form, $form_state);
  }

}
