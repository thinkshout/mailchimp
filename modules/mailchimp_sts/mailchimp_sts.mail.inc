<?php
// $Id$

/**
 * @file
 * The code processing mail in the Amazon SES module.
 *
 */

/**
 * Modify the drupal mail system to use Amazon SES when sending emails.
 */
class MailChimpSTSMailSystem implements MailSystemInterface {
  /**
   * Concatenate and wrap the email body for either
   * plain-text or HTML emails.
   *
   * @param $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return
   *   The formatted $message.
   */
  public function format(array $message) {
    // Join the body array into one string.
    if (is_array($message['body'])) {
      $message['body'] = implode("\n\n", $message['body']);
    }
    return $message;
  }

  /**
   * Send the email message.
   *
   * @see drupal_mail()
   *
   * @param $message
   *   A message array, as described in hook_mail_alter().
   * @return
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   */
  public function mail(array $message) {
    if (!$source = variable_get('amazon_ses_from', '')) {
      drupal_set_message(t('Amazon SES can\'t send email. Please <a href="%config">configure a valid From address', array('%from' => url('admin/settings/amazon_ses'))), 'error');
      return FALSE;
    }
    $destination = array();
    $opt = array();

    $destination['ToAddresses'] = (count(explode(',', $message['to']) > 1) ? explode(',', $message['to']) : $message['to']);

    $ses_message = array(
      'Subject.Data' => $message['subject'],
      'Body.Text.Data' => $message['body'],
      'Body.Html.Data' => check_markup($message['body'], variable_get('amazon_ses_filter_format', 'full_html')),
    );

    $opt['ReturnPath'] = $source;
    if (isset($message['headers']['Reply-To']) && !empty($message['headers']['Reply-To'])) {
      if (strpos($message['headers']['Reply-To'], '<')) {
        $opt['ReturnPath'] = preg_replace('/>.*/', '', preg_replace('/.*</', '', $message['headers']['Reply-To']));
      }
      else {
        $opt['ReturnPath'] = $message['headers']['Reply-To'];
      }
    }

    if ($source == NULL || $source == '') {
      // If from email address is blank, use amazon_ses_from config option.
      if (($source = variable_get('amazon_ses_from', '')) == '') {
        // If amazon_ses_from config option is blank, use site_email.
        if (($source = variable_get('site_email', '')) == '') {
          drupal_set_message(t('There is no submitted from address.'), 'error');
          watchdog('amazon_ses', 'There is no submitted from address.', array(), WATCHDOG_ERROR);
          return FALSE;
        }
      }
    }

    if (preg_match('/^"?.*"?\s*<.*>$/', $source)) {
      $source = preg_replace("/(.*)\<(.*)\>/i", '$2', $from); // It gives: name@domain.tld
    }
    elseif (!valid_email_address($source)) {
      drupal_set_message(t('The submitted from address (@from) is not valid.', array('@from' => $from)), 'error');
      watchdog('amazon_ses', 'The submitted from address (@from) is not valid.', array('@from' => $from), WATCHDOG_ERROR);
      return FALSE;
    }

    // Parse the headers of the message
    foreach ($message['headers'] as $key => $value) {
      switch (drupal_strtolower($key)) {
        case 'from':
          if ($source == NULL || $source == '') {
            $source = $value;
          }
        break;

        case 'content-type':
          $vars = explode('; ', $value);
          foreach ($vars as $i => $var) {
            if ($cut = strpos($var, '=')) {
              $new_var = drupal_strtolower(drupal_substr($var, $cut + 1));
              $new_key = drupal_substr($var, 0, $cut);
              unset($vars[$i]);
              $vars[$new_key] = $new_var;
            }
          }
          // Set the charset based on the provided value, if there is one.
          $charset = $vars['charset'];
          if ($charset) {
            $ses_message['Subject.Text.Charset'] = $charset;
            $ses_message['Body.Text.Charset'] = $charset;
            $ses_message['Body.Html.Charset'] = $charset;
          }
        break;

        case 'cc':
          $destination['CcAddresses'] = split(',', $value);
        break;

        case 'bcc':
          $destination['BccAddresses'] = split(',', $value);
        break;
      }
    }

    if (variable_get('amazon_ses_debugging', 0) == 1) {
      watchdog('amazon-ses', 'Sending mail to: @to', array('@to' => $destination['ToAddresses'][0]));
      drupal_set_message(t('Sending mail to: @to.', array('@to' => $destination['ToAddresses'][0])), 'notice');
    }

    $mailer = new AmazonSES();
    $status = $mailer->send_email($source, $destination, $ses_message, $opt);

    if (!$status->isOK()) {
      watchdog('amazon-ses', 'Error sending email from @from to @to : !error_message', array('@from' => $source, '@to' => print_r($destination['ToAddresses'], TRUE), '!error_message' => (string) $status->body->Error->Message), WATCHDOG_ERROR);
      watchdog('amazon-ses', '<pre>Source: !source <br />Destination: !destination <br />Message: !message', array('!source' => print_r($source, TRUE), '!destination' => print_r($destination, TRUE), '!message' => print_r($ses_message, TRUE)), WATCHDOG_ERROR);
      return FALSE;
    }
    return TRUE;
  }
}
