<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists\Form\MailchimpListsWebhookSettingsForm.
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

    $form_state->set('list', $list);

    $default_webhook_actions = mailchimp_lists_default_webhook_actions();
    $enabled_webhook_actions = mailchimp_lists_enabled_webhook_actions($list_id);

    $form['webhook_actions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Enabled webhook actions for the !name list',
        array(
          '!name' => $list['name'],
        )),
      '#tree' => TRUE,
    );

    foreach ($default_webhook_actions as $action => $name) {
      $form['webhook_actions'][$action] = array(
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
    $list = $form_state->get('list');

    $webhook_actions = $form_state->getValue('webhook_actions');

    $actions = array();
    foreach ($webhook_actions as $webhook_id => $enable) {
      $actions[$webhook_id] = ($enable === 1);
    }

    $result = FALSE;

    if (count($actions) > 0) {
      $webhook_url = mailchimp_webhook_url();

      $webhooks = mailchimp_webhook_get($list['id']);
      if (!empty($webhooks)) {
        foreach ($webhooks as $webhook) {
          if ($webhook['url'] == $webhook_url) {
            // Delete current webhook.
            mailchimp_webhook_delete($list['id'], mailchimp_webhook_url());
          }
        }
      }

      // Add webhook with enabled actions.
      $result = mailchimp_webhook_add(
        $list['id'],
        mailchimp_webhook_url(),
        $actions
      );
    }

    if ($result) {
      drupal_set_message(t('Webhooks for list "%name" have been updated.',
        array(
          '%name' => $list['name'],
        )
      ));
    }
    else {
      drupal_set_message(t('Unable to update webhooks for list "%name".',
        array(
          '%name' => $list['name'],
        )
      ), 'warning');
    }

    $form_state->setRedirect('mailchimp_lists.overview');
  }

}
