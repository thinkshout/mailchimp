<?php

/**
 * @file
 * Field hooks.
 */

/**
 * Implements hook_field_info().
 */
function mailchimp_lists_field_info() {
  return array(
    'mailchimp_lists_subscription' => array(
      'label' => t('Mailchimp Subscription'),
      'description' => t('Allows an entity to be subscribed to a Mailchimp list.'),
      'instance_settings' => array(
        'mc_list_id' => NULL,
        'merge_fields' => array(),
        'default_subscription_settings' => array(
          'subscribed' => FALSE,
          'interest_groups' => array(),
        ),
      ),
      'default_widget' => 'mailchimp_lists_select',
      'default_formatter' => 'mailchimp_lists_subscribe',
      'no_ui' => FALSE,
    ),
  );
}

