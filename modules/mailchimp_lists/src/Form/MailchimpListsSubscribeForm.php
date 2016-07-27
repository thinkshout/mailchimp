<?php

namespace Drupal\mailchimp_lists\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mailchimp_lists\Plugin\Field\FieldFormatter\MailchimpListsFieldSubscribeFormatter;
use Drupal\mailchimp_lists\Plugin\Field\FieldType\MailchimpListsSubscription;

/**
 * Subscribe to a MailChimp list.
 */
class MailchimpListsSubscribeForm extends FormBase {

  /**
   * The ID for this form.
   * Set as class property so it can be overwritten as needed.
   *
   * @var string
   */
  private $formId = 'mailchimp_lists_subscribe';

  /**
   * The MailchimpListsSubscription field instance used to build this form.
   *
   * @var MailchimpListsSubscription
   */
  private $fieldInstance;

  /**
   * A reference to the field formatter used to build this form.
   * Used to get field configuration.
   *
   * @var MailchimpListsFieldSubscribeFormatter
   */
  private $fieldFormatter;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return $this->formId;
  }

  public function setFormID($formId) {
    $this->formId = $formId;
  }

  public function setFieldInstance(MailchimpListsSubscription $fieldInstance) {
    $this->fieldInstance = $fieldInstance;
  }

  public function setFieldFormatter(MailchimpListsFieldSubscribeFormatter $fieldFormatter) {
    $this->fieldFormatter = $fieldFormatter;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailchimp_lists.subscribe'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    $field_settings = $this->fieldInstance->getFieldDefinition()->getSettings();
    $field_formatter_settings = $this->fieldFormatter->getSettings();

    $mc_list = mailchimp_get_list($field_settings['mc_list_id']);

    $email = mailchimp_lists_load_email($this->fieldInstance, $this->fieldInstance->getEntity());
    if (!$email) {
      return array();
    }

    $field_name = $this->fieldInstance->getFieldDefinition()->getName();

    // Determine if a user is subscribed to the list.
    $is_subscribed = mailchimp_is_subscribed($mc_list['id'], $email);
    $wrapper_key = 'mailchimp_' . $field_name;
    $form['wrapper_key'] = array(
      '#type' => 'hidden',
      '#default_value' => $wrapper_key,
    );
    $form[$wrapper_key] = array(
      '#type' => 'container',
      '#tree' => TRUE,
      '#description' => $this->fieldInstance->getFieldDefinition()->getDescription(),
      '#attributes' => array(
        'class' => array(
          'mailchimp-newsletter-wrapper',
          'mailchimp-newsletter-' . $field_name,
        ),
      ),
    );
    // Add the title and description to lists for anonymous users or if requested:
    $form[$wrapper_key]['subscribe'] = array(
      '#type' => 'checkbox',
      '#title' => 'Subscribe',
      '#disabled' => $this->fieldInstance->getFieldDefinition()->isRequired(),
      '#required' => $this->fieldInstance->getFieldDefinition()->isRequired(),
      '#default_value' => $this->fieldInstance->getFieldDefinition()->isRequired() || $is_subscribed,
    );
    // Present interest groups:
    if ($field_settings['show_interest_groups'] && $field_formatter_settings['show_interest_groups']) {
      // Perform test in case error comes back from MCAPI when getting groups:
      if (is_array($mc_list['intgroups'])) {
        $form[$wrapper_key]['interest_groups'] = array(
          '#type' => 'fieldset',
          '#title' => isset($settings['interest_groups_label']) ? $settings['interest_groups_label'] : t('Interest Groups'),
          '#weight' => 100,
          '#states' => array(
            'invisible' => array(
              ':input[name="' . $wrapper_key . '[subscribe]"]' => array('checked' => FALSE),
            ),
          ),
        );

        $groups_default = $this->fieldInstance->getInterestGroups();

        if ($groups_default == NULL) {
          $groups_default = array();
        }

        $form[$wrapper_key]['interest_groups'] += mailchimp_interest_groups_form_elements($mc_list, $groups_default, $email);
      }
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $wrapper_key = $form_state->getValue('wrapper_key');
    $choices = $form_state->getValue($wrapper_key);

    mailchimp_lists_process_subscribe_form_choices($choices, $this->fieldInstance, $this->fieldInstance->getEntity());
  }

}
