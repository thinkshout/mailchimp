<?php

namespace Drupal\mailchimp_lists\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'mailchimp_lists_select' widget.
 *
 * @FieldWidget (
 *   id = "mailchimp_lists_select",
 *   label = @Translation("Subscription form"),
 *   field_types = {
 *     "mailchimp_lists_subscription"
 *   },
 *   settings = {
 *     "placeholder" = "Select a MailChimp List."
 *   }
 * )
 */
class MailchimpListsSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /* @var $instance \Drupal\mailchimp_lists\Plugin\Field\FieldType\MailchimpListsSubscription */
    $instance = $items[0];

    $subscribe_default = $instance->getSubscribe();

    $email = NULL;
    if (!empty($instance->getEntity())) {
      $email = mailchimp_lists_load_email($instance, $instance->getEntity(), FALSE);
      if ($email) {
        $subscribe_default = mailchimp_is_subscribed($instance->getFieldDefinition()->getSetting('mc_list_id'), $email);
      }
    }

    // Load the MailChimp list from the field's list ID.
    $mc_list = mailchimp_get_list($this->fieldDefinition->getSetting('mc_list_id'));

    $element += array(
      '#title' => Html::escape($element['#title']),
      '#type' => 'fieldset',
    );

    $element['subscribe'] = array(
      '#title' => $this->fieldDefinition->getSetting('subscribe_checkbox_label') ?: $this->t('Subscribe'),
      '#type' => 'checkbox',
      '#default_value' => ($subscribe_default) ? TRUE : $this->fieldDefinition->isRequired(),
      '#required' => $this->fieldDefinition->isRequired(),
      '#disabled' => $this->fieldDefinition->isRequired(),
    );

    // TRUE if interest groups are enabled for this list.
    $show_interest_groups = $this->fieldDefinition->getSetting('show_interest_groups');
    // TRUE if interest groups are enabled but hidden from the user.
    $interest_groups_hidden = $this->fieldDefinition->getSetting('interest_groups_hidden');
    // TRUE if widget is being used to set default values via admin form.
    $is_default_value_widget = $this->isDefaultValueWidget($form_state);

    // Hide the Subscribe checkbox if:
    // - The form is not being used to configure default values.
    // - The field is configured to show interest groups.
    // - The field is configured to hide the Subscribe checkbox.
    // - The list has at least one interest group.
    // This allows users to skip the redundant step of checking the Subscribe
    // checkbox when also checking interest group checkboxes.
    if (!$is_default_value_widget && $show_interest_groups && $this->fieldDefinition->getSetting('hide_subscribe_checkbox') && !empty($mc_list->intgroups)) {
      $element['subscribe']['#access'] = FALSE;
      $interest_group_element_type = 'container';
    }
    else {
      $interest_group_element_type = 'fieldset';
    }

    if ($show_interest_groups || $is_default_value_widget) {
      $mc_list = mailchimp_get_list($instance->getFieldDefinition()->getSetting('mc_list_id'));

      if ($interest_groups_hidden && !$is_default_value_widget) {
        $element['interest_groups'] = array();
      }
      else {
        $element['interest_groups'] = array(
          '#type' => $interest_group_element_type,
          '#title' => Html::escape($instance->getFieldDefinition()->getSetting('interest_groups_label')),
          '#weight' => 100,
          '#states' => array(
            'invisible' => array(
              ':input[name="' . $instance->getFieldDefinition()->getName() . '[0][value][subscribe]"]' => array('checked' => FALSE),
            ),
          ),
        );
      }

      if ($is_default_value_widget) {
        $element['interest_groups']['#states']['invisible'] = array(
          ':input[name="settings[show_interest_groups]"]' => array('checked' => FALSE),
        );
      }

      $groups_default = $instance->getInterestGroups();

      if ($groups_default == NULL) {
        $groups_default = array();
      }

      if (!empty($mc_list->intgroups)) {
        $mode = $is_default_value_widget ? 'admin' : ($interest_groups_hidden ? 'hidden' : 'default');
        $element['interest_groups'] += mailchimp_interest_groups_form_elements($mc_list, $groups_default, $email, $mode);
      }
    }

    return array('value' => $element);
  }

}
