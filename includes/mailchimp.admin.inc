<?php

/**
 * @file
 * Mailchimp module admin settings.
 */

/**
 * Mailchimp List cache clear form.
 */
function mailchimp_clear_list_cache_form($form, &$form_state) {
  $cancel_destination = 'admin/config/services/mailchimp';
  return confirm_form($form,
    t('Reset Mailchimp List Cache'),
    $cancel_destination,
    t('Confirm clearing of Mailchimp list cache.'),
    'Confirm'
  );
}

/**
 * Handler for lists cache clear form.
 */
function mailchimp_clear_list_cache_form_submit($form, &$form_state) {
  mailchimp_get_lists(array(), TRUE);
  drupal_set_message(t('Mailchimp List cache cleared.'));
}
