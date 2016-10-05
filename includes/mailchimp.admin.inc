<?php

/**
 * @file
 * MailChimp module admin settings.
 */

/**
 * Return the MailChimp global settings form.
 */
function mailchimp_admin_settings() {
  $form['mailchimp_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('MailChimp API Key'),
    '#required' => TRUE,
    '#default_value' => variable_get('mailchimp_api_key', ''),
    '#description' => t('The API key for your MailChimp account. Get or generate a valid API key at your !apilink.', array('!apilink' => l(t('MailChimp API Dashboard'), 'http://admin.mailchimp.com/account/api'))),
  );
  $form['mailchimp_cron'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use batch processing.'),
    '#description' => t('Puts all MailChimp subscription operations into the cron queue. (Includes subscribe, update, and unsubscribe operations.) <i>Note: May cause confusion if caches are cleared, as requested changes will appear to have failed until cron is run.</i>'),
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
    '#description' => t('Maximum number of entities to process in a single cron run. MailChimp suggest keeping this at 5000 or below. <i>This value is also used for batch Merge Variable updates on the Fields tab (part of mailchimp_lists).</i>'),
    '#default_value' => variable_get('mailchimp_batch_limit', 100),
  );

  return system_settings_form($form);
}

/**
 * MailChimp List cache clear form.
 */
function mailchimp_clear_list_cache_form($form, &$form_state) {
  $cancel_destination = 'admin/config/services/mailchimp';
  return confirm_form($form,
    t('Reset MailChimp List Cache'),
    $cancel_destination,
    t('Confirm clearing of MailChimp list cache.'),
    'Confirm'
  );
}

/**
 * Handler for lists cache clear form.
 */
function mailchimp_clear_list_cache_form_submit($form, &$form_state) {
  mailchimp_get_lists(array(), TRUE);
  drupal_set_message(t('MailChimp List cache cleared.'));
}

/**
 * Menu callback to access mailchimp account info.
 *
 * This allows developers to easily figure out
 * the account_id associated with a given key,
 * enabling support for multiple api keys.
 *
 * @see hook_mailchimp_api_key()
 */
function mailchimp_account_overview_page() {
  $api_key_form = drupal_get_form('mailchimp_account_retrieval_form');
  $output .= drupal_render($api_key_form);
  if (isset($_REQUEST['mailchimp_api_key'])) {
    $account = mailchimp_api_account(NULL, check_plain($_REQUEST['mailchimp_api_key']));
  }
  else {
    $output .= '<h3>Default account</h3>';
    $account = mailchimp_api_account();
  }
  unset($account->_links);
  $output .= '<pre>' . json_encode($account, JSON_PRETTY_PRINT) . '</pre>';
  return $output;
}

/**
 * MailChimp account retrieval form.
 *
 * Outputs a textfield for an api_key
 * to retrieve information about a MailChimp account.
 */
function mailchimp_account_retrieval_form($form, &$form_state) {
  $form = array();
  $api_key = (isset($_REQUEST['mailchimp_api_key']))
    ? check_plain($_REQUEST['mailchimp_api_key'])
    : variable_get('mailchimp_api_key', '');
  $form['mailchimp_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('MailChimp API Key to check'),
    '#default_value' => $api_key,
    '#description' => t('The API key to check. Get or generate a valid API key at your !apilink.', array('!apilink' => l(t('MailChimp API Dashboard'), 'http://admin.mailchimp.com/account/api'))),
  );
  $form['#action'] = check_plain($_SERVER['REQUEST_URI']);
  $form['#method'] = 'post';
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Get account info'),
  );
  return $form;
}

/**
 * Form submit handler to enable the POST request processing.
 */
function mailchimp_account_retrieval_form_submit($form, &$form_state) {
  $form_state['rebuild'] = TRUE;
}
