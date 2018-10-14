<?php

/**
 * @file
 * Mailchimp module hook definitions.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform an action when an email address is successfully subscribed to a list.
 *
 * @param string $list_id
 *   The Mailchimp list ID.
 * @param string $email
 *   The email address subscribed.
 * @param string $merge_vars
 *   The mergevars used during the subscription.
 *
 * @ingroup mailchimp
 */
function hook_mailchimp_subscribe_success($list_id, $email, $merge_vars) {}

/**
 * Perform an action when an email is successfully unsubscribed from a list.
 *
 * @param string $list_id
 *   The Mailchimp list ID.
 * @param string $email
 *   The email address unsubscribed.
 *
 * @ingroup mailchimp
 */
function hook_mailchimp_unsubscribe_success($list_id, $email) {}

/**
 * Perform an action during the firing of a Mailchimp webhook.
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
 * Alter mergevars before they are sent to Mailchimp.
 *
 * @param array $mergevars
 *   The current mergevars.
 * @param EntityInterface $entity
 *   The entity used to populate the mergevars.
 * @param string $entity_type
 *   The entity type.
 *
 * @ingroup mailchimp
 */
function hook_mailchimp_lists_mergevars_alter(&$mergevars, EntityInterface $entity, $entity_type) {}

/**
 * @} End of "addtogroup hooks".
 */
