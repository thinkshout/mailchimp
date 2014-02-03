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
/*
  $form['list_settings']['required'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatically add all eligible users. <em>(Formerly "Required List")</em>'),
    '#description' => t('Adds all registered users with appropriate Roles to the list automatically. This will block them from controlling their subscription status through Drupal. (Email confirmation from Mailchimp may still be required.)'),
    '#default_value' => ($signup && !empty($signup->settings['required'])) ? $signup->settings['required'] : FALSE,
  );

  $form['list_settings']['roles'] = array(
    '#type' => 'fieldset',
    '#title' => t('Roles'),
    '#tree' => TRUE,
    '#description' => t('<p>Choose which roles may subscribe to this list. All
      users will see the lists they\'re eligible for at the !subscribe and in
      the subscription block. Lists assigned to the Authenticated role may
      also apear in the registration form if that option is selected below.
      Authenticated user may also manage their list settings from their profile.
      The Anonymous role may <em>only</em> be set for free form lists.</p>',
      array(
        '!subscribe' => l(t('newsletter subscription page'),
          'mailchimp/subscribe'),
      )),
  );
  $roles = user_roles();
  foreach ($roles as $rid => $role) {
    $form['list_settings']['roles'][$rid] = array(
      '#type' => 'checkbox',
      '#tree' => TRUE,
      '#title' => $role,
      '#return_value' => $rid,
      '#default_value' => ($signup && !empty($signup->settings['roles'][$rid])) ? $signup->settings['roles'][$rid] : FALSE,
    );
  }
  // We don't show the anonymous role: instead we use the "allow anonymous"
  // setting:
  unset($form['list_settings']['roles'][DRUPAL_ANONYMOUS_RID]);

  $form['list_settings']['settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Settings'),
  );

  $form['list_settings']['settings']['doublein'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require subscribers to Double Opt-in'),
    '#description' => t('New subscribers will be sent a link with an email
      they must follow to confirm their subscription.'),
    '#default_value' => isset($signup->settings['doublein']) ? $signup->settings['doublein'] : FALSE,
  );

  $form['list_settings']['settings']['show_register_form'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show subscription options on the user registration form.'),
    '#description' => t('This will only apply for lists granted to an authenticated role. <em>(If the list is set to "Automatically add", only the title and Interest Group options will appear.)</em>'),
    '#default_value' => isset($signup->settings['show_register_form']) ? $signup->settings['show_register_form'] : FALSE,
  );
  $form['list_settings']['settings']['show_account_form'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show subscription options on user edit screen'),
    '#description' => t('If set, a tab will be presented for managing newsletter subscriptions when editing an account. <em>(If the list is set to "Automatically add", the option to unsubscribe will be disabled, but users can configure their Interest Groups.)</em>'),
    '#default_value' => isset($signup->settings['show_account_form']) ? $signup->settings['show_account_form'] : FALSE,
  );
  $form['list_settings']['settings']['cron'] = array(
    '#type' => 'checkbox',
    '#title' => t('Sync List During Cron'),
    '#default_value' => isset($signup->settings['cron']) ? $signup->settings['cron'] : FALSE,
    '#description' => t('If this is set, users will be subscribed to the
      required list during cron runs. Otherwise subscription will take place when a user is added/edited.'),
  );
  $form['list_settings']['settings']['webhooks'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable MailChimp webhooks for this list'),
    '#default_value' => isset($signup->settings['webhooks']) ? $signup->settings['webhooks'] : FALSE,
    '#description' => t("When a user unsubscribes from a list or updates
      their profile outside of Drupal, MailChimp will trigger an event to
      update the user's cached MailChimp member information. This will
      <em>not</em> update any of their Drupal user information."),
  );

  $form['form_option_settings']['form_label'] = array(
    '#type' => 'textfield',
    '#title' => t('List Label'),
    '#description' => t('The label for this list that will appear on forms, either next to a checkbox or above the list merge fields, depending on the type of list.'),
    '#size' => 40,
    '#maxlength' => 64,
    '#default_value' => isset($signup->settings['form_label']) ? $signup->settings['form_label'] : '',
  );
  $form['form_option_settings']['submit_label'] = array(
    '#type' => 'textfield',
    '#title' => t('Submit Button Label'),
    '#description' => t('The label for the Submit button that will appear on subscription forms that include only this list: generally blocks only.'),
    '#size' => 20,
    '#maxlength' => 20,
    '#default_value' => isset($signup->settings['submit_label']) ? $signup->settings['submit_label'] : 'Submit',
  );
  $form['form_option_settings']['default_register_form_optin'] = array(
    '#type' => 'checkbox',
    '#title' => t('Default to opt-in on registration form.'),
    '#description' => t('This will only apply for lists appearing on the register form.'),
    '#default_value' => isset($signup->settings['default_register_form_optin']) ? $signup->settings['default_register_form_optin'] : FALSE,
    '#states' => array(
      // Hide for required lists.
      'disabled' => array(
        ':input[name="show_register_form"]' => array('checked' => FALSE),
      ),
      'checked' => array(
        ':input[name="required"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['form_option_settings']['include_interest_groups'] = array(
    '#type' => 'checkbox',
    '#title' => t('Include interest groups on subscription form.'),
    '#default_value' => isset($signup->settings['include_interest_groups']) ? $signup->settings['include_interest_groups'] : FALSE,
    '#description' => t('If set, users will be able to select applicable interest groups when registering or editing their accounts.'),
  );
  $form['form_option_settings']['opt_in_interest_groups'] = array(
    '#type' => 'checkbox',
    '#title' => t('Default all groups to opt-in.'),
    '#default_value' => isset($signup->settings['opt_in_interest_groups']) ? $signup->settings['opt_in_interest_groups'] : FALSE,
    '#description' => t('If set, the registration form will check all interest groups by default.'),
    '#states' => array(
      'visible' => array(
        ':input[name="include_interest_groups"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['form_option_settings']['interest_groups_label'] = array(
    '#type' => 'textfield',
    '#title' => t('Interest groups label'),
    '#default_value' => isset($signup->settings['interest_groups_label']) ? $signup->settings['interest_groups_label'] : t('Interest Groups'),
    '#description' => t('Label to appear above the interest group options.'),
    '#size' => 40,
    '#maxlength' => 255,
    '#states' => array(
      'enabled' => array(
        ':input[name="include_interest_groups"]' => array('checked' => TRUE),
      ),
    ),
  );*/

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
