<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists\Plugin\Field\FieldType\MailchimpListsSubscription.
 */

namespace Drupal\mailchimp_lists\Plugin\Field\FieldType;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
  public static function defaultStorageSettings() {
    return array(
      'mc_list_id' => '',
      'double_opt_in' => 0,
      'send_welcome' => 0,
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = array(
      'show_interest_groups' => array(
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
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();

    $lists = mailchimp_get_lists();
    $options = array('' => t('-- Select --'));
    foreach ($lists as $mc_list) {
      $options[$mc_list['id']] = $mc_list['name'];
    }
    // TODO: Get all subscription fields; check for assigned MC list IDs.
    /*
    $fields = field_info_fields();
    foreach ($fields as $field) {
      if ($field['type'] == 'mailchimp_lists_subscription') {
        if ($field['id'] != $this_field['id'] && isset($field['settings']['mc_list_id'])) {
          unset($options[$field['settings']['mc_list_id']]);
        }
      }
    }
    */

    $refresh_lists_url = Url::fromRoute('mailchimp_lists.refresh');
    $mailchimp_url = Url::fromUri('https://admin.mailchimp.com');

    $element['mc_list_id'] = array(
      '#type' => 'select',
      '#title' => t('MailChimp List'),
      '#multiple' => FALSE,
      '#description' => t('Available MailChimp lists which are not already
        attached to Mailchimp Subscription Fields. If there are no options,
        make sure you have created a list at !MailChimp first, then !cacheclear.',
        array(
          '!MailChimp' => \Drupal::l('MailChimp', $mailchimp_url),
          '!cacheclear' => \Drupal::l('clear your list cache', $refresh_lists_url),
        )),
      '#options' => $options,
      '#default_value' => $this->getSetting('mc_list_id'),
      '#required' => TRUE,
      '#disabled' => $has_data,
    );
    $element['double_opt_in'] = array(
      '#type' => 'checkbox',
      '#title' => 'Require subscribers to Double Opt-in',
      '#description' => 'New subscribers will be sent a link with an email they must follow to confirm their subscription.',
      '#default_value' => $this->getSetting('double_opt_in'),
      '#disabled' => $has_data,
    );
    $element['send_welcome'] = array(
      '#type' => 'checkbox',
      '#title' => 'Send a welcome email to new subscribers',
      '#description' => 'New subscribers will be sent a welcome email once they are confirmed.',
      '#default_value' => $this->getSetting('send_welcome'),
      '#disabled' => $has_data,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $storage = $form_state->getStorage();

    $mc_list_id = $storage['field']->getSetting('mc_list_id');

    $element['show_interest_groups'] = array(
      '#title' => "Enable Interest Groups",
      '#type' => "checkbox",
      '#default_value' => $this->getSetting('show_interest_groups'),
    );
    $element['interest_groups_title'] = array(
      '#title' => "Interest Groups Label",
      '#type' => "textfield",
      '#default_value' => !empty($this->getSetting('show_interest_groups')) ? $this->getSetting('show_interest_groups') : "Interest Groups",
    );
    $element['mergefields'] = array(
      '#type' => 'fieldset',
      '#title' => t('Merge Fields'),
      '#description' => t('Multi-value fields will only sync their first value to Mailchimp, as Mailchimp does not support multi-value fields.'),
      '#tree' => TRUE,
    );
    $element['unsubscribe_on_delete'] = array(
      '#title' => "Unsubscribe on deletion",
      '#type' => "checkbox",
      '#description' => t('Unsubscribe entities from this list when they are deleted.'),
      '#default_value' => $this->getSetting('unsubscribe_on_delete'),
    );
    $mv_defaults = $this->getSetting('mergefields');
    $mergevars = mailchimp_get_mergevars(array($mc_list_id));

    $fields = mailchimp_lists_fieldmap_options($form['field']['entity_type']['#value'], $form['field']['bundle']['#value']);
    $required_fields = mailchimp_lists_fieldmap_options($form['field']['entity_type']['#value'], $form['field']['bundle']['#value'], TRUE);

    //unset($fields[$field['field_name']]);

    //$fields_flat = options_array_flatten($fields);

    $fields_flat = $fields;

    foreach ($mergevars[$mc_list_id]['merge_vars'] as $mergevar) {
      $default_value = isset($mv_defaults[$mergevar['tag']]) ? $mv_defaults[$mergevar['tag']] : -1;
      $element['mergefields'][$mergevar['tag']] = array(
        '#type' => 'select',
        '#title' => String::checkPlain($mergevar['name']),
        '#default_value' => array_key_exists($default_value, $fields_flat) ? $default_value : '',
        '#required' => $mergevar['req'],
      );
      if (!$mergevar['req'] || $mergevar['tag'] === 'EMAIL') {
        $element['mergefields'][$mergevar['tag']]['#options'] = $fields;
        if ($mergevar['tag'] === 'EMAIL') {
          $element['mergefields'][$mergevar['tag']]['#description'] = t('Any entity with an empty or invalid email address field value will simply be ignored by the Mailchimp subscription system. <em>This is why the Email field is the only required merge field which can sync to non-required fields.</em>');
        }
      }
      else {
        $element['mergefields'][$mergevar['tag']]['#options'] = $required_fields;
        $element['mergefields'][$mergevar['tag']]['#description'] = t("Only 'required' and 'calculated' fields are allowed to be synced with Mailchimp 'required' merge fields.");
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['show_interest_groups'] = DataDefinition::create('integer')
      ->setLabel(t('Show Interest Groups'))
      ->setDescription(t('Boolean. True when list interest groups should be visible.'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // TODO: Temporary. Replace with more general property.
    $value = $this->get('show_interest_groups')->getValue();
    return $value === NULL || $value === '';
  }
}
