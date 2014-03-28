<?php
/**
 * @file
 * Administration pages for mailchimp_campaign module.
 */

/**
 * Returns a form for creating a campaign.
 */
function mailchimp_campaign_campaign_form($form, &$form_state, MailChimpCampaign $campaign = NULL) {
  $form_state['campaign'] = $campaign;
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#description' => t('An internal name to use for this campaign. By default, the campaign subject will be used.'),
    '#required' => FALSE,
    '#default_value' => ($campaign) ? $campaign->mc_data['title'] : ''
  );
  $form['subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#required' => TRUE,
    '#default_value' => ($campaign) ? $campaign->mc_data['subject'] : ''
  );
  $form['list_id'] = array(
    '#type' => 'select',
    '#title' => t('List'),
    '#description' => t('Select the list this campaign should be sent to.'),
    '#options' => _mailchimp_campaign_build_option_list(mailchimp_get_lists()),
    '#default_value' => ($campaign) ? $campaign->mc_data['list_id'] : -1,
    '#required' => TRUE,
    '#ajax' => array(
      'callback' => 'mailchimp_campaign_list_segment_callback',
      'method' => 'replace',
      'wrapper' => 'list_segments_wrapper',
    ),
  );

  if (!empty($form_state['values']['list_id'])) {
    $list_id = $form_state['values']['list_id'];
  } else if ($campaign && $campaign->mc_data) {
    $list_id = $campaign->mc_data['list_id'];
    if (isset($campaign->mc_data['saved_segment']['id'])) {
      $segment_id = $campaign->mc_data['saved_segment']['id'];
    }
  }

  $list_segments = array();
  if (isset($list_id)) {
    $list_segments = mailchimp_campaign_get_list_segments($list_id, 'saved');
  }

  $form['list_segment_id'] = array(
    '#type' => 'select',
    '#title' => t('List Segment'),
    '#description' => t('Select the list segment this campaign should be sent to.'),
    '#options' => _mailchimp_campaign_build_option_list($list_segments, '-- No segment --'),
    '#default_value' => (isset($segment_id))? $segment_id : '',
    '#prefix' => '<div id="list_segments_wrapper">',
    '#suffix' => '</div>',
  );
  $form['from_email'] = array(
    '#type' => 'textfield',
    '#title' => t('From Email'),
    '#description' => t('the From: email address for your campaign message.'),
    '#default_value' => ($campaign) ? $campaign->mc_data['from_email'] : variable_get('site_mail'),
    '#size' => 40,
    '#maxlength' => 255,
    '#required' => TRUE,
  );
  $form['from_name'] = array(
    '#type' => 'textfield',
    '#title' => t('From Name'),
    '#description' => t('the From: name for your campaign message (not an email address)'),
    '#default_value' => ($campaign) ? $campaign->mc_data['from_name'] : variable_get('site_name'),
    '#size' => 40,
    '#maxlength' => 255,
  );
  $form['template_id'] = array(
    '#type' => 'select',
    '#title' => t('Template'),
    '#description' => t('Select a MailChimp user template to use. Due to a limitation in the API, only templates that do not contain repeating sections are available. If empty, the default template will be applied.'),
    '#options' => _mailchimp_campaign_build_option_list(mailchimp_campaign_get_templates()),
    '#default_value' => ($campaign) ? $campaign->mc_data['template_id'] : -1,
    '#ajax' => array(
      'callback' => 'mailchimp_campaign_template_callback',
      'wrapper' => 'content-sections'
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

  $mc_template = NULL;
  if (!empty($form_state['values']['template_id'])) {
    $mc_template = mailchimp_campaign_get_templates($form_state['values']['template_id']);
  }
  else {
    if (($campaign) && $campaign->mc_template) {
      $mc_template = $campaign->mc_template;
    }
  }

  if ($mc_template) {
    if (isset($list_id)) {
      $merge_vars = mailchimp_lists_get_merge_vars(array($list_id));
      if (isset($merge_vars[$list_id])) {
        $form['content']['merge_vars'] = array(
          '#type' => 'fieldset',
          '#title' => 'Merge Tags',
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
          '#children' => _mailchimp_campaign_build_merge_vars_html(
            $merge_vars[$list_id]),
        );
      }
    }
    foreach ($mc_template['info']['default_content'] as $section => $content) {
      // Set the default value and text format to either saved campaign values
      // or defaults coming from the MC template.
      $default_value = $content;
      $format = 'mailchimp_campaign';
      if ($campaign && $campaign->template['html_' . $section]) {
        $default_value = $campaign->template['html_' . $section]['value'];
        $format = $campaign->template['html_' . $section]['format'];
      }
      $form['content']['html_' . $section] = array(
        '#type' => 'text_format',
        '#format' => $format,
        '#title' => drupal_ucfirst($section),
        '#default_value' => $default_value,
      );
      $form['content']['entity_' . $section] = array(
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
      );
      $form['content']['entity_' . $section]['entity'] = array(
        '#type' => 'textfield',
        '#title' => t('Entity'),
        '#description' => t('Choose an entity by title.'),
        '#autocomplete_path' => 'mailchimp_campaign_entities_autocomplete_callback',
      );
    }
  }
  else {
    $form['content']['html'] = array(
      '#type' => 'text_format',
      '#format' => ($campaign) ? $campaign->template['html']['format'] : 'mailchimp_campaign',
      '#title' => t('Content'),
      '#description' => t('The HTML content of the campaign.'),
      '#access' => empty($form_state['values']['template_id']),
      '#default_value' => ($campaign) ? $campaign->template['html']['value'] : ''
    );
  }

  // Message preview
  if (isset($form_state['mailchimp_campaign_campaign_preview'])) {
    $form['preview_wrapper'] = array(
      '#title' => t('Campaign content preview'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['preview_wrapper']['preview'] = array(
      '#markup' => $form_state['mailchimp_campaign_campaign_preview'],
    );
  }

  $form['actions']['preview'] = array(
    '#type' => 'submit',
    '#value' => t('Preview content'),
    '#weight' => 10,
    '#submit' => array('mailchimp_campaign_campaign_preview'),
  );
  $form['actions']['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save as draft'),
  );

  return $form;
}

/**
 * AJAX callback when changing template ID.
 *
 * @param $form
 * @param $form_state
 *
 * @return mixed
 */
function mailchimp_campaign_template_callback($form, $form_state) {
  return $form['content'];
}

/**
 * AJAX callback when changing list ID.
 *
 * @param $form
 * @param $form_state
 *
 * @return mixed
 */
function mailchimp_campaign_list_segment_callback($form, $form_state) {
  return $form['list_segment_id'];
}

/**
 * Preview callback for mailchimp_campaign_campaign_form().
 *
 * @param $form
 * @param $form_state
 */
function mailchimp_campaign_campaign_preview($form, &$form_state) {
  $text = '';
  $content = mailchimp_campaign_render_template($form_state['values']['content']);
  foreach ($content as $key => $section) {
    $text .= "<h3>$key</h3>" . $section;
  }

  $form_state['mailchimp_campaign_campaign_preview'] = $text;
  $form_state['rebuild'] = TRUE;
}

/**
 * Validation callback for mailchimp_campaign_campaign_form().
 *
 * @param $form
 * @param $form_state
 */
function mailchimp_campaign_campaign_form_validate($form, &$form_state) {
  if (!valid_email_address($form_state['values']['from_email'])) {
    form_set_error('from_email', t('Please provide a valid From email address.'));
  }
}

/**
 * Submit handler for mailchimp_campaign_campaign_form().
 *
 * @param $form
 * @param $form_state
 */
function mailchimp_campaign_campaign_form_submit($form, &$form_state) {
  $values = $form_state['values'];
  $options = array(
    'title' => $values['title'],
    'subject' => $values['subject'],
    'list_id' => $values['list_id'],
    'from_email' => $values['from_email'],
    'from_name' => check_plain($values['from_name']),
    'template_id' => $values['template_id']
  );
  $segment_options = NULL;
  if (isset($values['list_segment_id']) && !empty($values['list_segment_id'])) {
    $segment_options = array(
      'saved_segment_id' => $values['list_segment_id'],
    );
  }

  $campaign_id = (isset($form_state['campaign']) ? $form_state['campaign']->mc_campaign_id : NULL);
  mailchimp_campaign_save_campaign($values['content'], $options, $segment_options, $campaign_id);

  cache_clear_all('mailchimp_campaign_campaigns', 'cache');

  $form_state['redirect'][] = 'admin/config/services/mailchimp/campaigns';
}

/**
 * Returns an options list for a given array of items.
 */
function _mailchimp_campaign_build_option_list($list, $no_selection_label = '-- Select --') {
  $options = array('' => t($no_selection_label));
  foreach ($list as $item) {
    $options[$item['id']] = $item['name'];
  }

  return $options;
}

/**
 * Builds a HTML string used to render merge vars on the campaign form.
 *
 * @param array $merge_vars
 *  Array of merge vars. @see mailchimp_lists_get_merge_vars
 * @return string
 *  HTML string containing formatted merge vars.
 */
function _mailchimp_campaign_build_merge_vars_html($merge_vars) {
  $html = '';

  if (!empty($merge_vars)) {
    $html .= '<table>';
    foreach ($merge_vars as $var) {
      $html .= '<tr>'
        . '<td>' . $var['name'] . '</td>'
        . '<td>*|' . $var['tag'] . '|*</td>'
        . '</tr>';
    }
    $html .= '</table>';
  }
  else {
    $html .= t('No custom merge vars exist for the current list.');
  }

  $html .= '<p>' . l(t('Standard MailChimp merge tags are available.'),
      'http://kb.mailchimp.com/article/all-the-merge-tags-cheatsheet',
      array('attributes' => array('target' => '_blank'))) . '</p>';

  return $html;
}

/**
 * List deletion form.
 *
 * @param string $form
 * @param string $form_state
 * @param object $list
 */
function mailchimp_campaign_delete_form($form, &$form_state, MailChimpCampaign $campaign) {
  $form_state['campaign'] = $campaign;
  return confirm_form($form,
    t('Are you sure you want to delete %name?', array('%name' => $campaign->label())),
    'admin/config/services/mailchimp/campaigns/' . $campaign->mc_campaign_id,
    t('This action will delete both the MailChimp campaign and Drupal entity and cannot be undone.'),
    t('Delete campaign')
  );
}

/**
 * Submit handler for mailchimp_campaign_delete_list_form();
 *
 * @param string $form
 * @param string $form_state
 */
function mailchimp_campaign_delete_form_submit($form, &$form_state) {
  $campaign = $form_state['campaign'];
  if (mailchimp_campaign_delete_campaign($campaign->mc_campaign_id)) {
    drupal_set_message(t('Campaign %name has been deleted.',
      array('%name' => $campaign->label()))
    );
  }
  drupal_goto('admin/config/services/mailchimp/campaigns');
}

/**
 * List deletion form.
 *
 * @param string $form
 * @param string $form_state
 * @param object $list
 */
function mailchimp_campaign_send_form($form, &$form_state, MailChimpCampaign $campaign) {
  $form_state['campaign'] = $campaign;
  return confirm_form($form,
    t('Are you sure you want to send %name?', array('%name' => $campaign->label())),
    'admin/config/services/mailchimp/campaigns/' . $campaign->mc_campaign_id,
    t('This action will send the campaign through MailChimp and cannot be undone.'),
    t('Send campaign')
  );
}

/**
 * Submit handler for mailchimp_campaign_delete_list_form();
 *
 * @param string $form
 * @param string $form_state
 */
function mailchimp_campaign_send_form_submit($form, &$form_state) {
  $campaign = $form_state['campaign'];
  mailchimp_campaign_send_campaign($campaign);
  drupal_set_message(t('Campaign %name has been sent.',
      array('%name' => $campaign->label()))
  );
  drupal_goto('admin/config/services/mailchimp/campaigns');
}
