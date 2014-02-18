<?php

/**
 * @file
 * Mailchimp hook definitions.
 */

/**
 * Respond to an email being added to a list.
 *
 * @param $list
 *   MailChimp list object.
 * @param $email
 * @param $merge_vars
 */
function hook_mailchimp_subscribe_user($list, $email, $merge_vars) {

}

/**
 * Respond to an email being removed from a list.
 *
 * @param $list
 *   MailChimp list object.
 * @param $email
 */
function hook_mailchimp_unsubscribe_user($list, $email) {

}

/**
 * Alter mergevars before they are sent to Mailchimp.
 *
 * @return NULL
 */
function hook_mailchimp_lists_mergevars_alter(&$mergevars, $entity, $entity_type) {
}

/**
 * Perform an action during the firing of a MailChimp webhook.
 *
 * Refer to http://apidocs.mailchimp.com/webhooks for more details.
 *
 * @string $type
 *   The type of webhook firing.
 * @array $data
 *   The data contained in the webhook.
 */
function hook_mailchimp_process_webhook($type, $data) {

}
