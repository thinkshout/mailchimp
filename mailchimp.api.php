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
 *
 * @return NULL
 */

// TODO: Is this hook used anywhere?

function hook_mailchimp_lists_mergevars_alter(&$mergevars, $entity, $entity_type) {}

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
function hook_mailchimp_process_webhook($type, $data) {}
