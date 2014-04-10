<?php

/**
 * @file
 * mailchimp_lists module admin settings.
 */

/**
 * Administrative display showing existing lists and allowing edits/adds.
 */
function mailchimp_lists_overview_page() {
  $mc_lists = mailchimp_get_lists();
  $rows = array();
  $webhook_url = mailchimp_webhook_url();
  foreach ($mc_lists as $mc_list) {
    $webhooks = mailchimp_webhook_get($mc_list['id']);
    $enabled = FALSE;
    if ($webhooks) {
      foreach ($webhooks as $webhook) {
        if ($webhook_url == $webhook['url']) {
          $enabled = TRUE;
          continue;
        }
      }
    }
    if ($enabled) {
      // This is a webhook for this Mailchimp Module.
      $webhook_status = "ENABLED (" . l(t('disable'), 'admin/config/services/mailchimp/lists/' . $mc_list['id'] . '/webhook/disable') . ')';
    }
    else {
      $webhook_status = "disabled (" . l(t('enable'), 'admin/config/services/mailchimp/lists/' . $mc_list['id'] . '/webhook/enable') . ')';
    }
    $rows[] = array(
      l($mc_list['name'], 'https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_list['web_id']),
      $mc_list['stats']['member_count'],
      $webhook_status,
    );

  }
  $table = array(
    'header' => array(
      t('Name'),
      t('Members'),
      t('Webhook Status'),
    ),
    'rows' => $rows,
  );
  $refresh_link = l(t('Refresh lists from Mailchimp'),
    'admin/config/services/mailchimp/list_cache_clear',
    array('query' => array('destination' => 'admin/config/services/mailchimp/lists')));
  if (empty($mc_lists)) {
    $table['caption'] = $refresh_link;
    drupal_set_message(t('You don\'t have any lists configured in your MailChimp account, (or you haven\'t configured your API key correctly on the Global Settings tab). Head over to !link and create some lists, then come back here and click "Refresh lists from MailChimp"',
      array('!link' => l(t('MailChimp'), 'https://admin.mailchimp.com/lists/'))), 'warning');
  }
  else {
    $options = 'Currently Available MailChimp lists:<i>';
    foreach ($mc_lists as $mc_list) {
      $options .= ' ' . $mc_list['name'] . ',';
    }
    $options = rtrim($options, ',');
    $options .= ".</i><br />" . $refresh_link;
    $table['caption'] = $options;
  }

  return theme('table', $table);
}

/**
 * Webhooks Toggle form.
 */
function mailchimp_lists_webhook_form($form, &$form_state, $list_id, $action) {
  $list = mailchimp_get_list($list_id);
  $form_state['list'] = $list;
  $form_state['action'] = $action;
  if ($list) {
    switch ($action) {
      case "enable":
        break;

      case "disable":
        break;

      default:
        return array();
    }
    $label = $action . " webhooks";
    return confirm_form($form,
      t('Are you sure you want to &action webhooks for %name?',
        array(
          '&action' => $action,
          '%name' => $list['name'],
        )),
      'admin/config/services/mailchimp/lists',
      t('You can change this setting back from the mailchimp administrative UI.'),
      $label
    );
  }
  return array();
}

/**
 * Submit handler for mailchimp_lists_webhook_form().
 *
 * @param array $form
 * @param array $form_state
 */
function mailchimp_lists_webhook_form_submit($form, &$form_state) {
  $list_id = $form_state['list']['id'];
  switch ($form_state['action']) {
    case 'enable':
      $result = mailchimp_webhook_add(
        $list_id,
        mailchimp_webhook_url(),
        array('unsubscribe', 'profile', 'cleaned', 'upemail')
      );
      break;

    case 'disable':
      $result = mailchimp_webhook_delete(
        $list_id,
        mailchimp_webhook_url()
      );
      break;

    default:
      $result = FALSE;
      break;

  }
  if ($result) {
    drupal_set_message(t('Webhooks for list "%name" have been %action.',
      array(
        '%name' => $form_state['list']['name'],
        '%action' => $form_state['action'] . 'd',
      )
    ));
  }
  else {
    drupal_set_message(t('Unable to perform webhook action "%action" for list "%name".',
      array(
        '%name' => $form_state['list']['name'],
        '%action' => $form_state['action'],
      )
    ), 'warning');

  }
  $form_state['redirect'] = 'admin/config/services/mailchimp/lists';
}
