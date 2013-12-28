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
  foreach ($signup_forms as $signup_forms) {
    $mc_list = mailchimp_get_list($list->mc_list_id);
    $actions = array(
      l(t('Edit'), 'admin/config/services/mailchimp/lists/' . $list->id . '/edit'),
      l(t('Delete'), 'admin/config/services/mailchimp/lists/' . $list->id . '/delete'),
    );
    if (isset($list->settings['cron']) && $list->settings['cron']) {
      $actions[] = l(t('Queue existing'), 'mailchimp/lists/' . $list->id . '/queue_existing');
    }
    $rolelist = $list->settings['roles'];
    unset($rolelist[DRUPAL_ANONYMOUS_RID]);
    $role_display = array();
    foreach ($rolelist as $role_id) {
      $role_display[] = $roles[$role_id];
    }
    $role_display = implode(', ', $role_display);
    $rows[] = array(
      l($list->label(), 'admin/config/services/mailchimp/lists/' . $list->id . '/edit'),
      l($mc_list['name'], 'https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_list['web_id']),
      $list->description,
      $list->settings['required'] ? $role_display : '-No-',
      $list->settings['allow_anonymous'] ? '-Yes-' : '-No-',
      implode(' | ', $actions),
    );
  }
  $table = array(
    'header' => array(
      t('Name'),
      t('Type'),
      t('Description'),
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
