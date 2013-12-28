<?php

/**
 * @file
 * mailchimp_signup module admin settings.
 */

/**
 * Administrative display showing existing signup forms and allowing edits/adds.
 */
function mailchimp_signup_overview_page() {
  $signup_forms = mailchimp_signup_load_multiple();
  $rows = array();
  $mc_lists = mailchimp_get_lists();
  foreach ($signup_forms as $signup_forms) {
    $mc_list = mailchimp_get_list($list->mc_list_id);
    $actions = array(
      l(t('Edit'), 'admin/config/services/mailchimp/lists/' . $list->id . '/edit'),
      l(t('Delete'), 'admin/config/services/mailchimp/lists/' . $list->id . '/delete'),
    );
    if (isset($list->settings['cron']) && $list->settings['cron']) {
      $actions[] = l(t('Queue existing'), 'mailchimp/lists/' . $list->id . '/queue_existing');
    }
    $rolelist = $list->settings['roles'];
    unset($rolelist[DRUPAL_ANONYMOUS_RID]);
    $role_display = array();
    foreach ($rolelist as $role_id) {
      $role_display[] = $roles[$role_id];
    }
    $role_display = implode(', ', $role_display);
    $rows[] = array(
      l($list->label(), 'admin/config/services/mailchimp/lists/' . $list->id . '/edit'),
      l($mc_list['name'], 'https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_list['web_id']),
      $list->description,
      $list->settings['required'] ? $role_display : '-No-',
      $list->settings['allow_anonymous'] ? '-Yes-' : '-No-',
      implode(' | ', $actions),
    );
  }
  $table = array(
    'header' => array(
      t('Name'),
      t('Type'),
      t('Description'),
      t('MailChimp Lists'),
      t('Actions'),
    ),
    'rows' => $rows,
  );
  if (empty($mc_lists)) {
    drupal_set_message(t('You don\'t have any lists configured in your MailChimp account, (or you haven\'t configured your API key correctly on the Global Settings tab). Head over to !link and create some lists, then come back here and click "Refresh lists from MailChimp!"',
      array('!link' => l(t('MailChimp'), 'https://admin.mailchimp.com/lists/'))), 'warning');
  }
  else {
    $options = 'Currently Available MailChimp lists:<i>';
    foreach ($mc_lists as $mc_list) {
      $options .= ' ' . $mc_list['name'] . ',';
    }
    $options = rtrim($options, ',');
    $options .= ".</i>";
    $table['caption'] = $options;
  }

  return theme('table', $table);
}

/**
 * Return a form for adding/editing a mailchimp list.
 */
function mailchimp_signup_signup_form($form, &$form_state, MailchimpSignup $signup = NULL) {
  $form = array();

  // Store the existing list for updating on submit.
  if (isset($signup)) {
    $form_state['signup'] = $signup;
  }

  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#description' => t('The title for this signup form.'),
    '#size' => 35,
    '#maxlength' => 32,
    '#default_value' => $signup ? $signup->label : '',
    '#required' => TRUE,
  );

  // Machine-readable list name.
  $status = isset($signup->status) && $signup->mcs_id && (($signup->status & ENTITY_IN_CODE) || ($signup->status & ENTITY_FIXED));
  $form['name'] = array(
    '#type' => 'machine_name',
    '#default_value' => isset($signup->name) ? $signup->name : '',
    '#maxlength' => 32,
    '#disabled' => $status,
    '#machine_name' => array(
      'exists' => 'mailchimp_signup_load_multiple_by_name',
      'source' => array('title'),
    ),
    '#description' => t('A unique machine-readable name for this list. It must only contain lowercase letters, numbers, and underscores.'),
  );

  $form['mc_lists_config'] = array(
    '#type' => 'fieldset',
    '#title' => t('MailChimp List Selection & Configuration'),
    '#collapsible' => TRUE,
    '#collapsed' => isset($signup),
  );
  $form['mc_lists_config']['instructions'] = array(
    '#markup' => "Select the lists to display on this form:<br /><br />",
  );

  $lists = mailchimp_get_lists();
  foreach ($lists as $mc_list) {
    $default = FALSE;
    if (isset($signup)) {
      if (isset($signup->mc_lists[$mc_list['id']])){
        $default = TRUE;
      }
    }
    $form['mc_lists_config'][$mc_list['id']] = array(
      '#type' => 'checkbox',
      '#title' => $mc_list['name'],
      '#return_value' => $mc_list['id'],
      '#default_value' => $default,
      '#tree' => TRUE,
    );

    $form['mc_lists_config'][$mc_list['id'].'label'] = array(
      '#type' => 'textfield',
      '#title' => 'Label',
      '#description' => t('The label for this list that will appear on forms, either next to a checkbox or above the list merge fields, depending on the type of list.'),
      '#size' => 40,
      '#maxlength' => 64,
      '#default_value' => $default ? $signup->mc_lists[$mc_list['id']]['label'] : $mc_list['name'],
      '#states'=> array(
        'visible' => array(
          ':input[name="' . $mc_list['id'] . '"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  $form['delete'] = array(
    '#type' => 'submit',
    '#value' => t('Delete'),
    '#access' => isset($signup),
    '#submit' => array('mailchimp_lists_list_delete_submit'),
  );
  $form['cancel'] = array(
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => 'admin/config/services/mailchimp/lists',
  );

  return $form;
}
