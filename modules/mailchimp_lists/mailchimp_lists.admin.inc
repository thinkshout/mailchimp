<?php

/**
 * @file
 * mailchimp_lists module admin settings.
 */

function mailchimp_lists_overview_page() {
  $lists = mailchimp_lists_load_multiple();
  $rows = array();
  foreach($lists as $list) {
    $mc_list = mailchimp_get_list($list->mc_list_id);
    $rows[] = array(
      l($list->label, 'admin/config/services/mailchimp/lists/' . $list->id . '/edit'),
      l($mc_list['name'], 'https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_list['web_id']),
      $list->description,
      $list->list_type);
  }
  $table = array(
    'header' => array(t('Name'), t('MailChimp List'), t('Description'), t('Type')),
    'rows' => $rows
  );

  return theme('table', $table);
}

/**
 * Return a form for adding/editing a mailchimp list.
 *
 * @param array $form
 * @param array $form_state
 * @param object $list
 */
function mailchimp_lists_list_form($form, &$form_state, $list = NULL) {
  $form = array();

  // store the existing list for updating on submit
  if (isset($list)) {
    $form_state['list'] = $list;
  }
  
  $form['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#description' => t('The label for this list that will appear on forms.'),
    '#size' => 40,
    '#maxlength' => 255,
    '#default_value' => $list ? $list->label : '',
  );

  $form['list_type'] = array(
    '#type' => 'select',
    '#title' => t('Type of list'),
    '#multiple' => FALSE,
    '#description' => t('Select the type of list.'),
    '#options' => array(
      '' => t('-- Select --'),
      MAILCHIMP_LISTTYPE_REQUIRED => t('Required'),
      MAILCHIMP_LISTTYPE_OPTIONAL => t('Optional'),
      MAILCHIMP_LISTTYPE_ANONYMOUS => t('Anonymous')
    ),
    '#default_value' => $list ? $list->list_type : -1,
    '#required' => TRUE,
  );

  $lists = mailchimp_get_lists();
  $options = array('' => t('-- Select --'));
  foreach($lists as $mc_list) {
    $options[$mc_list['id']] = $mc_list['name'];
  }
  $form['mc_list_id'] = array(
    '#type' => 'select',
    '#title' => t('MailChimp List'),
    '#multiple' => FALSE,
    '#description' => t('Available MailChimp lists. If this field is empty, create a list at !MailChimp first.',
      array('!MailChimp' => l('MailChimp', 'https://admin.mailchimp.com'))),
    '#options' => $options,
    '#default_value' => $list ? $list->mc_list_id : -1,
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'mailchimp_lists_mergefields_callback',
      'wrapper' => 'mergefields-wrapper',
      'method' => 'replace',
      'effect' => 'fade',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Retrieving merge fields for this list.')
      )
    )
  );

  $form['mergefields'] = array(
    '#prefix' => '<div id="mergefields-wrapper">',
    '#suffix' => '</div>'
  );

  // show merge fields if changing list field or editing existing list
  if (!empty($form_state['values']['mc_list_id']) || isset($list)) {
    $form['mergefields'] = array(
      '#type' => 'fieldset',
      '#title' => t('Merge Fields'),
      '#id' => 'mergefields-wrapper',
      '#collapsible' => TRUE,
      '#tree' => TRUE
    );
    
    $mc_list_id = !empty($form_state['values']['mc_list_id']) ? $form_state['values']['mc_list_id'] : $list->mc_list_id;
    $mc_list = mailchimp_get_list($mc_list_id);
    $mergevars = $mc_list['mergevars'];

    $mergefields = array();
    foreach($mergevars as $field) {
      $mergefields[$field['tag']] = $field['name'];
    }

    if (!empty($mergefields)) {
      foreach($mergefields as $tag => $name) {
        $form['mergefields'][$tag] = array(
          '#type' => 'select',
          '#title' => $name,
          '#default_value' => isset($list->settings['mergefields']) ? $list->settings['mergefields'][$tag] : -1,
          '#options' => mailchimp_lists_get_merge_tokens()
        );
      }
    }
    else {
      $form['mergefields']['message'] = array(
        '#markup' => t('There are no merge fields configured for this list.')
      );
    }
  }
  
  $form['tabs'] = array(
    '#type'         => 'vertical_tabs'
  );

  $form['tabs']['roles_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Roles')
  );
  $form['tabs']['roles_fieldset']['roles'] = array(
    '#type'           => 'checkboxes',
    '#options'  => user_roles(),
    '#description'  => t('Choose which roles may subscribe to this list. All users will see the lists they\'re eligble for at the !subscribe and in the subscription block. Lists assigned to the Authenticated role may also apear in the registration form if that option is selected below. Authenticated user may also manage their list settings from their profile.', array('!subscribe' => l(t('newsletter subscription page'), 'mailchimp/subscribe'))),
    '#default_value'  => ($list &&  !empty($list->settings['roles'])) ? $list->settings['roles'] : array(),
  );

  $form['tabs']['description_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('List Description')
  );
  $form['tabs']['description_fieldset']['description'] = array(
    '#type'           => 'textarea',
    '#default_value'  => $list ? $list->description : '',
    // '#text_format'    => NULL, // @todo: should store this value
    '#description'    => t('This description will be shown to the user on the list signup and user account settings pages')
  );

  $form['tabs']['settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Additional Settings'),
  );

  $form['tabs']['settings']['doublein'] = array(
    '#type'           => 'checkbox',
    '#title'          => t('Require subscribers to Double Opt-in'),
    '#description'    => t('New subscribers will be sent a link with an email they must follow to confirm their subscription.'),
    '#default' => isset($list->settings['doublein']) ? $list->settings['doublein'] : FALSE,
    '#states' => array(
      // Hide this option for required lists
      'invisible' => array(
        ':input[name="list_type"]' => array('value' => MAILCHIMP_LISTTYPE_REQUIRED),
      ),
    ),
  );

  $form['tabs']['settings']['show_register_form'] = array(
    '#type'           => 'checkbox',
    '#title'          => t('Show subscription options on the user registration form.'),
    '#description'    => t('This will only apply for lists granted to an authenticated role.'),
    '#default_value'  => isset($list->settings['show_register_form']) ? $list->settings['show_register_form'] : FALSE,
    '#states' => array(
      // only show for optional lists
      'visible' => array(
        ':input[name="list_type"]' => array('value' => MAILCHIMP_LISTTYPE_OPTIONAL),
      ),
    ),
  );
  $form['tabs']['settings']['show_account_form'] = array(
    '#type'           => 'checkbox',
    '#title'          => t('Show Subscription Options on User Edit Screen'),
    '#description'    => t('If set, a tab will be presented for managing newsletter subscriptions when editing an account.'),
    '#default_value'  => isset($list->settings['show_account_form']) ? $list->settings['show_account_form'] : FALSE,
    '#states' => array(
      // only show for optional lists
      'visible' => array(
        ':input[name="list_type"]' => array('value' => MAILCHIMP_LISTTYPE_OPTIONAL),
      ),
    ),
  );
  $form['tabs']['settings']['include_interest_groups'] = array(
    '#type'           => 'checkbox',
    '#title'          => t('Include interest groups on subscription form.'),
    '#default_value'  => isset($list->settings['include_interest_groups']) ? $list->settings['include_interest_groups'] : FALSE,
    '#description'    => t('If set, users will be able to select applicable interest groups when registering or editing their accounts.'),
    '#states' => array(
      // hide for required groups
      'invisible' => array(
        ':input[name="list_type"]' => array('value' => MAILCHIMP_LISTTYPE_REQUIRED),
      ),
    ),
  );
  $form['tabs']['settings']['cron'] = array(
    '#type'           => 'checkbox',
    '#title'          => t('Sync List During Cron'),
    '#default_value'  => isset($list->settings['cron']) ? $list->settings['cron'] : FALSE,
    '#description'    => t('If this is set, users will be subscribed to the required list during cron runs. Otherwise subscription will take place when a user is added/edited.'),
    '#states' => array(
      // Hide this option for required lists
      'visible' => array(
        ':input[name="list_type"]' => array('value' => MAILCHIMP_LISTTYPE_REQUIRED),
      ),
    ),
  );

  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  $form['cancel'] = array(
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => 'admin/config/services/mailchimp/lists'
  );

  return $form;
}

/**
 * AJAX callback to return fields for a given type.
 */
function mailchimp_lists_mergefields_callback($form, $form_state) {
  return $form['mergefields'];
}

/**
 * mailchimp_lists_list_form() submit handler.
 */
function mailchimp_lists_list_form_submit($form, &$form_state) {
  $list = isset($form_state['list']) ? $form_state['list'] : new stdClass();

  $list->label = $form_state['values']['label'];
  $list->description = $form_state['values']['description'];
  $list->list_type = $form_state['values']['list_type'];
  $list->mc_list_id = $form_state['values']['mc_list_id'];
  $list->settings = array(
    'roles' => $form_state['values']['roles'],
    'mergefields' => isset($form_state['values']['mergefields']) ? $form_state['values']['mergefields'] : NULL,
    'doublein' => $form_state['values']['doublein'],
    'show_register_form' => $form_state['values']['show_register_form'],
    'show_account_form' => $form_state['values']['show_account_form'],
    'include_interest_groups' => $form_state['values']['include_interest_groups'],
    'cron' => $form_state['values']['cron'],
    'cron_batch_limit' => $form_state['values']['cron_batch_limit'],
  );

  if ($ret = mailchimp_lists_save($list)) {
    drupal_set_message(t('List @name has been saved.',
      array('@name' => $list->label)));
    drupal_goto('admin/config/services/mailchimp/lists');
  }
  else {
    drupal_set_message(t('There has been an error saving your list.'), 'error');
  }
}

/**
 * Return an array of available user tokens.
 */
function mailchimp_lists_get_merge_tokens() {
  $out = array(0 => '<none>');

  // grab user tokens
  $token_info = token_info();
  if (!empty($token_info['tokens']['user'])) {
    foreach ($token_info['tokens']['user'] as $key => $token) {
      $out[$key] = t('!field', array('!field' => $token['name']));
    }
  }

  return $out;
}
