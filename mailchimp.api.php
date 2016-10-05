<?php

/**
 * @file
 * Mailchimp hook definitions.
 */

/**
 * Alter mergevars before they are sent to MailChimp.
 *
 * @param array $mergevars
 * @param object $entity
 * @param string $entity_type
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

/**
 * Perform an action after a subscriber has been subscribed.
 *
 * @string $list_id
 *   MailChimp list id.
 * @string $email
 *   Subscriber email address.
 * @array $merge_vars
 *   Submitted user values.
 */
function hook_mailchimp_subscribe_user($list_id, $email, $merge_vars) {

}

/**
 * Perform an action after a subscriber has been unsubscribed.
 *
 * @string $list_id
 *   MailChimp list id.
 * @string $email
 *   Subscriber email address.
 */
function hook_mailchimp_unsubscribe_user($list_id, $email) {

}

/**
 * Register additional api keys to enable multiple accounts.
 *
 * @return array
 *   array_key is the mailchimp account_id.
 *   array_value is the api key.
 */
function hook_mailchimp_api_key() {
  return array('xxxxxxxxxxx' => 'api_key_here')
}

/**
 * Alter the key for a given api request.
 *
 * @param string &$api_key
 *   The MailChimp API key.
 * @param array $context
 *   The MailChimp API classname of the API object.
 */
function hook_mailchimp_api_key_alter(&$api_key, $context) {

}

/**
 * Perform an action after an api object has been created.
 *
 * @param object $account
 *   The MailChimp API account object.
 * @param array $context
 *   The MailChimp API classname of the API object.
 */
function hook_mailchimp_api_accessed($account, $context) {

}
