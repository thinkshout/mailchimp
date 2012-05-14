<?php
/**
 * @file
 * Administration pages for mailchimp_campaigns module.
 */

/**
 * Implements hook_form().
 */
function mailchimp_campaigns_campaign_form($form, &$form_state, $campaign) {
	dpm($campaign);
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
	);

	$form['html'] = array(
	  '#type' => 'text_format',
	  '#title' => t('Content'),
	  '#description' => t('The HTML content of the campaign.'),
	  '#cols' => 60,
	  '#rows' => 5,
		'#format' => 'plain_text',
		'#states' => array(
			'visible' => array(
				':input[name="template_id"]' => array('value' => ''),
			)
		)
	);

	$form['sections'] = array(
	  '#type' => 'fieldset',
	  '#title' => t('Template sections'),
	  '#description' => t('Add content for each template section.'),
	  '#collapsible' => TRUE,
	  '#collapsed' => FALSE,
		'#states' => array(
			'invisible' => array(
				':input[name="template_id"]' => array('value' => ''),
			)
		)
	);
	$form['sections']['html_header'] = array(
	  '#type' => 'text_format',
	  '#title' => t('Header'),
	  '#cols' => 60,
	  '#rows' => 5,
	);
	$form['sections']['html_main'] = array(
	  '#type' => 'text_format',
	  '#title' => t('Main'),
	  '#cols' => 60,
	  '#rows' => 5,
	);
	$form['sections']['html_sidecolumn'] = array(
	  '#type' => 'text_format',
	  '#title' => t('Side Column'),
	  '#cols' => 60,
	  '#rows' => 5,
	);
	$form['sections']['html_footer'] = array(
	  '#type' => 'text_format',
	  '#title' => t('Footer'),
	  '#cols' => 60,
	  '#rows' => 5,
	);

	$form['save'] = array(
	  '#type' => 'submit',
	  '#value' => t('Save'),
	);



  return $form;
}

function mailchimp_campaigns_campaign_form_submit($form, &$form_state) {
	$values = $form_state['values'];
	$options = array(
		'title' => check_plain($values['title']),
		'subject' => check_plain($values['subject']),
		'list_id' => $values['list_id'],
		'from_email' => check_plain($values['from_email']),
		'from_name' => check_plain($values['from_name']),
	);

	$content = array();
	if (!empty($values['template_id'])) {
		$options['template_id'] = $values['template_id'];
		$content['html_header'] = $values['html_header'];
		$content['html_main'] = $values['html_main'];
		$content['html_sidecolumn'] = $values['html_sidecolumn'];
		$content['html_footer'] = $values['html_footer'];
	}
	else {
		$content['html'] = check_markup($values['html']['value'], $values['html']['format']);
	}

	mailchimp_campaigns_create_campaign($content, $options);

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
