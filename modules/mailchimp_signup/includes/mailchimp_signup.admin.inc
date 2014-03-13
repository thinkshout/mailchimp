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
  foreach ($signup_forms as $signup) {
    $actions = array(
      l(t('Edit'), 'admin/config/services/mailchimp/signup/' . $signup->mcs_id . '/edit'),
      l(t('Delete'), 'admin/config/services/mailchimp/signup/' . $signup->mcs_id . '/delete'),
    );
    switch ($signup->mode) {
      case MAILCHIMP_SIGNUP_BLOCK:
        $modes = l('Block', 'admin/structure/block');
        break;
      case MAILCHIMP_SIGNUP_PAGE:
        $modes = l('Page', $signup->settings['path']);
        break;
      case MAILCHIMP_SIGNUP_BOTH:
        $modes = l('Block', 'admin/structure/block') . ' and ' . l('Page', $signup->settings['path']);
        break;
    }
    $list_labels = array();
    foreach ($signup->mc_lists as $list_id) {
      $list_labels[] = l($mc_lists[$list_id]['name'], 'https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_lists[$list_id]['web_id']);
    }
    $rows[] = array(
      l($signup->label(), 'admin/config/services/mailchimp/signup/' . $signup->mcs_id . '/edit'),
      $modes,
      implode(', ', $list_labels),
      implode(' | ', $actions),
    );
  }
  $table = array(
    'header' => array(
      t('Name'),
      t('Display Mode(s)'),
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
    $list_names = array();
    foreach ($mc_lists as $mc_list) {
      $list_names[] = $mc_list['name'];
    }
    $caption = 'Currently Available MailChimp lists: <i>';
    $caption .= implode(', ', $list_names);
    $caption .= ".</i>";
    $table['caption'] = $caption;
  }

  return theme('table', $table);
}

/**
 * Return a form for adding/editing a mailchimp list.
 */
function mailchimp_signup_signup_form($form, &$form_state, MailchimpSignup $signup = NULL) {
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
    '#default_value' => $signup ? $signup->title : '',
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
  $form['description'] = array(
    '#type' => 'textarea',
    '#title' => 'Description',
    '#default_value' => $signup ? $signup->settings['description'] : '',
    '#rows' => 2,
    '#maxlength' => 500,
    '#description' => t('This description will be shown on the signup form below the title. (500 characters or less)'),
  );
  $mode_defaults = array(
    MAILCHIMP_SIGNUP_BLOCK => array(MAILCHIMP_SIGNUP_BLOCK),
    MAILCHIMP_SIGNUP_PAGE => array(MAILCHIMP_SIGNUP_PAGE),
    MAILCHIMP_SIGNUP_BOTH => array(MAILCHIMP_SIGNUP_BLOCK, MAILCHIMP_SIGNUP_PAGE),
  );
  $form['mode'] = array(
    '#type' => 'checkboxes',
    '#title' => 'Display Mode',
    '#required' => TRUE,
    '#options' => array(
      MAILCHIMP_SIGNUP_BLOCK => 'Block',
      MAILCHIMP_SIGNUP_PAGE => 'Page',
    ),
    '#default_value' => isset($signup) ? $mode_defaults[$signup->mode] : array(),
  );

  $form['settings'] = array(
    '#type' => 'fieldset',
    '#title' => 'Settings',
    '#tree' => TRUE,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  
  $form['settings']['path'] = array(
    '#type' => 'textfield',
    '#title' => 'Page URL',
    '#description' => t('Path to the signup page. ie "newsletter/signup".'),
    '#default_value' => isset($signup) ? $signup->settings['path'] : NULL,
    '#states' => array(
      // Hide unless needed.
      'visible' => array(
        ':input[name="mode[' . MAILCHIMP_SIGNUP_PAGE . ']"]' => array('checked' => TRUE),
      ),
      'required' => array(
        ':input[name="mode[' . MAILCHIMP_SIGNUP_PAGE . ']"]' => array('checked' => TRUE),
      ),
    ),
  );

  $form['settings']['submit_button'] = array(
    '#type' => 'textfield',
    '#title' => 'Submit Button Label',
    '#required' => 'TRUE',
    '#default_value' => isset($signup) ? $signup->settings['submit_button'] : 'Submit',
  );

  $form['settings']['destination'] = array(
    '#type' => 'textfield',
    '#title' => 'Form destination page',
    '#description' => 'Leave blank to stay on the form page.',
    '#default_value' => isset($signup) ? $signup->settings['destination'] : NULL,
  );

  $form['mc_lists_config'] = array(
    '#type' => 'fieldset',
    '#title' => t('MailChimp List Selection & Configuration'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['mc_lists_config']['instructions'] = array(
    '#markup' => "Select the lists to display on this form:<br /><br />",
  );  
  $lists = mailchimp_get_lists();
  $options = array();
  foreach ($lists as $mc_list) {
    $options[$mc_list['id']] = $mc_list['name'];
  }
  $form['mc_lists_config']['mc_lists'] = array(
    '#type' => 'checkboxes',
    '#title' => t('MailChimp Lists'),
    '#description' => t('Which lists to show on your signup form. You can create additional lists at !MailChimp.',
      array('!MailChimp' => l(t('MailChimp'), 'https://admin.mailchimp.com'))),
    '#options' => $options,
    '#default_value' => $signup ? $signup->mc_lists : array(),
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'mailchimp_signup_mergefields_callback',
      'wrapper' => 'mergefields-wrapper',
      'method' => 'replace',
      'effect' => 'fade',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Retrieving merge fields for this list.'),
      ),
    ),
  );

  $form['mc_lists_config']['mergefields'] = array(
    '#prefix' => '<div id="mergefields-wrapper">',
    '#suffix' => '</div>',
  );

  // Show merge fields if changing list field or editing existing list.
  if (!empty($form_state['values']['mc_lists']) || isset($signup)) {
    $form['mc_lists_config']['mergefields'] = array(
      '#type' => 'fieldset',
      '#title' => t('Merge Field Display'),
      '#description' => t('Select the merge fields to show on registration forms. Required fields are automatically displayed.'),
      '#id' => 'mergefields-wrapper',
      '#tree' => TRUE,
      '#weight' => 20,
    );
    $mc_lists = !empty($form_state['values']['mc_lists']) ? $form_state['values']['mc_lists'] : $signup->mc_lists;
    $mergevar_settings = mailchimp_get_mergevars(array_filter($mc_lists));
    $form_state['mergevar_options'] = array();
    foreach ($mergevar_settings as $list_mergevars) {
      foreach ($list_mergevars['merge_vars'] as $mergevar) {
        if ($mergevar['public']) {
          $form_state['mergevar_options'][$mergevar['tag']] = $mergevar;
        }
      }
    }
    foreach ($form_state['mergevar_options'] as $mergevar){
      $form['mc_lists_config']['mergefields'][$mergevar['tag']] = array(
        '#type' => 'checkbox',
        '#title' => $mergevar['name'],
        '#default_value' => isset($signup->settings['mergefields'][$mergevar['tag']]) ? !empty($signup->settings['mergefields'][$mergevar['tag']]) : TRUE,
        '#required' => $mergevar['req'],
        '#disabled' => $mergevar['req'],
      );
    }
  }

    '#type' => 'fieldset',
  );

    '#type' => 'checkbox',
    '#title' => t('Require subscribers to Double Opt-in'),
    '#default_value' => isset($signup->settings['doublein']) ? $signup->settings['doublein'] : FALSE,
  );

    '#type' => 'checkbox',
    '#title' => t('Include interest groups on subscription form.'),
    '#default_value' => isset($signup->settings['include_interest_groups']) ? $signup->settings['include_interest_groups'] : FALSE,
  );

  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  $form['delete'] = array(
    '#type' => 'submit',
    '#value' => t('Delete'),
    '#access' => isset($signup),
    '#submit' => array('mailchimp_signup_signup_delete_submit'),
  );
  $form['cancel'] = array(
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => 'admin/config/services/mailchimp/signup',
  );

  return $form;
}

/**
 * AJAX callback to return fields for a given type.
 */
function mailchimp_signup_mergefields_callback($form, $form_state) {
  return $form['mc_lists_config']['mergefields'];
}

/**
 * Submit handler for mailchimp_lists_list_form().
 */
function mailchimp_signup_signup_form_submit($form, &$form_state) {
  $signup = isset($form_state['signup']) ? $form_state['signup'] : mailchimp_signup_create();
//  $is_new = isset($signup->is_new) && $signup->is_new;
  $mergefields = $form_state['values']['mergefields'];
  foreach ($mergefields as $id => $val) {
    if ($val) {
      $mergefields[$id] = $form_state['mergevar_options'][$id];
    }
  }
  $signup->title = $form_state['values']['title'];
  $signup->name = $form_state['values']['name'];
  $signup->mode = array_sum($form_state['values']['mode']);
  $signup->mc_lists = array_filter($form_state['values']['mc_lists']);
  $signup->settings = $form_state['values']['settings'];
  $signup->settings['mergefields'] = $mergefields;
  $signup->settings['description'] = $form_state['values']['description'];
  if ($signup->save()) {
    drupal_set_message(t('Signup form @name has been saved. If you have added or removed blocks or pages, you may need to clear caches.',
      array('@name' => $signup->name)));
    $form_state['redirect'] = 'admin/config/services/mailchimp/signup';
  }
  else {
    drupal_set_message(t('There has been an error saving your list.'), 'error');
  }
}

/**
 * Signup deletion form.
 */
function mailchimp_signup_signup_delete_form($form, &$form_state, $signup) {
  $form_state['signup'] = $signup;
  return confirm_form($form,
    t('Are you sure you want to delete the signup form %name?', array('%name' => $signup->label())),
    'admin/config/services/mailchimp/signup/' . $signup->mcs_id . '/edit',
    t('This action cannot be undone.'),
    t('Delete Signup form'));
}

/**
 * Submit function for the delete button on the signup overview and edit forms.
 */
function mailchimp_signup_signup_delete_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/config/services/mailchimp/signup/' . $form_state['signup']->mcs_id . '/delete';
}

/**
 * Submit handler for mailchimp_signup_signup_delete_form().
 */
function mailchimp_signup_signup_delete_form_submit($form, &$form_state) {
  $signup = $form_state['signup'];
  mailchimp_signup_delete_multiple(array($signup->mcs_id));
  drupal_set_message(t('%name has been deleted.', array('%name' => $signup->label())));
  $form_state['redirect'] = 'admin/config/services/mailchimp/signup';
}

/**
 * Create a new Mailchimp Signup object.
 *
 * @param array $values
 *   Associative array of values.
 *
 * @return MailchimpSignup
 *   New MailchimpSignup entity.
 */
function mailchimp_signup_create(array $values = array()) {
  return entity_get_controller('mailchimp_signup')->create($values);
}
