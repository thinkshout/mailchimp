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

  $form['drupal_entity'] = array(
    '#title' => t('Drupal Entity'),
    '#type' => 'select',
    '#options' => mailchimp_activity_drupal_entities(),
    '#default_value' => $entitynotnull ? $mailchimp_activity_entity->bundle : 'user',
    '#required' => TRUE,
    '#description' => t('The specific entity bundle with an email address to gather mailchimp history for'),
    '#weight' => -8,
  );

  $form['drupal_entity']['drupal_entity_type'] = array(
    '#title' => t('Drupal Entity Type'),
    '#type' => 'select',

    '#default_value' => $entitynotnull ? $mailchimp_activity_entity->entity_type : 'user',
    '#description' => t('The entity with an email address to gather mailchimp history for'),
    '#weight' => -7,
  );

  $form['email_property'] = array(
    '#type' => 'textfield',
    '#title' => t('email property'),
    '#default_value' => $entitynotnull ? $mailchimp_activity_entity->email_property : '',
    '#maxlength' => 127,
    '#required' => TRUE,
    '#weight' => -6,
  );

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
