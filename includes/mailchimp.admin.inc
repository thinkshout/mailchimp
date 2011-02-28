<?php

/**
 * @file
 * Mailchimp module admin settings.
 */

/**
 * implementation of hook_admin_settings
 * @return <type>
 */
function mailchimp_admin_settings() {
  module_load_include('php', 'mailchimp', 'MCAPI.class');

  $form['mailchimp_account_info'] = array(
      '#type'       => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title'      => 'MailChimp API Key',
  );
  $form['mailchimp_account_info']['mailchimp_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Mailchimp API Key'),
      '#required' => TRUE,
      '#default_value' => variable_get('mailchimp_api_key', ''),
      '#description' => t('The API key for your MailChimp account. Get or generate a valid API key at your !apilink.', array('!apilink' => l(t('MailChimp API Dashboard'), 'http://admin.mailchimp.com/account/api')))
  );
  
  return system_settings_form($form);
}
