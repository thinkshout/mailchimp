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
    foreach ($webhooks as $webhook) {
      if ($webhook_url == $webhook['url']) {
        $enabled = TRUE;
        continue;
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
