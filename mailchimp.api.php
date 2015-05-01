<?php

/**
 * @file
 * MailChimp module hook definitions.
 */

/**
 * Alter mergevars before they are sent to Mailchimp.
 *
 * @param array $mergevars
 * @param object $entity
 * @param string $entity_type
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform an action when an email address is successfully subscribed to a list.
 *
 * @param $list_id
 * @param $email
 * @param $merge_vars
 *
 * @ingroup mailchimp
 */
function hook_mailchimp_subscribe_success($list_id, $email, $merge_vars) {}

/**
 * Perform an action when an email is successfully unsubscribed from a list.
 *
 * @param $list_id
 * @param $email
 *
 * @ingroup mailchimp
 */
function hook_mailchimp_unsubscribe_success($list_id, $email) {}

/**
 * Perform an action during the firing of a MailChimp webhook.
 *
 * Refer to http://apidocs.mailchimp.com/webhooks for more details.
 *
 * @param string $type
 *   The type of webhook firing.
 * @param array $data
 *   The data contained in the webhook.
 *
 * @ingroup mailchimp
 */
function hook_mailchimp_process_webhook($type, $data) {}

/**
 * @} End of "addtogroup hooks".
 */
// TODO: Is this hook used anywhere?

function hook_mailchimp_lists_mergevars_alter(&$mergevars, $entity, $entity_type) {}
