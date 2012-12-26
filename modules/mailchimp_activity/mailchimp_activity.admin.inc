<?php
/**
 * Returns a form for a mailchimp_activity_entity.
 *
 * @param $form
 * @param $form_state
 * @param MailchimpActivityEntity $mailchimp_activity_entity
 * @param string $op
 *
 * @return mixed
 */
function mailchimp_activity_entity_form($form, &$form_state, MailchimpActivityEntity $mailchimp_activity_entity = NULL, $op = 'edit') {
  if ($op == 'clone') {
    $mailchimp_activity_entity->label .= ' (cloned)';
    $mailchimp_activity_entity->name = '';
  }
  $entitynotnull = isset($mailchimp_activity_entity->mailchimp_activity_entity_id);
  $form['#id'] = 'mailchimp_activity_entity_form'; // @todo not sure what's going on here
  $form_state['mailchimp_activity'] = $mailchimp_activity_entity;

  $form['label'] = array(
    '#title' => t('Label'),
    '#type' => 'textfield',
    '#default_value' => $entitynotnull ? $mailchimp_activity_entity->label : '',
    '#description' => t('The human-readable name of this activity entity.'),
    '#required' => TRUE,
    '#weight' => -10,
  );

  $form['name'] = array(
    '#title' => t('Name'),
    '#type' => 'textfield',
    '#default_value' => $entitynotnull ? $mailchimp_activity_entity->name : '',
    '#description' => t('machine name should only contain lowercase letters & underscores'),
    '#disabled' => !empty($mailchimp_activity_entity->name),
    '#required' => TRUE,
    '#weight' => -9,
  );

/*  $form['drupal_entity'] = array(
    '#title' => t('Drupal entity'),
    '#type' => 'fieldset',
    '#attributes' => array(
      'id' => array('edit-drupal-entity'),
    ),
  );*/

  //$form['drupal_entity']['entity_type'] = array(
  $form['entity_type'] = array(
      '#title' => t('Drupal Entity Type'),
    '#type' => 'select',
    '#options' => mailchimp_activity_drupal_entities(),
    '#default_value' => $entitynotnull ? $mailchimp_activity_entity->entity_type : 'user',
    '#required' => TRUE,
    '#description' => t('The specific entity type with an email address to gather mailchimp history for'),
    '#ajax' => array(
      'callback' => 'mailchimp_activity_mapping_form_callback',
      'wrapper' => 'mailchimp_activity_entity_form'
    ),
    '#weight' => -8,
  );

  $form_entity_type = &$form_state['values']['entity_type'];
  if (!$form_entity_type && $entitynotnull) {
    $form_entity_type = $mailchimp_activity_entity->entity_type;
  }
  if ($form_entity_type) {
    $bundles = array('' => t('-- Select --'));
    $info  = entity_get_info($form_entity_type);
    foreach ($info['bundles'] as $key => $bundle) {
      $bundles[$key] = $bundle['label'];
    }
    //$form['drupal_entity']['bundle'] = array(
    $form['bundle'] = array(
      '#title' => t('Drupal Entity Bundle'),
      '#type' => 'select',
      '#required' => TRUE,
      '#description' => t('Select a Drupal entity bundle with an email address to report on.'),
      '#options' => $bundles,
      '#default_value' => $entitynotnull ? $mailchimp_activity_entity->bundle : 0,
      '#weight' => -7,
      '#ajax' => array(
        'callback' => 'mailchimp_activity_mapping_form_callback',
        'wrapper' => 'mailchimp_activity_entity_form'
      ),
    );
  }
  $form_bundle = &$form_state['values']['bundle'];
  if (!$form_bundle && $entitynotnull) {
    $form_bundle = $mailchimp_activity_entity->bundle;
  }
  if ($form_bundle && $form_entity_type) {
    $fields = array('' => t('-- Select --'));
    $props = entity_get_all_property_info($form_entity_type);//$form_bundle); //('node')
    foreach ($props as $key => $prop) {
      $fields[$key] = $props[$key]['label'];
    }
    $form['email_property'] = array(
      '#title' => t('Email Property'),
      '#type' => 'select',
      '#required' => TRUE,
      '#description' => t('Select the field which contains the email address'),
      '#options' => $fields,
      '#default_value' => $entitynotnull ? $mailchimp_activity_entity->email_property : '',
      '#maxlength' => 127,
      '#weight' => -6,
    );
  }

  $form['enabled'] = array(
    '#title' => t('Enabled'),
    '#type' => 'checkbox',
    '#default_value' => $entitynotnull ? $mailchimp_activity_entity->enabled : TRUE,
    '#weight' => -5,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#value' => t('Save Entity'),
    '#type' => 'submit',
  );

  return $form;
}

/**
 * @return array
 */
function mailchimp_activity_drupal_entities() {
  $types = array('' => t('-- Select --'));
  $content_types = field_info_bundles();
  foreach ($content_types as $type => $value) {
    if ($info = entity_get_info($type)) {
      $types[$type] = $info['label'];
    }
  }
  return $types;
}

/**
 * Ajax callback for mailchimp_activity_mapping_form().
 */
function mailchimp_activity_mapping_form_callback($form, $form_state) {
  return $form;
}



/**
 * Implements form_submit
 *
 * @param $form
 * @param $form_state -- $form_state->op determines add vs edit behavior.
 *
 */

function mailchimp_activity_entity_form_submit($form, &$form_state) {
  $values = $form_state['values'];
  $activity_entity = array();
  if ($form_state['op'] == 'add' || $form_state['op'] == 'clone') {
    $activity_entity = entity_create('mailchimp_activity_entity', $values);
  }
  // the form currently only supports adding/cloning (above) and editing.
  // other options necessary? Refreshing?
  else {
    $activity_entity = $form_state['mailchimp_activity_entity'];
    foreach ($values as $key => $val) {
      $activity_entity->{$key} = $val;
    }
  }
  $activity_entity->save();
  $form_state['redirect'] = 'admin/config/services/mailchimp/activity';
}
