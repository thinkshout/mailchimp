<?php
/**
 * @file
 * Administrative forms for MailChimp STS module.
 */

function mailchimp_sts_admin_page() {
  $mailchimp_sts = new MailChimpSTS();
  $addresses = $mailchimp_sts->list_verified_email_addresses();
  if (!$mailchimp_sts->errorCode) {
    $rows = array();
    if (in_array(variable_get('mailchimp_sts_from', ''), $addresses)) {
      $rows[] = array('Ready to send email', '<span style="color: #00ff00; font-weight: bold;">YES</span>');
    }
    else {
      $rows[] = array('Ready to send email', '<span style="color: #ff0000; font-weight: bold;">NO</span>');
      $rows[] = array(array('data' => 'You must specify an alternate Email from address below, or takes steps to verify your site\'s email address with Amazon AWS.', 'colspan' => 2));
    }
    $rows[] = array('Verified email addresses', theme('item_list', array('items' => $addresses)));
    
    return theme('table', array('rows' => $rows)) . l(t('View Amazon SES statistics'), 'admin/reports/mailchimp_sts') .
      drupal_render(drupal_get_form('mailchimp_sts_admin_settings'));  
  }
  else {
    drupal_set_message(
      t('There was a problem accessing the MailChimp STS service: %msg', 
      array('%msg' => $mailchimp_sts->errorMessage)
    ));
    
    return t('Refer to the !link for properly configuring MailChimp STS.', 
      array('!link' => l('MailChimp Documentation', 
      'http://kb.mailchimp.com/article/how-does-mailchimp-integrate-with-amazon-ses'
    )));
  }

}

/**
 * Administrative settings.
 *
 * @return
 *   An array containing form items to place on the module settings page.
 */
function mailchimp_sts_admin_settings() {
  $form = array();
  
  $form['onoff'] = array(
    '#type'  => 'fieldset',
    '#title' => t('Install options'),
  );  
  $form['onoff']['mailchimp_sts_on'] = array(
    '#type'          => 'radios',
    '#title'         => t('Turn this module on or off'),
    '#default_value' => variable_get('mailchimp_sts_on', 0),
    '#options'       => array(1 => t('On'), 0 => t('Off')),
    '#description'   => t('To uninstall this module you must turn it off here first.'),
  );
  $form['onoff']['mailchimp_sts_debugging'] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Enable debugging'),
    '#default_value' => variable_get('mailchimp_sts_debugging', 0),
    '#description'   => t('Checking this box will print Amazon SES messages from the server for every email that is sent.'),
  );

  $form['email_options'] = array(
    '#type'  => 'fieldset',
    '#title' => t('Email options'),
  );
  $form['email_options']['mailchimp_sts_from'] = array(
    '#type'          => 'select',
    '#title'         => t('Email from address'),
    '#default_value' => variable_get('mailchimp_sts_from', ''),
    '#description'   => t('Select a verified email address. All email will be sent as this address.'),
    '#options' => mailchimp_sts_verified_addresses(),
  );
  $form['email_options']['mailchimp_sts_from_new'] = array(
    '#type' => 'fieldset',
    '#title' => t('Verify a new email address'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['email_options']['mailchimp_sts_from_new']['new_address'] = array(
    '#type' => 'textfield',
    '#title' => 'New Email address',
  );
  $form['email_options']['mailchimp_sts_from_new']['new_address_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Send'),
    '#submit' => array('mailchimp_sts_admin_new_address_submit'),
  );

  $formats = filter_formats();
  $options = array();
  foreach ($formats AS $v => $format) {          
    $options[$v] = $format->name;
  }
  $form['email_options']['mailchimp_sts_filter_format'] = array(
    '#type' => 'select',
    '#title' => t('Input format'),
    '#description' => t('Email bodies must be converted to HTML prior to sending. Please select an Input Format to use. Full HTML recommended.'),
    '#options' => $options,
    '#default_value' => array(variable_get('mailchimp_sts_filter_format', 'full_html')),
  );

  $form['email_test'] = array(
    '#type'  => 'fieldset',
    '#title' => t('Send test email'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['email_test']['mailchimp_sts_test_address'] = array(
    '#type' => 'textfield',
    '#title' => t('Email address to send a test email to'),
    '#default_value' => '',
    '#description' => t('Type in an address to have a test email sent there.'),
  );
  $form['email_test']['mailchimp_sts_test_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Test body contents'),
    '#default_value' => t('If you receive this message it means your site is capable of using Amazon SES to send email.'),
  );

  $form['#submit'][] = 'mailchimp_sts_admin_settings_submit';
  return system_settings_form($form);
}

/**
 * Validataion for the administrative settings form.
 *
 * @param form
 *   An associative array containing the structure of the form.
 * @param form_state
 *   A keyed array containing the current state of the form.
 */
function mailchimp_sts_admin_settings_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Send') && !valid_email_address($form_state['values']['new_address'])) {
    form_set_error('new_address', t('This email address is not valid.'));
  }
}

/**
 * Submission for the administrative settings form.
 *
 * @param form
 *   An associative array containing the structure of the form.
 * @param form_state
 *   A keyed array containing the current state of the form.
 */
function mailchimp_sts_admin_settings_submit($form, &$form_state) {
  $values = $form_state['values'];

  // If an address was given, send a test email message.
  $test_address = $values['mailchimp_sts_test_address'];
  if ($test_address != '') {
    global $language;
    // Clear the variable so only one message is sent.
    variable_del('mailchimp_sts_test_address');
    $params['subject'] = t('Drupal Amazon SES test email');
    $params['body'] = $values['mailchimp_sts_test_body'];
    drupal_mail('mailchimp_sts', 'mailchimp-sts-test', $test_address, $language, $params);
    drupal_set_message(t('A test email has been sent to @email.', array('@email' => $test_address)));
  }

  if ($values['mailchimp_sts_on']) {
    variable_set('mail_system', array('default-system' => 'MailChimpSTSMailSystem'));
    drupal_set_message(t('MailChimp STS will be used to deliver all site emails.'));
    watchdog('mailchimp_sts', 'MailChimp STS has been enabled.');
  }
  else {
    variable_set('mail_system', array('default-system' => 'DefaultMailSystem'));
  }
}

/**
 * Submission for the administrative settings form - new address.
 *
 * @param form
 *   An associative array containing the structure of the form.
 * @param form_state
 *   A keyed array containing the current state of the form.
 */
function mailchimp_sts_admin_new_address_submit($form, &$form_state) {
  $address = $form_state['values']['new_address'];
  if ($address) {
    $mailchimp_sts = new MailChimpSTS();
    $new_address = $mailchimp_sts->verify_email_address($address);
    if ($mailchimp_sts->errorCode) {
      drupal_set_message(t('There has been an error verifying your message: %msg.', array('%msg' => $mailchimp_sts->errorMessage)));
    }
    else {
      drupal_set_message(t('The address %address has been verified. Please check your email for a confirmation message from Amazon.', array('%address' => $address)));
    }
  }
}

/**
 * Page callback for showing basic sts usage and quotas
 */
function mailchimp_sts_admin_stats() {
  if ($quota = mailchimp_sts_quota()) {
    $rows = array();
    foreach (array(
        'SentLast24Hours' => t('Emails sent in the past 24 hours:'),
        'Max24HourSend' => t('Maximum number of emails you can send per 24 hours:'),
        'MaxSendRate' => t('Maximum number of emails you can send per second:'
      )) AS $k => $v) {
      $rows[] = array($v, str_replace('.0', '', (string) $quota[$k]));
    }
    $output = theme('table', array('header' => array(array('data' => 'Quota', 'colspan' => 2)), 'rows' => $rows));
  }

  $output .= l(t('Configure Amazon SES'), 'admin/config/services/mailchimp/sts');
  return $output;
}

function mailchimp_sts_quota() {
  $mailchimp_sts = new MailChimpSTS();
  $quota = $mailchimp_sts->get_send_quota();
  if (!$mailchimp_sts->errorCode) {
    dpm($quota);
    return $quota;
  }
}

function mailchimp_sts_send_statistics() {
  $mailchimp_sts = new MailChimpSTS();
  $stats = $mailchimp_sts->get_send_statistics();
  if ($stats->IsOK()) {
    return $stats->body->GetSendStatisticsResult;
  }
}

function mailchimp_sts_verified_addresses() {
  $emails = array();
  $mailchimp_sts = new MailChimpSTS();
  $response = $mailchimp_sts->list_verified_email_addresses();
  if (!$mailchimp_sts->errorCode) {
    foreach ($response['email_addresses'] AS $email) {
      if (!empty($email)) {
        $emails[$email] = $email;        
      }
    }
  }
  
  return $emails;
}
