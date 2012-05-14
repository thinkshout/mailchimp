<?php
/**
 * @file
 * Administration pages for mailchimp_campaigns module.
 */

/**
 * Implements hook_form().
 */
function mailchimp_campaigns_campaign_form($form, &$form_state, $campaign = NULL) {
	$form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
		'#description' => t('An internal name to use for this campaign. By default, the campaign subject will be used.'),
    '#required' => FALSE,
  );
  $form['subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#required' => TRUE,
  );
	$form['list_id'] = array(
	  '#type' => 'select',
	  '#title' => t('List'),
	  '#description' => t('Select the list this campaign should be sent to.'),
	  '#options' => _mailchimp_campaigns_options_list(mailchimp_get_lists()),
	  '#default_value' => -1,
		'#required' => TRUE
	);
	$form['from_email'] = array(
	  '#type' => 'textfield',
	  '#title' => t('From Email'),
	  '#description' => t('the From: email address for your campaign message.'),
		'#default_value' => variable_get('site_mail'),
	  '#size' => 40,
	  '#maxlength' => 255,
	);
	$form['from_name'] = array(
	  '#type' => 'textfield',
	  '#title' => t('From Name'),
	  '#description' => t('the From: name for your campaign message (not an email address)'),
		'#default_value' => variable_get('site_name'),
	  '#size' => 40,
	  '#maxlength' => 255,
	);
	$form['template_id'] = array(
	  '#type' => 'select',
	  '#title' => t('Template'),
	  '#description' => t('Select a user template to use. If empty, the default template will be applied.'),
	  '#options' => _mailchimp_campaigns_options_list(mailchimp_campaigns_get_templates()),
	  '#default_value' => -1,
    '#ajax' => array(
      'callback' => 'mailchimp_campaigns_template_callback',
      'wrapper' => 'template-sections'
    )
	);

  $form['content'] = array(
    '#id' => 'content-sections',
 	  '#type' => 'fieldset',
 	  '#title' => t('Content sections'),
 	  '#description' => t('The HTML content or, if a template is selected, the content for each section.'),
 	  '#collapsible' => TRUE,
 	  '#collapsed' => FALSE,
    '#tree' => TRUE
 	);

  if (empty($form_state['values']['template_id'])) {
    $form['content']['html'] = array(
   	  '#type' => 'textarea',
   	  '#title' => t('Content'),
   	  '#description' => t('The HTML content of the campaign.'),
       '#access' => empty($form_state['values']['template_id'])
   	);
  }
  else {
    $tid = $form_state['values']['template_id'];
    $api = mailchimp_get_api_object();
    $response = $api->templateInfo($tid);
    foreach ($response['default_content'] as $section => $content) {
      $form['content']['html_' . $section] = array(
        '#type' => 'textarea',
        '#title' =>  drupal_ucfirst($section),
        '#default_value' => $content
      );
    }
  }

	$form['save'] = array(
	  '#type' => 'submit',
	  '#value' => t('Save'),
	);

  return $form;
}

/**
 * AJAX callback when changing template id.
 *
 * @param $form
 * @param $form_state
 *
 * @return mixed
 */
function mailchimp_campaigns_template_callback($form, $form_state) {
  return $form['sections'];
}

/**
 * Submit handler for mailchimp_campaigns_campaign_form().
 *
 * @param $form
 * @param $form_state
 */
function mailchimp_campaigns_campaign_form_submit($form, &$form_state) {
	$values = $form_state['values'];
	$options = array(
		'title' => check_plain($values['title']),
		'subject' => check_plain($values['subject']),
		'list_id' => $values['list_id'],
		'from_email' => check_plain($values['from_email']),
		'from_name' => check_plain($values['from_name']),
    'template_id' => $values['template_id']
	);

	mailchimp_campaigns_create_campaign($values['content'], $options);

	cache_clear_all('mailchimp_campaigns_campaigns', 'cache');

	$form_state['redirect'][] = 'admin/config/services/mailchimp/campaigns';
}

/**
 * Returns an options list for a given array of items.
 */
function _mailchimp_campaigns_options_list($list) {
  $options = array('' => t('-- Select --'));
  foreach ($list as $item) {
    $options[$item['id']] = $item['name'];
  }

  return $options;
}

/**
 * List deletion form.
 *
 * @param string $form
 * @param string $form_state
 * @param object $list
 */
function mailchimp_campaigns_delete_list_form($form, &$form_state, $campaign) {
  $form_state['campaign'] = $campaign;
  return confirm_form($form,
    t('Are you sure you want to delete %name?', array('%name' => $campaign->data['title'])),
    'admin/config/services/mailchimp/campaigns/' . $campaign->mc_campaign_id . '/edit',
    t('This action will delete both the MailChimp campaign and Drupal entity and cannot be undone.'),
    t('Delete campaign')
  );
}

/**
 * Submit handler for mailchimp_campaigns_delete_list_form();
 *
 * @param string $form
 * @param string $form_state
 */
function mailchimp_campaigns_delete_list_form_submit($form, &$form_state) {
  $campaign = $form_state['campaign'];
  mailchimp_campaigns_delete_campaign($campaign->mc_campaign_id);
  drupal_set_message(t('Campaign %name has been deleted.',
		array('%name' => $campaign->data['title'])));
  drupal_goto('admin/config/services/mailchimp/campaigns');
}
