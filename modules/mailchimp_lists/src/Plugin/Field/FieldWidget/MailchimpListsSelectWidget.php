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

    $element += array(
      '#title' => Html::escape($element['#title']),
      '#type' => 'fieldset',
    );

    $element['subscribe'] = array(
      '#title' => t('Subscribe'),
      '#type' => 'checkbox',
      '#default_value' => ($subscribe_default)? TRUE : $this->fieldDefinition->isRequired(),
      '#required' => $this->fieldDefinition->isRequired(),
      '#disabled' => $this->fieldDefinition->isRequired(),
    );

    $form_id = $form_state->getFormObject()->getFormId();

    if ($this->fieldDefinition->getSetting('show_interest_groups') || ($form_id == 'field_config_edit_form')) {
      $mc_list = mailchimp_get_list($instance->getFieldDefinition()->getSetting('mc_list_id'));

      $element['interest_groups'] = array(
        '#type' => 'fieldset',
        '#title' => Html::escape($instance->getFieldDefinition()->getSetting('interest_groups_title')),
        '#weight' => 100,
        '#states' => array(
          'invisible' => array(
            ':input[name="' . $instance->getFieldDefinition()->getName() . '[0][value][subscribe]"]' => array('checked' => FALSE),
          ),
        ),
      );

      if ($form_id == 'field_config_edit_form') {
        $element['interest_groups']['#states']['invisible'] = array(
          ':input[name="settings[show_interest_groups]"]' => array('checked' => FALSE),
        );
      }

      $groups_default = $instance->getInterestGroups();

      if ($groups_default == NULL) {
        $groups_default = array();
      }

      if (!empty($mc_list->intgroups)) {
        $element['interest_groups'] += mailchimp_interest_groups_form_elements($mc_list, $groups_default, $email);
      }
    }

    return array('value' => $element);
  }

}
