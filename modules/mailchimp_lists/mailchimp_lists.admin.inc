<?php

/**
 * @file
 * mailchimp_lists module admin settings.
 */

function mailchimp_lists_overview_page($form, &$form_state, $list = NULL) {
  $rows = array();  
  return theme('table', $rows);
}

/**
 * Return a form for adding/editing a mailchimp list.
 *
 * @param array $form 
 * @param array $form_state 
 * @param object $list 
 */
function mailchimp_list_add_form($form, &$form_state, $list = NULL) {
  $form = array();
  
  $form['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#description' => t('The label for this list that will appear on forms.'),
    '#size' => 40,
    '#maxlength' => 255,
  );
  
  $form['mailchimp_list_type'] = array(
    '#type' => 'select',
    '#title' => t('Type of list'),
    '#multiple' => FALSE,
    '#description' => t('Select the type of list.'),
    '#options' => array(
      '' => t('-- Select --'),
      MAILCHIMP_LISTTYPE_REQUIRED => t('Required'),
      MAILCHIMP_LISTTYPE_OPTIN => t('Opt-in'),
      MAILCHIMP_LISTTYPE_OPTOUT => t('Opt-out')
    ),
    '#default_value' => -1,
    '#required' => TRUE
  );
  
  $lists = mailchimp_get_lists();
  $options = array('' => t('-- Select --'));
  foreach($lists as $mc_list) {
    $options[$mc_list['id']] = $mc_list['name'];
  }
  
  $form['mailchimp_list'] = array(
    '#type' => 'select',
    '#title' => t('MailChimp List'),
    '#multiple' => FALSE,
    // '#description' => t('Select a MailChimp List.'),
    '#options' => $options,
    '#default_value' => -1,
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'mailchimp_list_get_mergefields_js',
      'wrapper' => 'mailchimp_list_mergefields',
      'method' => 'replace',
      'effect' => 'fade',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Retrieving merge fields for this list.')
      )
    )
  );

  // this is our wrapper for merge fields
  $form['mailchimp_list_mergefields'] = array(
    '#type' => 'fieldset',
    '#title' => t('Merge Fields'),
    '#id' => 'mailchimp_list_mergefields',
    '#description' => t('Merge fields will be loaded based on the selected MailChimp list.')
  );
  
  $form['additional_settings'] = array(
    '#type'         => 'vertical_tabs'
  );
    
  $form['additional_settings']['roles_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Roles')
  );
  $form['additional_settings']['roles_fieldset']['roles'] = array(
    '#type'           => 'checkboxes',
    '#options'  => user_roles(),
    '#description'  => t('Choose which roles may subscribe to this list. All users will see the lists they\'re eligble for at the !subscribe and in the subscription block. Lists assigned to the Authenticated role may also apear in the registration form if that option is selected below. Authenticated user may also manage their list settings from their profile.', array('!subscribe' => l(t('newsletter subscription page'), 'mailchimp/subscribe'))),
    //'#default_value'  => ($saved_list &&  !empty($saved_list->roles[$rid])) ? $saved_list->roles[$rid] : FALSE,
  );
  
  $form['additional_settings']['description_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('List Description')
  );
  $form['additional_settings']['description_fieldset']['description'] = array(
    '#type'           => 'textarea',
    '#default_value'  => $list ? $list->description : '',
    // '#text_format'    => FILTER_FORMAT_DEFAULT,
    '#description'    => t('This description will be shown to the user on the list signup and user account settings pages')
  );
  
  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  
  $form['cancel'] = array(
    '#type' => 'button',
    '#value' => t('Cancel'),
  );
  
  return $form;
}

/**
 * AJAX callback for returning merge form fields for list.
 */
function mailchimp_list_get_mergefields_js($form, $form_state) {
  $form = array();
  $form['additional_settings']['mailchimp_list_mergefields'] = array(
    '#type' => 'fieldset',
    '#title' => t('Merge Fields'),
    '#id' => 'mailchimp_list_mergefields',
    '#collapsible' => TRUE
  );
  
  $list_id = $form_state['values']['mailchimp_list'];
  if (!empty($list_id) && $q = mailchimp_get_api_object()) {
    $result = $q->listMergeVars($list_id);
    if (!empty($result)) {
      foreach($result as $field) {
        $form['additional_settings']['mailchimp_list_mergefields'][$field['name']] = array(
          '#type' => 'select',
          '#title' => $field['name']
        );
      }
    }
    else {
      $form['mailchimp_list_mergefields']['message'] = array(
        '#markup' => t('There are no merge fields configured for this list.')
      );
    }
  }
  
  return $form;
}

/**
 * Implementation of hook_admin_settings().
 */
function mailchimp_lists_admin_settings() {
  // only show the list selection forms if account info provided
  $api_key = variable_get('mailchimp_api_key', FALSE);
  if ($api_key) {
    $q = new MCAPI($api_key);

    if (!$q->errorCode) {
      $lists = $q->lists();
      if ($lists['total'] > 0) {
        $form['mailchimp_lists'] = array(
            '#type'       => 'fieldset',
            '#collapsible' => FALSE,
            '#title'      => 'MailChimp Subscription Lists',
            '#tree'         => TRUE
        );
        foreach ($lists['data'] as $list) {
          $saved_list = unserialize(variable_get('mailchimp_lists', NULL));
          $saved_list = $saved_list[$list['id']];

          $form['mailchimp_lists']['mailchimp_list_' . $list['id']] = array(
              '#type'       => 'fieldset',
              '#collapsible' => TRUE,
              '#collapsed' => TRUE,
              '#title'      => $list['name'],
              '#tree'         => TRUE,
          );

          $form['mailchimp_lists']['mailchimp_list_' . $list['id']]['list_id'] = array(
              '#type'       => 'value',
              '#value'      => $list['id'],
          );

          $form['mailchimp_lists']['mailchimp_list_' . $list['id']]['name'] = array(
              '#type'       => 'value',
              '#value'      => $list['name'],
          );

          // Add a row of checkboxes for role enabling.
          $form['mailchimp_lists']['mailchimp_list_' . $list['id']]['roles'] = array(
              '#type'         => 'fieldset',
              '#title'        => t('Roles'),
              '#description'  => t('Choose which roles may subscribe to this list. All users will see the lists they\'re eligble for at the !subscribe and in the subscription block. Lists assigned to the Authenticated role may also apear in the registration form if that option is selected below. Authenticated user may also manage their list settings from their profile.', array('!subscribe' => l(t('newsletter subscription page'), 'mailchimp/subscribe'))),
              '#tree'         => TRUE,
          );

          foreach (user_roles() as $rid => $name) {
            $form['mailchimp_lists']['mailchimp_list_' . $list['id']]['roles'][$rid] = array(
                '#type'           => 'checkbox',
                '#title'          => $name,
                '#default_value'  => ($saved_list &&  !empty($saved_list->roles[$rid])) ? $saved_list->roles[$rid] : FALSE,
            );
          }

          $form['mailchimp_lists']['mailchimp_list_' . $list['id']]['description'] = array(
              '#type'           => 'textarea',
              '#title'          => t('List Description'),
              '#default_value'  => $saved_list ? $saved_list->description : '',
              '#description'    => t('This description will be shown to the user on the list signup and user account settings pages')
          );

          $form['mailchimp_lists']['mailchimp_list_' . $list['id']]['listtype'] = array(
              '#type'           => 'select',
              '#title'          => t('Subscription Method'),
              '#options'        => array(MAILCHIMP_LISTTYPE_OPTIN => "Opt-in", MAILCHIMP_LISTTYPE_OPTOUT => 'Opt-out', MAILCHIMP_LISTTYPE_REQUIRED => 'Required'),
              '#default_value'  => ($saved_list) ?  $saved_list->listtype : MAILCHIMP_LISTTYPE_OPTOUT,
              '#description'    => t('<strong>Opt-in:</strong> Users must sign up to recieve messages.<br/><strong>Opt-out: </strong> Users are automatically signed up but may unsubscribe.<br/><strong>Required: </strong> Users will remain on the list as long as they have an account and cannot unsubscribe.')
          );

          $form['mailchimp_lists']['mailchimp_list_' . $list['id']]['doublein'] = array(
              '#type'           => 'checkbox',
              '#title'          => t('Require subscribers to Double Opt-in'),
              '#default_value'  => $saved_list ? $saved_list->doublein : FALSE,
              '#description'    => t('New subscribers will be sent a link with an email they must follow to confirm their subscription.'),
          );
          $mergevars = $q->listMergeVars($list['id']);
          if ($mergevars) {
          // Merge var fieldset
            $form['mailchimp_lists']['mailchimp_list_' . $list['id']]['mergevars'] = array(
                '#type'         => 'fieldset',
                '#title'        => t('Merge Variables'),
                '#description'  => t('Select Drupal user variables to send to Mailchimp as Merge Variables. Available Drupal variables are any Profile or Token variables for the given user. For more information on Merge Variables, see the !doc', array('!doc' => l(t('Mailchimp Documentation'), 'http://server.iad. liveperson.net/hc/s-31286565/cmd/kbresource/kb-8214439208090042855/view_question!PAGETYPE?sq=merge%2bvariables&sf=101113&sg=0&st=188569&documentid=143258&action=view'))),
                '#tree'         => TRUE,
            );
            $mergeoptions = mailchimp_get_merge_keys();
            foreach ($mergevars as $mergevar) {
              if ($mergevar['tag'] !== 'EMAIL') {
                $form['mailchimp_lists']['mailchimp_list_' . $list['id']]['mergevars'][$mergevar['tag']] = array(
                    '#type'           => 'select',
                    '#title'          => $mergevar['name'],
                    '#options'        => $mergeoptions,
                    '#default_value'  => $saved_list ? $saved_list->mergevars[$mergevar['tag']] : ''
                );
              }
            }
          }
        }

        $form['mailchimp_messages'] = array(
            '#type'       => 'fieldset',
            '#collapsible' => TRUE,
            '#collapsed' => TRUE,
            '#title'      => 'Anonymous Lists Messaging',
        );
        $form['mailchimp_messages']['mailchimp_subscription_success_message'] = array(
            '#type'           => 'textarea',
            '#title'          => t('Subscription Success Message'),
            '#default_value'  => variable_get('mailchimp_subscription_success_message', t('Thank you, you have been successfully subscribed.'))
        );
        $form['mailchimp_messages']['mailchimp_subscription_failure_message'] = array(
            '#type'           => 'textarea',
            '#title'          => t('Subscription Failure Message'),
            '#default_value'  => variable_get('mailchimp_subscription_failure_message', t('We were unable to subscribe you at this time. Please try again later.'))
        );
        $form['mailchimp_messages']['mailchimp_unsubscription_success_message'] = array(
            '#type'           => 'textarea',
            '#title'          => t('Unsubscription Success Message'),
            '#default_value'  => variable_get('mailchimp_unsubscription_success_message', t('Thank you, you have been successfully unsubscribed.'))
        );
        $form['mailchimp_messages']['mailchimp_unsubscription_failure_message'] = array(
            '#type'           => 'textarea',
            '#title'          => t('Unsubscription Failure Message'),
            '#default_value'  => variable_get('mailchimp_unsubscription_failure_message', t('We were unable to unsubscribe you at this time. Please try again later.'))
        );

        $form['mailchimp_lists_register'] = array(
            '#type'           => 'checkbox',
            '#title'          => t('Show subscription options on the user registration form.'),
            '#description'    => t('This will only apply for lists granted to the authenticated role.'),
            '#default_value'  => variable_get('mailchimp_lists_register', TRUE)
        );
        $form['mailchimp_lists_edit'] = array(
            '#type'           => 'checkbox',
            '#title'          => t('Show Subscription Options on User Edit Screen'),
            '#description'    => t('If set, a tab will be presented for managing newsletter subscriptions when editing an account.'),
            '#default_value'  => variable_get('mailchimp_lists_edit', TRUE)
        );
        $form['mailchimp_interest_groups_user_forms'] = array(
            '#type'           => 'checkbox',
            '#title'          => t('Show Interest Groups on Registration and Account Forms'),
            '#default_value'  => variable_get('mailchimp_interest_groups_user_forms', FALSE),
            '#description'    => t('If set, users will be able to select applicable interest groups when registering or editing their accounts.')
        );

        $form['cron'] = array(
          '#type' => 'fieldset',
          '#title' => t('Mailchimp Cron'),
        );
        // $form['cron']['info'] = array(
        //   '#value' => t('Updates to Mailchimp can be done when the user is added or edited, or deferred until the next cron run. Deferring these updates to cron will speed up the user interface when editing users. You need to ensure cron is running correctly, and that it keeping up with the number of user edits on your site.<br /><strong>There are currently !count users with pending updates in the queue.</strong>', 
        //     array('!count' => db_query('SELECT COUNT(*) FROM {mailchimp_lists} WHERE status = \':status\'', array(':status' => MAILCHIMP_USERSTATUS_PENDING))->fetchField())),
        // );
        $form['cron']['mailchimp_cron'] = array(
            '#type'           => 'checkbox',
            '#title'          => t('Sync Required Lists During Cron'),
            '#default_value'  => variable_get('mailchimp_cron', FALSE),
            '#description'    => t('If this is set, users will be subscribed to the required list during cron runs. Otherwise subscription will take place when a user is added/edited.'),
        );
        // NB: This is the maximum number of users to update, but unless you have required lists,
        // the actual number of users subscribed and therefore updated will likely be signficantly less.
        $form['cron']['mailchimp_batch_limit'] = array(
          '#type' => 'select',
          '#options' => array(
            '1' => '1',
            '10' => '10',
            '25' => '25',
            '50' => '50',
            '75' => '75',
            '100' => '100',
            '250' => '250',
            '500' => '500',
            '750' => '750',
            '1000' => '1000',
            '2500' => '2500',
            '5000' => '5000',
            '7500' => '7500',
            '10000' => '10000',
          ),
          '#title' => t('Batch limit'),
          '#description' => t('Maximum number of users to process in a single cron run. Mailchimp suggest keeping this below 5000-10000. Ignored if updates take place on user add / edit.'),
          '#default_value' => variable_get('mailchimp_batch_limit', 100),
        );
      }
      else {
        drupal_set_message(t('You do not have any valid MailChimp mailing lists.'));
      }
    }
    elseif (FALSE && $q->errorCode === 'INVALID_LOGIN') {
        drupal_set_message(t('Could not login to mailchimp. Please check your username and password.'), "error");
      }
      elseif ($q->errorMessage) {
          drupal_set_message(t('Could not retrieve info for mailchimp. The following error was returned: %error.', array('%error' => $q->errorMessage)), "error");
        }
        else {
          drupal_set_message(t('Could not retrieve info for mailchimp for an unknown reason. Please try again later'), "error");
        }
  }

  $form['#validate'][] = 'mailchimp_lists_admin_settings_validate';

  return system_settings_form($form);
}

/**
 * Validate the admin settings and serialize the saved lists into objects.
 *
 * @param <type> $form
 * @param <type> $form_state
 */
function mailchimp_lists_admin_settings_validate($form, &$form_state) {
  // no lists selected or first time here
  if (empty($form_state['values']['mailchimp_lists'])) {
    return;
  }

  $lists = array();
  foreach ($form_state['values']['mailchimp_lists'] as $form_list) {
    $list = new stdClass();
    $list->id = $form_list['list_id'];
    $list->name = $form_list['name'];
    $list->roles = array_filter($form_list['roles']);
    $list->description = $form_list['description'];
    $list->listtype = $form_list['listtype'];
    $list->doublein = $form_list['doublein'];
    $list->mergevars  = $form_list['mergevars'];
    $lists[$form_list['list_id']] = $list;
  }

  // remove lists from the form_state
  unset($form_state['values']['mailchimp_lists']);
  variable_set('mailchimp_lists', serialize($lists));
}
