<?php

/**
 * @file
 * mailchimp_entities module admin settings.
 */

/**
 * Administrative display showing existing entities and allowing edits/adds.
 */
function mailchimp_entities_overview_page() {
  // @todo refactor
  $entities = mailchimp_entities_load_multiple();
  $rows = array();
  $roles = user_roles();
  foreach ($entities as $entity) {
    $mc_list = mailchimp_get_list($entity->mc_list_id);
    $actions = array(
      l(t('Edit'), 'admin/config/services/mailchimp/entities/' . $entity->id . '/edit'),
      l(t('Delete'), 'admin/config/services/mailchimp/entities/' . $entity->id . '/delete'),
    );
    if (isset($entity->settings['cron']) && $entity->settings['cron']) {
      $actions[] = l(t('Queue existing'), 'mailchimp/lists/' . $entity->id . '/queue_existing');
    }
    $rolelist = $entity->settings['roles'];
    unset($rolelist[DRUPAL_ANONYMOUS_RID]);
    $role_display = array();
    foreach ($rolelist as $role_id) {
      $role_display[] = $roles[$role_id];
    }
    $role_display = implode(', ', $role_display);
    $rows[] = array(
      l($entity->label(), 'admin/config/services/mailchimp/lists/' . $entity->id . '/edit'),
      l($mc_list['name'], 'https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_list['web_id']),
      $entity->description,
      $entity->settings['required'] ? $role_display : '-No-',
      $entity->settings['allow_anonymous'] ? '-Yes-' : '-No-',
      implode(' | ', $actions),
    );
  }
  $table = array(
    'header' => array(
      t('Name'),
      t('MailChimp List'),
      t('Description'),
      t('Required'),
      t('Anonymous Allowed'),
      t('Actions'),
    ),
    'rows' => $rows,
  );
  $mc_lists = mailchimp_get_lists();
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
 * Return a form for adding/editing a mailchimp entity list connection.
 */
function mailchimp_entities_entity_form($form, &$form_state, MailchimpEntity $entity = NULL) {
  $form = array();

  // Store the existing list for updating on submit.
  if (isset($entity)) {
    $form_state['entity'] = $entity;
  }

  $form['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#description' => t('The label for this Entity that appears in the admin UI.'),
    '#size' => 35,
    '#maxlength' => 32,
    '#default_value' => $entity ? $entity->label : '',
    '#required' => TRUE,
  );

  // Machine-readable list name.
  $status = isset($entity->status) && $entity->id && (($entity->status & ENTITY_IN_CODE) || ($entity->status & ENTITY_FIXED));
  $form['name'] = array(
    '#type' => 'machine_name',
    '#default_value' => isset($entity->name) ? $entity->name : '',
    '#maxlength' => 32,
    '#disabled' => $status,
    '#machine_name' => array(
      'exists' => 'mailchimp_entities_load_multiple_by_name',
      'source' => array('label'),
    ),
    '#description' => t('A unique machine-readable name for this list. It must only contain lowercase letters, numbers, and underscores.'),
  );

  $form['description'] = array(
    '#title' => 'Description',
    '#type' => 'textarea',
    '#default_value' => $entity ? $entity->description : '',
    '#rows' => 2,
    '#maxlength' => 255,
    '#description' => t('This description will be shown to the user on the list signup and user account settings pages. (255 characters or less)'),
  );


  $form['drupal_entity'] = array(
    '#title' => t('Drupal entity'),
    '#type' => 'fieldset',
    '#attributes' => array(
      'id' => array('mailchimp-entity-drupal-entity'),
    ),
  );
  //prep the entity type list before creating the form item:
  $entity_types = array('' => t('-- Select --'));
  foreach (entity_get_info() as $entity_type => $info) {
    // ignore MailChimp entity types
    if (strpos($entity_type, 'mailchimp') === FALSE) {
      $entity_types[$entity_type] = $info['label'];
    }
  }
  asort($entity_types);
  $form['drupal_entity']['entity_type'] = array(
    '#title' => t('Entity type'),
    '#type' => 'select',
    '#options' => $entity_types,
    '#default_value' => isset($entity) ? $entity->entity_type : 0,
    '#required' => TRUE,
    '#description' => t('Select an entity type to connect to a mailchimp list.'),
    '#ajax' => array(
      'callback' => 'mailchimp_entities_entity_selection_callback',
      'wrapper' => 'mailchimp-entity-drupal-entity',
      'effect' => 'fade',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Retrieving bundle options for this entity.'),
      ),
    ),
    '#weight' => -8,
  );

  $form_entity_type = & $form_state['values']['entity_type'];
  if (!$form_entity_type && isset($entity)) {
    $form_entity_type = $entity->entity_type;
  }
  if ($form_entity_type) {
    //prep the bundle list before creating the form item:
    $bundles = array('' => t('-- Select --'));
    $info  = entity_get_info($form_entity_type);
    foreach ($info['bundles'] as $key => $bundle) {
      $bundles[$key] = $bundle['label'];
    }
    asort($bundles);
    $form['drupal_entity']['bundle'] = array(
      '#title' => t('Entity bundle'),
      '#type' => 'select',
      '#required' => TRUE,
      '#description' => t('Select a Drupal entity bundle with an email address to connect.'),
      '#options' => $bundles,
      '#default_value' => isset($entity) ? $entity->bundle : 0,
      '#weight' => -7,
      '#ajax' => array(
        'callback' => 'mailchimp_entities_mergefields_callback',
        'wrapper' => 'mergefields-wrapper',
        'progress' => array(
          'type' => 'throbber',
          'message' => t('Retrieving property options for this bundle.'),
        ),
      ),
    );

    $form_bundle = & $form_state['values']['bundle'];
    if (!$form_bundle && isset($entity)) {
      $form_bundle = $entity->bundle;
    }
  }

  $form['mc_list'] = array(
    '#type' => 'fieldset',
    '#title' => t('MailChimp List & Merge Fields'),
    '#collapsible' => TRUE,
    '#collapsed' => isset($entity),
    '#attributes' => array(
      'id' => array('lists-wrapper'),
    ),
  );
  $entities = mailchimp_get_lists();
  $options = array('' => t('-- Select --'));
  foreach ($entities as $mc_list) {
    $options[$mc_list['id']] = $mc_list['name'];
  }
  $form['mc_list']['mc_list_id'] = array(
    '#type' => 'select',
    '#title' => t('MailChimp List'),
    '#multiple' => FALSE,
    '#description' => t('Available MailChimp lists. If this field is empty,
      create a list at !MailChimp first.',
      array('!MailChimp' => l(t('MailChimp'), 'https://admin.mailchimp.com'))),
    '#options' => $options,
    '#default_value' => $entity ? $entity->mc_list_id : -1,
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'mailchimp_entities_mergefields_callback',
      'wrapper' => 'mergefields-wrapper',
      'method' => 'replace',
      'effect' => 'fade',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Retrieving merge fields for this list.'),
      ),
    ),
  );

  $form['mc_list']['mergefields'] = array(
    '#prefix' => '<div id="mergefields-wrapper">',
    '#suffix' => '</div>',
  );

  // Show merge fields if changing list field or editing existing list.
  if (!empty($form_state['values']['mc_list_id']) || isset($entity)) {
    $form['mc_list']['mergefields'] = array(
      '#type' => 'fieldset',
      '#title' => t('Merge Fields'),
      '#id' => 'mergefields-wrapper',
      '#tree' => TRUE,
    );
    $form['form_option_settings']['mergefields_display'] = array(
      '#type' => 'fieldset',
      '#title' => t('Merge Field Form Display Settings'),
      '#description' => t('Select the merge fields to show on registration forms. Required fields are automatically displayed.'),
      '#id' => 'mergefields-display-wrapper',
      '#tree' => TRUE,
      '#weight' => 20,
    );
    $mc_list_id = !empty($form_state['values']['mc_list_id']) ? $form_state['values']['mc_list_id'] : $entity->mc_list_id;
    $mergevars = mailchimp_get_mergevars(array($mc_list_id));

    if ($form_bundle && isset($mergevars[$mc_list_id]) && !empty($mergevars[$mc_list_id])) {
      $fields = mailchimp_entities_email_fieldmap_options($form_entity_type, $form_bundle);
      foreach ($mergevars[$mc_list_id]['merge_vars'] as $mergevar) {
        $default_value = isset($entity->settings['mergefields'][$mergevar['tag']]) ? $entity->settings['mergefields'][$mergevar['tag']] : -1;
        $disabled = FALSE;
        $description = '';
        $form['mc_list']['mergefields'][$mergevar['tag']] = array(
          '#type' => 'select',
          '#title' => $mergevar['name'],
          '#description' => $description,
          '#default_value' => isset($fields[$default_value]) ? $default_value : -1,
          '#disabled' => $disabled,
          '#required' => $mergevar['req'],
          '#options' => $fields,
        );
      }
    }
    else {
      $form['mc_list']['mergefields']['message'] = array(
        '#markup' => t('Mergefields will not display until a bundle is selected.'),
      );
    }


    $form['list_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Settings'),
      '#collapsible' => TRUE,
      '#attributes' => array(
        'id' => array('mailchimp-entity-drupal-entity'),
      ),
    );

    $form['list_settings']['required'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically add all eligible entities. <em>(Formerly "Required List")</em>'),
      '#description' => t('Adds all entities with appropriate settings to the list automatically. This will block them from controlling their subscription status through Drupal. (Email confirmation from Mailchimp may still be required.)'),
      '#default_value' => ($entity && !empty($entity->settings['required'])) ? $entity->settings['required'] : FALSE,
    );
    /* @todo refactor to use any given trait of an entity
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
    '#default_value' => ($entity && !empty($entity->settings['roles'][$rid])) ? $entity->settings['roles'][$rid] : FALSE,
    );
    }
     */
    $form['list_settings']['settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Settings'),
    );

    $form['list_settings']['settings']['doublein'] = array(
      '#type' => 'checkbox',
      '#title' => t('Require subscribers to Double Opt-in'),
      '#description' => t('New subscribers will be sent a link with an email
      they must follow to confirm their subscription.'),
      '#default_value' => isset($entity->settings['doublein']) ? $entity->settings['doublein'] : FALSE,
    );

    $form['list_settings']['settings']['show_register_form'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show subscription options on the user registration form.'),
      '#description' => t('This will only apply for lists granted to an authenticated role. <em>(If the list is set to "Automatically add", only the title and Interest Group options will appear.)</em>'),
      '#default_value' => isset($entity->settings['show_register_form']) ? $entity->settings['show_register_form'] : FALSE,
    );
    $form['list_settings']['settings']['show_account_form'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show subscription options on user edit screen'),
      '#description' => t('If set, a tab will be presented for managing newsletter subscriptions when editing an account. <em>(If the list is set to "Automatically add", the option to unsubscribe will be disabled, but users can configure their Interest Groups.)</em>'),
      '#default_value' => isset($entity->settings['show_account_form']) ? $entity->settings['show_account_form'] : FALSE,
    );
    $form['list_settings']['settings']['cron'] = array(
      '#type' => 'checkbox',
      '#title' => t('Sync List During Cron'),
      '#default_value' => isset($entity->settings['cron']) ? $entity->settings['cron'] : FALSE,
      '#description' => t('If this is set, users will be subscribed to the
      required list during cron runs. Otherwise subscription will take place when a user is added/edited.'),
    );
    $form['list_settings']['settings']['webhooks'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable MailChimp webhooks for this list'),
      '#default_value' => isset($entity->settings['webhooks']) ? $entity->settings['webhooks'] : FALSE,
      '#description' => t("When a user unsubscribes from a list or updates
      their profile outside of Drupal, MailChimp will trigger an event to
      update the user's cached MailChimp member information. This will
      <em>not</em> update any of their Drupal user information."),
    );
  }
  $form['form_option_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Form & Subscribe Block Options'),
  );


  $form['form_option_settings']['form_label'] = array(
    '#type' => 'textfield',
    '#title' => t('List Label'),
    '#description' => t('The label for this list that will appear on forms, either next to a checkbox or above the list merge fields, depending on the type of list.'),
    '#size' => 40,
    '#maxlength' => 64,
    '#default_value' => isset($entity->settings['form_label']) ? $entity->settings['form_label'] : '',
  );
  $form['form_option_settings']['default_register_form_optin'] = array(
    '#type' => 'checkbox',
    '#title' => t('Default to opt-in on registration form.'),
    '#description' => t('This will only apply for lists appearing on the register form.'),
    '#default_value' => isset($entity->settings['default_register_form_optin']) ? $entity->settings['default_register_form_optin'] : FALSE,
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
    '#default_value' => isset($entity->settings['include_interest_groups']) ? $entity->settings['include_interest_groups'] : FALSE,
    '#description' => t('If set, users will be able to select applicable interest groups when registering or editing their accounts.'),
  );
  $form['form_option_settings']['opt_in_interest_groups'] = array(
    '#type' => 'checkbox',
    '#title' => t('Default all groups to opt-in.'),
    '#default_value' => isset($entity->settings['opt_in_interest_groups']) ? $entity->settings['opt_in_interest_groups'] : FALSE,
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
    '#default_value' => isset($entity->settings['interest_groups_label']) ? $entity->settings['interest_groups_label'] : t('Interest Groups'),
    '#description' => t('Label to appear above the interest group options.'),
    '#size' => 40,
    '#maxlength' => 255,
    '#states' => array(
      'enabled' => array(
        ':input[name="include_interest_groups"]' => array('checked' => TRUE),
      ),
    ),
  );

  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  $form['delete'] = array(
    '#type' => 'submit',
    '#value' => t('Delete'),
    '#access' => isset($entity),
    '#submit' => array('mailchimp_entities_delete_entity_submit'),
  );
  $form['cancel'] = array(
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => 'admin/config/services/mailchimp/entities',
  );

  return $form;
}

/**
 * Ajax callback to refresh bundle list.
 */
function mailchimp_entities_entity_selection_callback($form, &$form_state) {
  return $form['drupal_entity'];
}

/**
 * AJAX callback to return fields for a given type.
 */
function mailchimp_entities_mergefields_callback($form, &$form_state) {
  foreach (element_children($form['mc_list']['mergefields']))
  return $form['mc_list']['mergefields'];
}

/**
 * Validation handler for mailchimp_entities_entity_form().
 */
function mailchimp_entities_entity_form_validate($form, &$form_state) {
}

/**
 * Submit handler for mailchimp_entities_entity_form().
 */
function mailchimp_entities_entity_form_submit($form, &$form_state) {
  $entity = isset($form_state['entity']) ? $form_state['entity'] : mailchimp_entity_create();
  $is_new = isset($entity->is_new) && $entity->is_new;

  // Set this value here for use in webhook call below:
  $entity->mc_list_id = $form_state['values']['mc_list_id'];

  $mcapi = mailchimp_get_api_object();
  $webhook_ret = FALSE;
  // If the webhook was enabled before, check to see if it should be disabled:
  if (!empty($entity->settings['webhooks'])) {
    if (!$is_new && !$form_state['values']['webhooks']) {
      $webhook_ret = mailchimp_webhook_delete($entity->mc_list_id, mailchimp_webhook_url());
    }
  }
  // If it wasn't enabled, check to see if it should be:
  else {
    if ($form_state['values']['webhooks']) {
      $webhook_ret = $mailchimp_webhook_add(
        $entity->mc_list_id,
        mailchimp_webhook_url(),
        array('unsubscribe', 'profile', 'cleaned', 'upemail')
      );

      // Handle errors in setting webooks:
      if (!$webhook_ret) {
        drupal_set_message(t('Unable to connect webhooks: @msg',
          array('@msg' => $mcapi->errorMessage)), 'error');
      }
    }
  }
/* @todo: refactor for any given trait.
  // Check for altered roles.
  $added_roles = FALSE;
  $removed_roles = FALSE;
  // We intuit the anonymous RID based on the Allow Anonymous setting:
  $form_state['values']['roles'][DRUPAL_ANONYMOUS_RID] = $form_state['values']['allow_anonymous'];
  if (!$is_new) {
    foreach ($form_state['values']['roles'] as $role_id => $role) {
      $was_on = !empty($form_state['list']->settings['roles'][$role_id]);
      $set_on = !empty($role);
      if ($was_on && !$set_on) {
        $removed_roles = TRUE;
      }
      if (!$was_on && $set_on) {
        $added_roles = TRUE;
      }
      if ($added_roles && $removed_roles) {
        break;
      }
    }
  }
  if ($added_roles || $removed_roles) {
    mailchimp_entities_queue_existing($entity, $removed_roles);
  }
*/
  $entity->label = $form_state['values']['label'];
  $entity->name = $form_state['values']['name'];
  $entity->description = $form_state['values']['description'];
  $entity->settings = array(
    'roles' => array_filter($form_state['values']['roles']),
    'mergefields' => isset($form_state['values']['mergefields']) ? $form_state['values']['mergefields'] : NULL,
    'mergefields_display' => isset($form_state['values']['mergefields_display']) ? $form_state['values']['mergefields_display'] : NULL,
    'doublein' => $form_state['values']['doublein'],
    'show_register_form' => $form_state['values']['show_register_form'],
    'default_register_form_optin' => $form_state['values']['default_register_form_optin'],
    'show_account_form' => $form_state['values']['show_account_form'],
    'form_label' => $form_state['values']['form_label'],
    'include_interest_groups' => $form_state['values']['include_interest_groups'],
    'opt_in_interest_groups' => $form_state['values']['opt_in_interest_groups'],
    'interest_groups_label' => $form_state['values']['interest_groups_label'],
    'cron' => $form_state['values']['cron'],
    'webhooks' => $webhook_ret & $form_state['values']['webhooks'],
    'allow_anonymous' => $form_state['values']['allow_anonymous'],
    'required' => $form_state['values']['required'],
  );

  if ($ret = mailchimp_entities_save($entity)) {
    drupal_set_message(t('List @name has been saved.',
      array('@name' => $entity->label)));
    $form_state['redirect'] = 'admin/config/services/mailchimp/lists';
  }
  else {
    drupal_set_message(t('There has been an error saving your list.'), 'error');
  }
}

/**
 * Submit function for the delete button on the menu item editing form.
 */
function mailchimp_entities_delete_entity_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/config/services/mailchimp/lists/' . $form_state['entity']->id . '/delete';
}

/**
 * Entity deletion form.
 */
function mailchimp_entities_delete_entity_form($form, &$form_state, $entity) {
  $form_state['entity'] = $entity;
  return confirm_form($form,
    t('Are you sure you want to delete %name?', array('%name' => $entity->label())),
    'admin/config/services/mailchimp/lists/' . $entity->id . '/edit',
    t('This action cannot be undone, although it will not have any effect on the MailChimp list.'),
    t('Delete entity->list connection'));
}

/**
 * Submit handler for mailchimp_entities_delete_list_form().
 */
function mailchimp_entities_delete_entity_form_submit($form, &$form_state) {
  $entity = $form_state['entity'];
  mailchimp_entities_delete_multiple(array($entity->id));
  drupal_set_message(t('%name has been deleted.', array('%name' => $entity->label())));
  $form_state['redirect'] = 'admin/config/services/mailchimp/entities';
}


/**
 * Return all possible Drupal properties for a given entity type.
 *
 * @param string $entity_type
 *   Name of entity whose properties to list.
 * @param bool $include_entities
 *   Choose whether or not to include properties of the given entity type that
 *   are themselves also entities.
 *
 * @return array
 *   List of entities that can be used as an #options list.
 */
function mailchimp_entities_email_fieldmap_options($entity_type, $entity_bundle = NULL) {
  $options = array('' => t('-- Select --'));

  $properties = entity_get_all_property_info($entity_type);
  if (isset($entity_bundle)) {
    $info = entity_get_property_info($entity_type);
    $properties = $info['properties'];
    if (isset($info['bundles'][$entity_bundle])) {
      $properties += $info['bundles'][$entity_bundle]['properties'];
    }
  }

  foreach ($properties as $key => $property) {
    $type = isset($property['type']) ? entity_property_extract_innermost_type($property['type']) : 'text';
    $is_entity = ($type == 'entity') || (bool) entity_get_info($type);
    // Leave entities out of this.
    if (!$is_entity) {
      if (isset($property['field']) && $property['field'] && !empty($property['property info'])) {
        foreach ($property['property info'] as $sub_key => $sub_prop) {
          $options[$property['label']][$key . ':' . $sub_key] = $sub_prop['label'];
        }
      }
      else {
        $options[$key] = $property['label'];
      }
    }
  }

  return $options;
}
