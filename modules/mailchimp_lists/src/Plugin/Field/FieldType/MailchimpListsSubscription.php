<?php

namespace Drupal\mailchimp_lists\Plugin\Field\FieldType;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
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
  public static function defaultFieldSettings() {
    return array(
      'show_interest_groups' => 0,
      'interest_groups_label' => '',
      'merge_fields' => array(),
      'unsubscribe_on_delete' => 0,
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = array(
      'subscribe' => array(
        'type' => 'int',
        'size' => 'tiny',
        'not null' => TRUE,
        'default' => 0,
      ),
      'interest_groups' => array(
        'type' => 'text',
        'size' => 'normal',
        'not null' => TRUE,
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
    $properties['subscribe'] = DataDefinition::create('boolean')
      ->setLabel(t('Subscribe'))
      ->setDescription(t('True when an entity is subscribed to a list.'));

    $properties['interest_groups'] = DataDefinition::create('string')
      ->setLabel(t('Interest groups'))
      ->setDescription(t('Interest groups selected for a list.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    $lists = mailchimp_get_lists();
    $options = array('' => t('-- Select --'));
    foreach ($lists as $mc_list) {
      $options[$mc_list->id] = $mc_list->name;
    }

    $field_map = \Drupal::entityManager()->getFieldMap();

    $field_definitions = array();
    foreach ($field_map as $entity_type => $fields) {
      $field_definitions[$entity_type] = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type);
    }

    // Prevent MailChimp lists that have already been assigned to a field
    // appearing as field options.
    foreach ($field_map as $entity_type => $fields) {
      foreach ($fields as $field_name => $field_properties) {
        if ($field_properties['type'] == 'mailchimp_lists_subscription') {
          /* @var $field \Drupal\field\Entity\FieldStorageConfig */
          $field = $field_definitions[$entity_type][$field_name];
          $field_settings = $field->getSettings();

          if (($field_name != $this->getFieldDefinition()->getName()) && isset($field_settings['mc_list_id'])) {
            unset($options[$field_settings['mc_list_id']]);
          }
        }
      }
    }

    $refresh_lists_url = Url::fromRoute('mailchimp_lists.refresh');
    $mailchimp_url = Url::fromUri('https://admin.mailchimp.com', array('attributes' => array('target' => '_blank')));

    $element['mc_list_id'] = array(
      '#type' => 'select',
      '#title' => t('MailChimp List'),
      '#multiple' => FALSE,
      '#description' => t('Available MailChimp lists which are not already
        attached to Mailchimp Subscription Fields. If there are no options,
        make sure you have created a list at @MailChimp first, then @cacheclear.',
        array(
          '@MailChimp' => Link::fromTextAndUrl('MailChimp', $mailchimp_url)->toString(),
          '@cacheclear' => Link::fromTextAndUrl('clear your list cache', $refresh_lists_url)->toString(),
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

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    $mc_list_id = $this->getFieldDefinition()->getSetting('mc_list_id');

    if (empty($mc_list_id)) {
      drupal_set_message(t('Select a list to sync with on the Field Settings tab before configuring the field instance.'), 'error');
      return $element;
    }
    $this->definition;
    $instance_settings = $this->definition->getSettings();

    $element['show_interest_groups'] = array(
      '#title' => "Enable Interest Groups",
      '#type' => "checkbox",
      '#default_value' => $instance_settings['show_interest_groups'],
    );
    $element['interest_groups_label'] = array(
      '#title' => "Interest Groups Label",
      '#type' => "textfield",
      '#default_value' => !empty($instance_settings['interest_groups_label']) ? $instance_settings['interest_groups_label'] : 'Interest Groups',
    );
    $element['merge_fields'] = array(
      '#type' => 'fieldset',
      '#title' => t('Merge Fields'),
      '#description' => t('Multi-value fields will only sync their first value to Mailchimp, as Mailchimp does not support multi-value fields.'),
      '#tree' => TRUE,
    );

    $element['unsubscribe_on_delete'] = array(
      '#title' => "Unsubscribe on deletion",
      '#type' => "checkbox",
      '#description' => t('Unsubscribe entities from this list when they are deleted.'),
      '#default_value' => $instance_settings['unsubscribe_on_delete'],
    );

    $mv_defaults = $instance_settings['merge_fields'];
    $mergevars = mailchimp_get_mergevars(array($mc_list_id));

    $field_config = $this->getFieldDefinition();

    $fields = $this->getFieldmapOptions($field_config->get('entity_type'), $field_config->get('bundle'));
    $required_fields = $this->getFieldmapOptions($field_config->get('entity_type'), $field_config->get('bundle'), TRUE);

    // Prevent this subscription field appearing as a merge field option.
    $field_name = $this->getFieldDefinition()->getName();
    unset($fields[$field_name]);

    $fields_flat = OptGroup::flattenOptions($fields);

    foreach ($mergevars[$mc_list_id] as $mergevar) {
      $default_value = isset($mv_defaults[$mergevar->tag]) ? $mv_defaults[$mergevar->tag] : -1;
      $element['merge_fields'][$mergevar->tag] = array(
        '#type' => 'select',
        '#title' => Html::escape($mergevar->name),
        '#default_value' => array_key_exists($default_value, $fields_flat) ? $default_value : '',
        '#required' => $mergevar->required,
      );
      if (!$mergevar->required || $mergevar->tag === 'EMAIL') {
        $element['merge_fields'][$mergevar->tag]['#options'] = $fields;
        if ($mergevar->tag === 'EMAIL') {
          $element['merge_fields'][$mergevar->tag]['#description'] = t('Any entity with an empty or invalid email address field value will simply be ignored by the Mailchimp subscription system. <em>This is why the Email field is the only required merge field which can sync to non-required fields.</em>');
        }
      }
      else {
        $element['merge_fields'][$mergevar->tag]['#options'] = $required_fields;
        $element['merge_fields'][$mergevar->tag]['#description'] = t("Only 'required' and 'calculated' fields are allowed to be synced with Mailchimp 'required' merge fields.");
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->getValue();
    return (($value === NULL) || ($value === ''));
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    $choices = $this->value;

    // Only act if the field has a value to prevent unintentional unsubscription.
    if (!empty($choices)) {
      mailchimp_lists_process_subscribe_form_choices($choices, $this, $this->getEntity());
    }
  }

  /**
   * Returns the field 'subscribe' value.
   *
   * @return bool
   */
  public function getSubscribe() {
    if (isset($this->values['value'])) {
      return ($this->values['value']['subscribe'] == 1);
    }

    return NULL;
  }

  /**
   * Returns the field 'interest_groups' value.
   *
   * @return array
   */
  public function getInterestGroups() {
    if (isset($this->values['value'])) {
      return $this->values['value']['interest_groups'];
    }

    return NULL;
  }

  /**
   * Get an array with all possible Drupal properties for a given entity type.
   *
   * @param string $entity_type
   *   Name of entity whose properties to list.
   * @param string $entity_bundle
   *   Optional bundle to limit available properties.
   * @param bool $required
   *   Set to TRUE if properties are required.
   * @param string $prefix
   *   Optional prefix for option IDs in the options list.
   * @param string $tree
   *   Optional name of the parent element if this options list is part of a tree.
   *
   * @return array
   *   List of properties that can be used as an #options list.
   */
  private function getFieldmapOptions($entity_type, $entity_bundle = NULL, $required = FALSE, $prefix = NULL, $tree = NULL) {
    $options = array();
    if (!$prefix) {
      $options[''] = t('-- Select --');
    }

    $properties = \Drupal::entityManager()->getFieldDefinitions($entity_type, $entity_bundle);

    /*
      if (isset($entity_bundle)) {
        $info = entity_get_property_info($entity_type);
        $properties = $info['properties'];
        if (isset($info['bundles'][$entity_bundle])) {
          $properties += $info['bundles'][$entity_bundle]['properties'];
        }
      }
    */

    foreach ($properties as $key => $property) {
      $keypath = $prefix ? $prefix . ':' . $key : $key;
      $type = isset($property->type) ? entity_property_extract_innermost_type($property->type) : 'text';
      $is_entity = ($type == 'entity');// || (bool) entity_get_info($type);

      $label = $property->getLabel();
      $bundle = $property->getTargetBundle();

      if ($is_entity) {
        // We offer fields on related entities (useful for field collections).
        // But we only offer 1 level of depth to avoid loops.
        if (!$prefix) {
          $options[$label] = $this->getFieldmapOptions($type, $bundle, $required, $keypath, $label);
        }
      }
      elseif (!$required || $property->isRequired() || $property->isComputed()) {
        //if (isset($property['field']) && $property['field'] && !empty($property['property info'])) {
        //  foreach ($property['property info'] as $sub_key => $sub_prop) {
        //    $label = isset($tree) ? $tree . ' - ' . $property['label'] : $property['label'];
        //    $options[$label][$keypath . ':' . $sub_key] = $sub_prop['label'];
        //  }
        //}
        //else {
        $options[$keypath] = $label;
        //}
      }
    }
    return $options;
  }
}
