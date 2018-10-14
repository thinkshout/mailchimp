<?php

namespace Drupal\mailchimp_lists\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure settings for a Mailchimp list webhook.
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

    $default_webhook_events = mailchimp_lists_default_webhook_events();
    $enabled_webhook_events = mailchimp_lists_enabled_webhook_events($list_id);

    $form['webhook_events'] = array(
      '#type' => 'fieldset',
      '#title' => t('Enabled webhook events for the @name list',
        array(
          '@name' => $list->name,
        )),
      '#tree' => TRUE,
    );

    foreach ($default_webhook_events as $event => $name) {
      $form['webhook_events'][$event] = array(
        '#type' => 'checkbox',
        '#title' => $name,
        '#default_value' => in_array($event, $enabled_webhook_events),
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
    /* @var \Mailchimp\MailchimpLists $mc_lists */
    $mc_lists = mailchimp_get_api_object('MailchimpLists');
    $list = $form_state->get('list');

    $webhook_events = $form_state->getValue('webhook_events');

    $events = array();
    foreach ($webhook_events as $webhook_id => $enable) {
      $events[$webhook_id] = ($enable === 1);
    }

    $result = FALSE;

    if (count($events) > 0) {
      $webhook_url = mailchimp_webhook_url();

      $webhooks = mailchimp_webhook_get($list->id);

      if (!empty($webhooks)) {
        foreach ($webhooks as $webhook) {
          if ($webhook->url == $webhook_url) {
            // Delete current webhook.
            mailchimp_webhook_delete($list->id, mailchimp_webhook_url());
          }
        }
      }

      $sources = array(
        'user' => TRUE,
        'admin' => TRUE,
        'api' => FALSE,
      );

      // Add webhook with enabled events.
      $result = mailchimp_webhook_add(
        $list->id,
        mailchimp_webhook_url(),
        $events,
        $sources
      );
    }

    if ($result) {
      drupal_set_message(t('Webhooks for list "%name" have been updated.',
        array(
          '%name' => $list->name,
        )
      ));
    }
    else {
      drupal_set_message(t('Unable to update webhooks for list "%name".',
        array(
          '%name' => $list->name,
        )
      ), 'warning');
    }

    $form_state->setRedirect('mailchimp_lists.overview');
  }

}
