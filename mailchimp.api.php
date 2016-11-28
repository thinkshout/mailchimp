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
 * Alter the key for a given api request.
 *
 * @string &$api_key
 *   The MailChimp API key.
 * @array $context
 *   The MailChimp API classname of the API object.
 */
function hook_mailchimp_api_key_alter(&$api_key, $context) {

}

/**
 * Alter the entity options list on the automations entity form.
 *
 * @param array $entity_type_options
 *   The full list of Drupal entities.
 * @param string $automation_entity_label
 *   The label for the automation entity, if it exists.
 */
function hook_mailchimp_automations_entity_options(&$entity_type_options, $automation_entity_label) {

}

/**
 * Perform an action when a related automation entity is created or updated.
 *
 * @param object $automation_entity
 *   The MailchimpAutomationEntity object.
 * @param string $action
 *   'create' or 'update'.
 * @param object $wrapped_entity
 *   The EntityMetadataWrapper for the triggering entity.
 */
function hook_mailchimp_automations_entity_triggered($automation_entity, $action, $wrapper) {

}

/**
 * Alter the email address before a workflow automation is triggered.
 *
 * @param string $email
 *   The email address to which the automation will be sent.
 * @param object $automation_entity
 *   The MailchimpAutomationEntity object.
 * @param object $wrapped_entity
 *   The EntityMetadataWrapper for the triggering entity.
 */
function hook_mailchimp_automations_email_alter(&$email, $automation_entity, $wrapped_entity) {

}

/**
 * Alter mergevars before a workflow automation is triggered.
 *
 * @param array $merge_vars
 *   The merge vars that will be passed to MailChimp.
 * @param object $automation_entity
 *   The MailchimpAutomationEntity object.
 * @param object $wrapped_entity
 *   The EntityMetadataWrapper for the triggering entity.
 */
function hook_mailchimp_automations_mergevars_alter(&$merge_vars, $automation_entity, $wrapped_entity) {

}

/**
 * Perform an action after a successful MailChimp workflow automation.
 *
 * @param object $automation_entity
 *   The MailchimpAutomationEntity object.
 * @param string $email
 *   The email_property value from the MailchimpAutomationEntity.
 * @param object $wrapped_entity
 *   The EntityMetadataWrapper for the triggering entity.
 */
function hook_mailchimp_automations_workflow_email_triggered($automation_entity, $email, $wrapped_entity) {

}
