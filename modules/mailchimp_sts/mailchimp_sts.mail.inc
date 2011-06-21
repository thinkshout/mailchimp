<?php

/**
 * @file
 * Implements MailChimp STS as a Drupal MailSystemInterface
 */

/**
 * Modify the drupal mail system to use MailChimp STS when sending emails.
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
    if (!$from = variable_get('mailchimp_sts_from', '')) {
      drupal_set_message(t('MailChimp STS can\'t send email. Please !link.',
        array('!link' => l('add a verified from address',
          'admin/config/services/mailchimp/sts'))), 'error');
      return FALSE;
    }

    $to = (count(explode(',', $message['to']) > 1) ? explode(',', $message['to']) : $message['to']);

    $sts_message = array(
      'html' => check_markup($message['body'], variable_get('mailchimp_sts_filter_format', 'full_html')),
      'text' => drupal_html_to_text($message['body']),
      'subject' => $message['subject'],
      //'from_name' => $from,
      'from_email' => $from,
      'to_email' => $to,
      //'to_name' => $message['subject'],
      //'autogen_html' =>
    );

		// Handle custom headers
		foreach ($message['headers'] as $name => $value) {
			if ($name == 'Reply-To') {
				$sts_message['reply_to'] = $value;
			}
		}

    if (variable_get('mailchimp_sts_debugging', 0) == 1) {
      watchdog('mailchimp_sts', 'Sending mail to: @to', array('@to' => $to[0]));
      drupal_set_message(t('Sending mail to: @to.', array('@to' => $to[0])), 'notice');
    }

    // send the email passing the mail key as the tag for use in reporting
    $mailer = new MailChimpSTS();
    $status = $mailer->send_email($sts_message, TRUE, TRUE, array($message['key']));

    if ($mailer->errorCode) {
      watchdog('mailchimp_sts', 'Error sending email from @from to @to : !error_message', array('@from' => $from, '@to' => print_r($to, TRUE), '!error_message' => $mailer->errorMessage), WATCHDOG_ERROR);
      return FALSE;
    }

    return TRUE;
  }
}
