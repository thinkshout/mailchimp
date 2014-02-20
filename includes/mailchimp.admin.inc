<?php

/**
 * @file
 * Mailchimp module admin settings.
 */

/**
 * Return the MailChimp global settings form.
 */
function mailchimp_admin_settings() {
  $form['mailchimp_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Mailchimp API Key'),
    '#required' => TRUE,
    '#default_value' => variable_get('mailchimp_api_key', ''),
    '#description' => t('The API key for your MailChimp account. Get or generate a valid API key at your !apilink.', array('!apilink' => l(t('MailChimp API Dashboard'), 'http://admin.mailchimp.com/account/api'))),
  );
  $form['mailchimp_cron'] = array(
    '#type' => 'checkbox',
    '#title' => 'Use batch processing.',
    '#description' => 'Puts all Mailchimp subscription operations into the cron queue. (Includes subscribe, update, and unsubscribe operations.) <i>Note: May cause confusion if caches are cleared, as requested changes will appear to have failed until cron is run.</i>',
    '#default_value' => variable_get('mailchimp_cron', FALSE),
  );
  $form['mailchimp_batch_limit'] = array(
    '#type' => 'select',
    '#options' => array(
      '1' => '1',
      '10' => '10',
      '25' => '25',
      '50' => '50',
      '75' => '75',
      '100' => '100',
      '250' => '250',
      '500' => '500',
      '750' => '750',
      '1000' => '1000',
      '2500' => '2500',
      '5000' => '5000',
      '7500' => '7500',
      '10000' => '10000',
    ),
    '#title' => t('Batch limit'),
    '#description' => t('Maximum number of entities to process in a single cron run. Mailchimp suggest keeping this below 5000-10000.'),
    '#default_value' => variable_get('mailchimp_batch_limit', 100),
  );

  return system_settings_form($form);
}
