<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists\Plugin\Field\FieldType\MailchimpListsSubscription.
 */

namespace Drupal\mailchimp_lists\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'mailchimp_lists_subscription' field type.
 *
 * @FieldType (
 *   id = "mailchimp_lists_subscription",
 *   label = @Translation("Mailchimp Subscription"),
 *   description = @Translation("Allows an entity to be subscribed to a Mailchimp list."),
 *   default_widget = "mailchimp_lists_select",
 *   default_formatter = "mailchimp_lists_subscribe_default"
 * )
 */
class MailchimpListsSubscription extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = array(
      'mc_list_id' => array(
        'type' => 'varchar',
        'length' => 32,
        'not null' => FALSE,
      ),
      'double_opt_in' => array(
        'type' => 'int',
        'size' => 'tiny',
        'not null' => TRUE,
        'default' => 0,
      ),
      'send_welcome' => array(
        'type' => 'int',
        'size' => 'tiny',
        'not null' => TRUE,
        'default' => 0,
      ),
    );
    return array(
      'columns' => $columns,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['mc_list_id'] = DataDefinition::create('string')
      ->setLabel(t('MailChimp List'))
      ->setDescription(t('The MailChimp list attached to this field.'));
    $properties['double_opt_in'] = DataDefinition::create('int')
      ->setLabel(t('Double Opt-in'))
      ->setDescription(t('Boolean. True when new subscribers must confirm their subscription.'));
    $properties['send_welcome'] = DataDefinition::create('int')
      ->setLabel(t('Send Welcome Email'))
      ->setDescription(t('Boolean. True when new subscribers are sent a welcome email.'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }
}
