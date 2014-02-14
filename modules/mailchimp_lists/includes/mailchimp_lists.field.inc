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
        'mergefields' => array(),
        'default_subscription_settings' => array(
          'interest_groups' => array(),
        ),
      ),
      'default_widget' => 'mailchimp_lists_select',
      'default_formatter' => 'mailchimp_lists_subscribe_default',
      'no_ui' => FALSE,
      'property_type' => 'mailchimp_lists_subscription',
      'property_callbacks' => array('mailchimp_lists_subscription_property_info_callback'),
    ),
  );
}

/**
 * Implements hook_field_instance_settings_form().
 */
function mailchimp_lists_field_instance_settings_form($field, $instance) {
  $form = array();
  $lists = mailchimp_get_lists();
  $options = array('' => t('-- Select --'));
  foreach ($lists as $mc_list) {
    $options[$mc_list['id']] = $mc_list['name'];
  }
  $form['mc_list_id'] = array(
    '#type' => 'select',
    '#title' => t('MailChimp List'),
    '#multiple' => FALSE,
    '#description' => t('Available MailChimp lists. If there are no options,
      create a list at !MailChimp first, then refresh this page.',
      array('!MailChimp' => l(t('MailChimp'), 'https://admin.mailchimp.com'))),
    '#options' => $options,
    '#default_value' => $instance['settings']['mc_list_id'],
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'mailchimp_lists_mergefields_callback',
      'wrapper' => 'mergefields-wrapper',
      'method' => 'replace',
      'effect' => 'fade',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Retrieving merge fields for this list.'),
      ),
    ),
  );
  // We add the mergefields container only. The actually mergefield form
  // elements cannot be added here because we don't have the $form_state,
  // so we add a #process function to take care of those elements.
  $form['mergefields'] = array(
    '#type' => 'fieldset',
    '#title' => t('Merge Fields'),
    '#process' => array('_mailchimp_lists_field_settings_process'),
    '#id' => 'mergefields-wrapper',
    '#tree' => TRUE,
  );

  return $form;
}

/**
 * AJAX callback to return fields for a given type.
 */
function mailchimp_lists_mergefields_callback($form, &$form_state) {
  return $form['instance']['settings']['mergefields'];
}

/**
 * Helper #process function to have ajaxified dynamic form elements.
 */
function _mailchimp_lists_field_settings_process($element, $form_state) {
  $mc_list_id = $form_state['values']['instance']['settings']['mc_list_id'];
  $defaults = $form_state['build_info']['args'][0]['settings']['mergefields'];
  if (strlen($form_state['values']['instance']['settings']['mc_list_id'])) {
    $mergevars = mailchimp_get_mergevars(array($mc_list_id));
    $fields = mailchimp_lists_fieldmap_options($form_state['values']['instance']['entity_type'], $form_state['values']['instance']['bundle']);
    foreach ($mergevars[$mc_list_id]['merge_vars'] as $mergevar) {
      $default_value = isset($defaults[$mergevar['tag']]) ? $defaults[$mergevar['tag']] : -1;
      $element[$mergevar['tag']] = array(
        '#type' => 'select',
        '#title' => $mergevar['name'],
        '#description' => '',
        '#default_value' => isset($fields[$default_value]) ? $default_value : '',
        '#required' => $mergevar['req'],
        '#options' => $fields,
      );
    }
  }
  return $element;
}

/**
 * Implements hook_field_validate().
 */
function mailchimp_lists_field_settings_validate($entity_type, $entity, $field, $instance, $langcode, $items, &$errors) {

}

/**
 * Implements hook_field_is_empty().
 */
function mailchimp_lists_field_is_empty($item, $field) {
  return FALSE;
}

/**
 * Implements hook_field_widget_info().
 */
function mailchimp_lists_field_widget_info() {
  return array(
    'mailchimp_lists_select' => array(
      'label' => t('default'),
      'field types' => array('mailchimp_lists_subscription'),
      'settings' => array(),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_DEFAULT,
        'default value' => FIELD_BEHAVIOR_DEFAULT,
      ),
    ),
  );
}

/**
 * Implements hook_field_widget_form().
 */
function mailchimp_lists_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {

  // @todo: deal with case where "required" is checked
  $default = isset($instance['default_value'][0][$element['#field_name']]) ? $instance['default_value'][0][$element['#field_name']] : FALSE;
  if (isset($element['#entity']) && isset($instance['settings']['mergefields']['EMAIL'])) {
    $entity_wrapper = entity_metadata_wrapper($element['#entity_type'], $element['#entity']);
    $email_prop = $instance['settings']['mergefields']['EMAIL'];
    $email = $entity_wrapper->{$email_prop}->value();
    if (!empty($email)) {
      $default = mailchimp_is_subscribed($instance['settings']['mc_list_id'], $email);
    }
  }
  $element += array(
    '#title' => $element['#title'],
    '#type' => 'checkbox',
    '#default_value' => $default,
  );

  return array('subscribe' => $element);
}

/**
 * Implements hook_field_formatter_info().
 */
function mailchimp_lists_field_formatter_info() {
  return array(
    'mailchimp_lists_subscribe' => array(
      'label' => t('Subscription Form'),
      'field types' => array('mailchimp_lists_subscription'),
      'settings' => array(
        'label' => ' ',
      ),
    ),
  );
}

/**
 * Implements hook_field_formatter_settings_form().
 */
function mailchimp_lists_field_formatter_settings_form($field, $instance, $view_mode, $form, &$form_state) {
  $display = $instance['display'][$view_mode];
  $settings = $display['settings'];

  $element = array();

  return $element;
}
 * Implements hook_form_FORM_ID_alter().
 *
 * Hide the cardinality setting on the field settings for
 * mailchimp_lists_subscription fields.
 */
function mailchimp_lists_form_field_ui_field_edit_form_alter(&$form, &$form_state, $form_id) {
  if ($form['#field']['type'] == 'mailchimp_lists_subscription') {
    $form['field']['cardinality']['#default_value'] = 1;
    $form['field']['cardinality']['#access'] = FALSE;
    $form['#validate'][] = 'mailchimp_lists_form_field_ui_field_edit_form_validate';
  }
}

/**
 * Validation handler for mailchimp_lists_form_field_ui_field_edit_form.
 *
 * Ensure cardinality is set to 1 on mailchimp_lists_subscription fields.
 *
 * @param $form
 * @param $form_state
 */
function mailchimp_lists_form_field_ui_field_edit_form_validate(&$form, &$form_state) {
  if ($form['#field']['type'] == 'mailchimp_lists_subscription') {
    if ($form_state['values']['field']['cardinality'] !== 1) {
      form_set_error('cardinality', t('Cardinality on mailchimp lists fields must be set to one.'));
    }
  }
}
