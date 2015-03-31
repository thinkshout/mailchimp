<?php

/**
 * @file
 * mailchimp_signup module admin settings.
 */

/**
 * Signup deletion form.
 */
function mailchimp_signup_signup_delete_form($form, &$form_state, $signup) {
  $form_state['signup'] = $signup;
  return confirm_form($form,
    t('Are you sure you want to delete the signup form %name?', array('%name' => $signup->label())),
    'admin/config/services/mailchimp/signup/' . $signup->identifier() . '/edit',
    t('This action cannot be undone.'),
    t('Delete Signup form'));
}

/**
 * Submit function for the delete button on the signup overview and edit forms.
 */
function mailchimp_signup_delete_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/config/services/mailchimp/signup/' . $form_state['signup']->identifier() . '/delete';
}

/**
 * Submit handler for mailchimp_signup_signup_delete_form().
 */
function mailchimp_signup_signup_delete_form_submit($form, &$form_state) {
  $signup = $form_state['signup'];
  mailchimp_signup_delete_multiple(array($signup->identifier()));
  drupal_set_message(t('%name has been deleted.', array('%name' => $signup->label())));
  $form_state['redirect'] = 'admin/config/services/mailchimp/signup';
}

/**
 * Create a new Mailchimp Signup object.
 *
 * @param array $values
 *   Associative array of values.
 *
 * @return MailchimpSignup
 *   New MailchimpSignup entity.
 */
function mailchimp_signup_create(array $values = array()) {
  return entity_get_controller('mailchimp_signup')->create($values);
}
