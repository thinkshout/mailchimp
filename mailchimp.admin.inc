<?php
/**
 * implementation of hook_admin_settings
 * @return <type>
 */
function mailchimp_admin_settings() {
  module_load_include('php', 'mailchimp', 'MCAPI.class');

  $form['mailchimp_account_info'] = array(
      '#type'       => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title'      => 'MailChimp API Key',
  );
  $form['mailchimp_account_info']['mailchimp_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Mailchimp API Key'),
      '#required' => TRUE,
      '#default_value' => variable_get('mailchimp_api_key', ''),
      '#description' => t('The API key for your MailChimp account. Get or generate a valid API key at your !apilink.', array('!apilink' => l(t('MailChimp API Dashboard'), 'http://admin.mailchimp.com/account/api')))
  );
  
  // only show the list selection forms if account info provided
  $api_key = variable_get('mailchimp_api_key', FALSE);
  if ($api_key) {
    $q = new MCAPI($api_key);

    if (!$q->errorCode) {
      $lists = $q->lists();
      if (!empty($lists)) {
        $form['mailchimp_lists'] = array(
            '#type'       => 'fieldset',
            '#collapsible' => FALSE,
            '#title'      => 'MailChimp Subscription Lists',
            '#tree'         => TRUE
        );
        foreach ($lists as $list) {
          $saved_list = unserialize(variable_get('mailchimp_lists', NULL));
          $saved_list = $saved_list[$list['id']];

          $form['mailchimp_lists']['mailchimp_list_'. $list['id']] = array(
              '#type'       => 'fieldset',
              '#collapsible' => TRUE,
              '#collapsed' => TRUE,
              '#title'      => $list['name'],
              '#tree'         => TRUE,
          );

          $form['mailchimp_lists']['mailchimp_list_'. $list['id']]['list_id'] = array(
              '#type'       => 'value',
              '#value'      => $list['id'],
          );

          $form['mailchimp_lists']['mailchimp_list_'. $list['id']]['name'] = array(
              '#type'       => 'value',
              '#value'      => $list['name'],
          );

          // Add a row of checkboxes for role enabling.
          $form['mailchimp_lists']['mailchimp_list_'. $list['id']]['roles'] = array(
              '#type'         => 'fieldset',
              '#title'        => t('Roles'),
              '#description'  => t('Choose which roles may subscribe to this list. All users will see the lists they\'re eligble for at the !subscribe and in the subscription block. Lists assigned to the Authenticated role may also apear in the registration form if that option is selected below. Authenticated user may also manage their list settings from their profile.', array('!subscribe' => l(t('newsletter subscription page'), 'mailchimp/subscribe'))),
              '#tree'         => TRUE,
          );

          foreach (user_roles() as $rid => $name) {
            $form['mailchimp_lists']['mailchimp_list_'. $list['id']]['roles'][$rid] = array(
                '#type'           => 'checkbox',
                '#title'          => $name,
                '#default_value'  => ($saved_list &&  !empty($saved_list->roles[$rid])) ? $saved_list->roles[$rid] : FALSE,
            );
          }

          $form['mailchimp_lists']['mailchimp_list_'. $list['id']]['description'] = array(
              '#type'           => 'textarea',
              '#title'          => t('List Description'),
              '#default_value'  => $saved_list ? $saved_list->description : '',
              '#description'    => t('This description will be shown to the user on the list signup and user account settings pages')
          );

          $form['mailchimp_lists']['mailchimp_list_'. $list['id']]['listtype'] = array(
              '#type'           => 'select',
              '#title'          => t('Subscription Method'),
              '#options'        => array(MAILCHIMP_LISTTYPE_OPTIN => "Opt-in", MAILCHIMP_LISTTYPE_OPTOUT => 'Opt-out', MAILCHIMP_LISTTYPE_REQUIRED => 'Required'),
              '#default_value'  => ($saved_list) ?  $saved_list->listtype : MAILCHIMP_LISTTYPE_OPTOUT,
              '#description'    => t('<strong>Opt-in:</strong> Users must sign up to recieve messages.<br/><strong>Opt-out: </strong> Users are automatically signed up but may unsubscribe.<br/><strong>Required: </strong> Users will remain on the list as long as they have an account and cannot unsubscribe.')
          );

          $form['mailchimp_lists']['mailchimp_list_'. $list['id']]['doublein'] = array(
              '#type'           => 'checkbox',
              '#title'          => t('Require subscribers to Double Opt-in'),
              '#default_value'  => $saved_list ? $saved_list->doublein : FALSE,
              '#description'    => t('New subscribers will be sent a link with an email they must follow to confirm their subscription.'),
          );
          $mergevars = $q->listMergeVars($list['id']);
          if ($mergevars) {
          // Merge var fieldset
            $form['mailchimp_lists']['mailchimp_list_'. $list['id']]['mergevars'] = array(
                '#type'         => 'fieldset',
                '#title'        => t('Merge Variables'),
                '#description'  => t('Select Drupal user variables to send to Mailchimp as Merge Variables. Available Drupal variables are any Profile or Token variables for the given user. For more information on Merge Variables, see the !doc', array('!doc' => l(t('Mailchimp Documentation'), 'http://server.iad. liveperson.net/hc/s-31286565/cmd/kbresource/kb-8214439208090042855/view_question!PAGETYPE?sq=merge%2bvariables&sf=101113&sg=0&st=188569&documentid=143258&action=view'))),
                '#tree'         => TRUE,
            );
            $mergeoptions = mailchimp_get_merge_keys();
            foreach ($mergevars as $mergevar) {
              if ($mergevar['tag'] !== 'EMAIL') {
                $form['mailchimp_lists']['mailchimp_list_'. $list['id']]['mergevars'][$mergevar['tag']] = array(
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

        $form['mailchimp_user_register'] = array(
            '#type'           => 'checkbox',
            '#title'          => t('Show subscription options on the user registration form.'),
            '#description'    => t('This will only apply for lists granted to the authenticated role.'),
            '#default_value'  => variable_get('mailchimp_user_register', TRUE)
        );
        $form['mailchimp_user_edit'] = array(
            '#type'           => 'checkbox',
            '#title'          => t('Show Subscription Options on User Edit Screen'),
            '#description'    => t('If set, a tab will be presented for managing newsletter subscriptions when editing an account.'),
            '#default_value'  => variable_get('mailchimp_user_edit', TRUE)
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
        $form['cron']['info'] = array(
          '#value' => t('Updates to Mailchimp can be done when the user is added or edited, or deferred until the next cron run. Deferring these updates to cron will speed up the user interface when editing users. You need to ensure cron is running correctly, and that it keeping up with the number of user edits on your site.<br /><strong>There are currently !count users with pending updates in the queue.</strong>', array('!count'=>db_result(db_query('SELECT COUNT(*) FROM {mailchimp_user} WHERE status = \'%s\'', MAILCHIMP_USERSTATUS_PENDING)))),
        );
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
    else if (FALSE && $q->errorCode === 'INVALID_LOGIN') {
        drupal_set_message(t('Could not login to mailchimp. Please check your username and password.'), "error");
      }
      else if ($q->errorMessage) {
          drupal_set_message(t('Could not retrieve info for mailchimp. The following error was returned: %error.', array('%error' => $q->errorMessage)), "error");
        }
        else {
          drupal_set_message(t('Could not retrieve info for mailchimp for an unknown reason. Please try again later'), "error");
        }
  }

  $form['#validate'][] = 'mailchimp_admin_settings_validate';

  return system_settings_form($form);
}

/**
 * validate the admin settings and serialize the saved lists into objects
 *
 * @param <type> $form
 * @param <type> $form_state
 */
function mailchimp_admin_settings_validate($form, &$form_state) {
  // no lists selected or first time here
  if(empty($form_state['values']['mailchimp_lists'])){
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
