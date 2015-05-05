<?php

/**
 * @file
 * Field hooks.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function mailchimp_lists_form_field_storage_config_edit_form_alter(&$form, FormStateInterface &$form_state, $form_id) {
  $storage = $form_state->getStorage();

  /* @var $field_config \Drupal\field\Entity\FieldConfig */
  $field_config = $storage['field_config'];

  if ($field_config->field_type == 'mailchimp_lists_subscription') {
    // Hide the cardinality setting:
    $form['cardinality_container']['cardinality_number']['#default_value'] = 1;
    $form['cardinality_container']['#access'] = FALSE;

    $form['#validate'][] = 'mailchimp_lists_form_field_ui_field_edit_form_validate';
  }
}

/**
 * Validation handler for mailchimp_lists_form_field_ui_field_edit_form.
 *
 * Ensure cardinality is set to 1 on mailchimp_lists_subscription fields.
 */
function mailchimp_lists_form_field_ui_field_edit_form_validate(&$form, FormStateInterface &$form_state) {
  $storage = $form_state->getStorage();

  /* @var $field_config \Drupal\field\Entity\FieldConfig */
  $field_config = $storage['field_config'];

  if ($field_config->field_type == 'mailchimp_lists_subscription') {

    if ($form_state->getValue('cardinality_number') != 1) {
      $form_state->setErrorByName('cardinality_number', t('Cardinality on mailchimp lists fields must be set to 1.'));
    }
  }
}

// TODO: Update mailchimp_lists_field_get for D8.

/**
 * Entity field data callback for mailchimp subscription fields.
 */
function mailchimp_lists_field_get($entity, array $options, $name, $entity_type, &$context) {
  $verbatim = entity_metadata_field_verbatim_get($entity, $options, $name, $entity_type, $context);
  // If we're creating or updating field values they shouldn't be mucked with:
  if (isset($verbatim['subscribe'])) {
    return $verbatim;
  }

  $email = mailchimp_lists_load_email($context['instance'], $entity);
  $subscribed = mailchimp_is_subscribed($context['field']['settings']['mc_list_id'], $email);

  $val = array('subscribe' => $subscribed);

  if ($context['instance']['settings']['show_interest_groups']) {

    $mc_list = mailchimp_get_list($context['field']['settings']['mc_list_id']);

    if (isset($mc_list['intgroups'])) {

      $val['interest_groups'] = array();
      $interest_group_settings = mailchimp_interest_groups_form_elements($mc_list, array(), $email);

      foreach ($interest_group_settings as $id => $group) {
        $val['interest_groups'][$id] = $group['#options'];

        foreach ($val['interest_groups'][$id] as $key => &$value) {
          if (!in_array($key, $group['#default_value'])) {
            $value = 0;
          }
        }

      }

    }

  }

  return $val;
}
