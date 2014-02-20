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
        'show_interest_groups' => FALSE,
        'interest_groups_title' => NULL,
        'mergefields' => array(),
        'options' => array(
          'subscribe' => FALSE,
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
  $form['show_interest_groups'] = array(
    '#title' => "Enable Interest Groups",
    '#type' => "checkbox",
    '#default_value' => $instance['settings']['show_interest_groups'],
  );
  $form['interest_groups_title'] = array(
    '#title' => "Interest Groups Label",
    '#type' => "textfield",
    '#default_value' => isset($instance['settings']['interest_groups_title']) ? $instance['settings']['interest_groups_title'] : "Interest Groups",
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
  $default = isset($instance['default_value'][0]['options']['subscribe']) ? $instance['default_value'][0]['options']['subscribe'] : FALSE;
  $email = NULL;
  if (isset($element['#entity'])) {
    $entity_wrapper = entity_metadata_wrapper($element['#entity_type'], $element['#entity']);
    $email = mailchimp_lists_load_email($instance, $entity_wrapper, FALSE);
    if ($email) {
      $default = mailchimp_is_subscribed($instance['settings']['mc_list_id'], $email);
    }
  }
  $element += array(
    '#title' => $element['#title'],
    '#type' => 'fieldset',
  );
  $element['subscribe'] = array(
    '#title' => 'Subscribe',
    '#type' => 'checkbox',
    '#default_value' => $default,
  );
  if ($instance['settings']['show_interest_groups']) {
    $mc_list = mailchimp_get_list($instance['settings']['mc_list_id']);
    $element['interest_groups'] = array(
      '#type' => 'fieldset',
      '#title' => $instance['settings']['interest_groups_title'],
      '#weight' => 100,
      '#states' => array(
        'invisible' => array(
          ':input[name="' . $field['field_name'] . '['. $langcode .'][0][options][subscribe]"]' => array('checked' => FALSE),
        ),
      ),
    );
    $element['interest_groups'] += _mailchimp_lists_interest_groups_form_elements($mc_list, $instance['default_value'][0]['options']['interest_groups'], $email);
  }
  return array('options' => $element);
}

/**
 * Implements hook_field_formatter_info().
 */
function mailchimp_lists_field_formatter_info() {
  return array(
    'mailchimp_lists_field_subscribe' => array(
      'label' => t('Subscription Form'),
      'field types' => array('mailchimp_lists_subscription'),
      'settings' => array(
        'show_interest_groups' => FALSE,
      ),
    ),
    'mailchimp_lists_subscribe_default' => array(
      'label' => t('Subscription Info'),
      'field types' => array('mailchimp_lists_subscription'),
      'settings' => array(
        'show_interest_groups' => FALSE,
        'interest_groups' => array(),
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
  $element['show_interest_groups'] = array(
    '#title' => t('Show Interest Groups'),
    '#type' => 'checkbox',
    '#default_value' => $settings['show_interest_groups'],
  );

  return $element;
}

/**
 * Implements hook_field_formatter_settings_summary().
 */
function mailchimp_lists_field_formatter_settings_summary($field, $instance, $view_mode) {
  $display = $instance['display'][$view_mode];
  $settings = $display['settings'];
  if ($settings['show_interest_groups']) {
    $summary = t('Display Interest Groups');
  }
  else {
    $summary = t('Hide Interest Groups');
  }

  return $summary;
}

/**
 * Implements hook_field_formatter_view().
 */
function mailchimp_lists_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = array();
  $mc_list = mailchimp_get_list($instance['settings']['mc_list_id']);
  $wrapper = entity_metadata_wrapper($entity_type, $entity);
  $email = mailchimp_lists_load_email($instance, $wrapper, FALSE);
  switch ($display['type']) {
    case 'mailchimp_lists_subscribe_default':
      if ($email) {
        if (mailchimp_is_subscribed($instance['settings']['mc_list_id'], $email)) {
          $status = "<i>Subscribed</i>";
        }
        else {
          $status = "<i>Not Subscribed</i>";
        }
      }
      else {
        $status = "<i>No Valid Email Address</i>";
      }
      $element['status'] = array(
        '#markup' => $status,
        '#description' => t('@mc_list_description', array('@mc_list_description' => $instance['description'])),
      );
      if ($display['settings']['show_interest_groups']) {
        if (is_array($mc_list['intgroups'])) {
          $element['interest_groups'] = array(
            '#type' => 'fieldset',
            '#title' => t('Interest Groups'),
            '#weight' => 100,
          );
          foreach ($mc_list['intgroups'] as $group) {
            $element['interest_groups'][$group['id']] = array(
              '#markup' => $group['name'],
            );
          }
        }
      }
      break;

    case 'mailchimp_lists_field_subscribe':
      // Display subscription form if accessible.
      if ($email) {
        if (TRUE) {
          // @todo: check for valid permissions here
          $element = drupal_get_form('mailchimp_lists_subscribe_form', $instance, $display['settings'], $entity);
        }
      }
      break;

  }
  return array($element);
}

/**
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

/**
 * Property callback for mailchimp_lists_subscription field.
 */
function mailchimp_lists_subscription_property_info_callback(&$info, $entity_type, $field, $instance, $field_type) {
  $name = $field['field_name'];
  $property = &$info[$entity_type]['bundles'][$instance['bundle']]['properties'][$name];

  $property['type'] = 'mailchimp_lists_subscription';
  $property['getter callback'] = 'entity_metadata_field_verbatim_get';
  $property['setter callback'] = 'entity_metadata_field_verbatim_set';
  //$property['auto creation'] = 'redhen_fields_email_default_values';
  //$property['property info'] = mailchimp_lists_subscription_data_property_info();

  unset($property['query callback']);
}

/**
 * Implements hook_field_presave().
 * 
 * If we have any mailchimp_lists_subscription fields, we handle any changes to
 * them by making appropriate subscription calls.
 */
function mailchimp_lists_field_presave($entity_type, $entity, $field, $instance, $langcode, &$items) {
  if ($field['type'] == 'mailchimp_lists_subscription') {
    $list_id = $instance['settings']['mc_list_id'];
    $entity_wrapper = entity_metadata_wrapper($entity_type, $entity);
    $email = mailchimp_lists_load_email($instance, $entity_wrapper);
    if (!$email) {
      return;
    }
    // Do we have a valid email?
    if (strlen($email)) {
      $function = FALSE;
      $subscribed = mailchimp_is_subscribed($list_id, $email);
      $choices = $entity_wrapper->{$instance['field_name']}->value();
      if ($choices['options']['subscribe'] != $subscribed) {
        // Subscription selection has changed.
        if ($choices['options']['subscribe']) {
          $function = 'add';
        }
        else {
          $function = 'remove';
        }
      }
      elseif($choices['options']['subscribe']) {
        $function = 'update';
      }
      if ($function) {
        if ($function == 'remove') {
          $mergevars = array();
        }
        else {
          $mergevars = _mailchimp_lists_mergevars_populate($instance['settings']['mergefields'], $entity, $instance['entity_type']);
          if (!empty($choices['options']['interest_groups'])) {        
            $mergevars['GROUPINGS'] = _mailchimp_lists_reformat_groupings($choices['options']['interest_groups']);
          }
        // @todo: if email address has changed, do we unsubscribe the old one?
        }
        mailchimp_lists_execute_change($function, $instance['settings']['mc_list_id'], $email, $mergevars);
      }
    }
  }
}
