<?php

/**
 * @file
 * Field hooks.
 */


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
