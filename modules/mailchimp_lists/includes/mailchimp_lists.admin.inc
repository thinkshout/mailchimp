<?php

/**
 * @file
 * mailchimp_lists module admin settings.
 */

/**
 * Administrative display showing existing lists and allowing edits/adds.
 */
function mailchimp_lists_overview_page() {
  $total_webhook_actions = count(mailchimp_lists_default_webhook_actions());

  $mc_lists = mailchimp_get_lists();
  $rows = array();

  foreach ($mc_lists as $mc_list) {
    $enabled_webhook_actions = count(mailchimp_lists_enabled_webhook_actions($mc_list['id']));
    $webhook_status = $enabled_webhook_actions . ' of ' . $total_webhook_actions . ' enabled (' . l(t('update'), 'admin/config/services/mailchimp/lists/' . $mc_list['id'] . '/webhook') . ')';

    $rows[] = array(
      l($mc_list['name'], 'https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_list['web_id']),
      $mc_list['stats']['member_count'],
      $webhook_status,
    );
  }
  $table = array(
    'header' => array(
      t('Name'),
      t('Members'),
      t('Webhook Status'),
    ),
    'rows' => $rows,
  );
  $refresh_link = l(t('Refresh lists from Mailchimp'),
    'admin/config/services/mailchimp/list_cache_clear',
    array('query' => array('destination' => 'admin/config/services/mailchimp/lists')));
  if (empty($mc_lists)) {
    $table['caption'] = $refresh_link;
    drupal_set_message(t('You don\'t have any lists configured in your MailChimp account, (or you haven\'t configured your API key correctly on the Global Settings tab). Head over to !link and create some lists, then come back here and click "Refresh lists from MailChimp"',
      array('!link' => l(t('MailChimp'), 'https://admin.mailchimp.com/lists/'))), 'warning');
  }
  else {
    $options = 'Currently Available MailChimp lists:<i>';
    foreach ($mc_lists as $mc_list) {
      $options .= ' ' . $mc_list['name'] . ',';
    }
    $options = rtrim($options, ',');
    $options .= ".</i><br />" . $refresh_link;
    $table['caption'] = $options;
  }

  return theme('table', $table);
}

/**
 * Administrative display showing existing lists and allowing edits/adds.
 */
function mailchimp_lists_fields_overview_page() {
  $fields = field_info_fields();
  $rows = array();
  foreach ($fields as $field) {
    if ($field['type'] == 'mailchimp_lists_subscription') {
      foreach ($field['bundles'] as $entity_type => $bundles) {
        foreach ($bundles as $bundle) {
          $link = 'admin/config/services/mailchimp/lists/update_mergevars/' . $entity_type . '/' . $bundle . '/' . $field['field_name'];
          $rows[] = array(
            $entity_type,
            $bundle,
            $field['field_name'],
            l(t('Update Mailchimp Mergevar Values'), $link),
          );
        }
      }
    }
  }
  $table = array(
    'header' => array(
      t('Entity Type'),
      t('Bundle'),
      t('Field'),
      t('Batch Update'),
    ),
    'rows' => $rows,
  );

  $table['caption'] = t("This displays a list of all Mailchimp Subscription Fields configured on your system, with a row for each unique Instance of that field.
    To edit each field's settings, go to the Entity Bundle's configuration screen and use the Field UI.
    When entities with MailChimp Subscription Fields are updated, the Merge Variables configured through Field UI are automatically updated if necessary.
    However, if you have existing subscribers on MailChimp and matching Entities on Drupal when you configure your Merge Variables, the existing values are not synced automatically,
    as this could be a slow process. You can manually force updates of all existing Merge Values to existing MailChimp subscribers for each field configuration using the 'Batch Update'
    option on this table. The MailChimp Subscription Field is provided by the Mailchimp Lists (mailchimp_lists) module.<br/><br/>
    The following MailChimp Subscription Field instances were found:");

  return theme('table', $table);
}

/**
 * Webhooks update form.
 */
function mailchimp_lists_webhook_form($form, &$form_state, $list_id) {
  $list = mailchimp_get_list($list_id);
  $form_state['list'] = $list;

  if ($list) {
    $default_webhook_actions = mailchimp_lists_default_webhook_actions();
    $enabled_webhook_actions = mailchimp_lists_enabled_webhook_actions($list_id);

    $form = array();

    $form['webhooks'] = array(
      '#type' => 'fieldset',
      '#title' => t('Choose webhooks to enable for %name. Uncheck to disable.',
        array(
          '%name' => $list['name'],
        )),
      '#tree' => TRUE,
    );

    foreach ($default_webhook_actions as $action => $name) {
      $form['webhooks'][$action] = array(
        '#type' => 'checkbox',
        '#title' => $name,
        '#default_value' => in_array($action, $enabled_webhook_actions),
      );
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Update webhooks',
    );

    return $form;
  }
  return array();
}

/**
 * Submit handler for mailchimp_lists_webhook_form().
 */
function mailchimp_lists_webhook_form_submit($form, &$form_state) {
  $list_id = $form_state['list']['id'];

  $actions = array();
  foreach ($form_state['values']['webhooks'] as $webhook_id => $enable) {
    $actions[$webhook_id] = ($enable === 1);
  }

  $result = FALSE;

  // Enable all checked webhooks for the list.
  if (count($actions) > 0) {
    $result = mailchimp_webhook_add(
      $list_id,
      mailchimp_webhook_url(),
      $actions
    );
  }

  if ($result) {
    drupal_set_message(t('Webhooks for list "%name" have been updated.',
      array(
        '%name' => $form_state['list']['name'],
      )
    ));
  }
  else {
    drupal_set_message(t('Unable to update webhooks for list "%name".',
      array(
        '%name' => $form_state['list']['name'],
      )
    ), 'warning');

  }
  $form_state['redirect'] = 'admin/config/services/mailchimp/lists';
}

/**
 * Update Mergevars Form.
 */
function mailchimp_lists_update_mergevars_form($form, &$form_state, $entity_type, $bundle_name, $field_name) {
  $field = field_info_field($field_name);
  if (!isset($field['settings']['mc_list_id'])) {
    return array();
  }
  $list = mailchimp_get_list($field['settings']['mc_list_id']);
  $form_state['entity_type'] = $entity_type;
  $form_state['bundle_name'] = $bundle_name;
  $form_state['field'] = $field;
  if ($list) {
    return confirm_form($form,
      t('Update Merge Fields on %list for all @entity_type -- @bundle entities?',
        array(
          '%list' => $list['name'],
          '@entity_type' => $entity_type,
          '@bundle' => $bundle_name,
        )),
      'admin/config/services/mailchimp/lists',
      t('This can overwrite values configured directly on your Mailchimp Account.'),
      "Update Merge Values"
    );
  }
  return array();
}

/**
 * Submit handler for mailchimp_lists_update_mergevars_form().
 */
function mailchimp_lists_update_mergevars_form_submit($form, &$form_state) {
  mailchimp_lists_update_member_merge_values($form_state['entity_type'], $form_state['bundle_name'], $form_state['field']);
  $form_state['redirect'] = 'admin/config/services/mailchimp/fields';
}

/**
 * Returns an array of enabled webhook actions.
 *
 * @param $list_id
 *   The MailChimp list ID to return webhook actions for.
 *
 * @return array
 *   An array of enabled webhook action names.
 */
function mailchimp_lists_enabled_webhook_actions($list_id) {
  $enabled_actions = array();

  $webhook_url = mailchimp_webhook_url();

  $webhooks = mailchimp_webhook_get($list_id);
  if ($webhooks) {
    foreach ($webhooks as $webhook) {
      if ($webhook_url == $webhook['url']) {
        foreach ($webhook['actions'] as $action => $enabled) {
          if ($enabled) {
            $enabled_actions[] = $action;
          }
        }
      }
    }
  }

  return $enabled_actions;
}
