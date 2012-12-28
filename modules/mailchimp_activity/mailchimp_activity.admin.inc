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
    '#type' => 'machine_name',
    '#default_value' => $entitynotnull ? $mailchimp_activity_entity->name : '',
    '#description' => t('machine name should only contain lowercase letters & underscores'),
    '#disabled' => !empty($mailchimp_activity_entity->name),
    '#required' => TRUE,
    '#machine_name' => array(
      'exists' => 'mailchimp_activity_load_entities',
      'source' => array('label'),
    ),
    '#weight' => -9,
  );

  $form['drupal_entity'] = array(
    '#title' => t('Drupal Entity Details'),
    '#type' => 'fieldset',
    '#attributes' => array(
      'id' => array('edit-drupal-entity'),
    ),
    '#weight' => -8,
  );
  //prep the entity type list before creating the form item:
  $entity_types = array('' => t('-- Select --'));
  $content_types = field_info_bundles();
  foreach ($content_types as $content_type => $value) {
    if ($info = entity_get_info($content_type)) {
      $entity_types[$content_type] = $info['label'];
    }
  }
  asort($entity_types);
  $form['drupal_entity']['entity_type'] = array(
    '#title' => t('Drupal Entity'),
    '#type' => 'select',
    '#options' => $entity_types,
    '#default_value' => $entitynotnull ? $mailchimp_activity_entity->entity_type : 0,
    '#required' => TRUE,
    '#description' => t('Select an entity type to enable mailchimp history on.'),
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
    //prep the bundle list before creating the form item:
    $bundles = array('' => t('-- Select --'));
    $info  = entity_get_info($form_entity_type);
    foreach ($info['bundles'] as $key => $bundle) {
      $bundles[$key] = $bundle['label'];
    }
    asort($bundles);
    $form['drupal_entity']['bundle'] = array(
      '#title' => t('Entity Bundle'),
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
    $form_bundle = &$form_state['values']['bundle'];
    if (!$form_bundle && $entitynotnull) {
      $form_bundle = $mailchimp_activity_entity->bundle;
    }
    if ($form_bundle) {
      //prep the field & properties list before creating the form item:
      $fields = array('' => t('-- Select --'));
      $props = entity_get_all_property_info($form_entity_type);
      foreach ($props as $key => $prop) {
        $fields[$key] = $props[$key]['label'];
      }
      asort($fields);
      $form['drupal_entity']['email_property'] = array(
        '#title' => t('Email Property'),
        '#type' => 'select',
        '#required' => TRUE,
        '#description' => t('Select the field which contains the email address'),
        '#options' => $fields,
        '#default_value' => $entitynotnull ? $mailchimp_activity_entity->email_property : 0,
        '#maxlength' => 127,
        '#weight' => -6,
      );
    }
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
  $form['#validate'][] = 'mailchimp_activity_entity_form_validate';

  return $form;
}

/**
 * Ajax callback for mailchimp_activity_mapping_form().
 */
function mailchimp_activity_mapping_form_callback($form, &$form_state) {
  if ($form_state['triggering_element']['#name'] == 'bundle') {
    $form_state['values']['email_property'] = 0;
  }
  if ($form_state['triggering_element']['#name'] == 'entity_type') {
    $form_state['values']['bundle'] = 0;
    $form_state['values']['email_property'] = 0;
  }
  return $form;
}

/**
 * @param $form
 * @param $form_state
 */
function mailchimp_activity_entity_form_validate($form, &$form_state) {
  $extant_mc_entities = entity_load('mailchimp_activity_entity');
  $form_id = $form_state['mailchimp_activity_entity']->mailchimp_activity_entity_id;
  $form_bundle = $form_state['values']['bundle'];
  $form_entity_id = $form_state['values']['entity_type'];
  foreach ($extant_mc_entities as $extant_ent) {
    if ($form_bundle == $extant_ent->bundle &&
        $form_entity_id == $extant_ent->entity_type &&
        $form_id != $extant_ent->mailchimp_activity_entity_id) {
      form_set_error('bundle', t('A MailChimp Activity Entity already exists for this Bundle. Either select a different Bundle or edit the <a href = /admin/config/services/mailchimp/activity/manage/'
        . $extant_ent->name . '>existing MailChimp Activity Entity</a> for this bundle.'));
    }
  }
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
  //menu_cache_clear_all(); <- does nothing?
  $form_state['redirect'] = 'admin/config/services/mailchimp/activity';
}
