<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists\Plugin\Field\FieldWidget\MailchimpListsSelectWidget.
 */

namespace Drupal\mailchimp_lists\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\String;
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

    if (!empty($instance->getEntity())) {
      $email = mailchimp_lists_load_email($instance, $instance->getEntity(), FALSE);
      if ($email) {
        $subscribed = mailchimp_is_subscribed($instance->getFieldDefinition()->getSetting('mc_list_id'), $email);
      }
    }

    $element += array(
      '#title' => String::checkPlain($element['#title']),
      '#type' => 'fieldset',
    );
    $element['subscribe'] = array(
      '#title' => t('Subscribe'),
      '#type' => 'checkbox',
      '#default_value' => ($subscribed)? TRUE : $this->fieldDefinition->isRequired(),
      '#required' => $this->fieldDefinition->isRequired(),
      '#disabled' => $this->fieldDefinition->isRequired(),
    );

    // TODO: Condition should also be true if current form is field settings form.
    if ($this->fieldDefinition->getSetting('show_interest_groups')) {
      $mc_list = mailchimp_get_list($instance->getFieldDefinition()->getSetting('mc_list_id'));
      $element['interest_groups'] = array(
        '#type' => 'fieldset',
        '#title' => String::checkPlain($instance->getFieldDefinition()->getSetting('interest_groups_title')),
        '#weight' => 100,
        // TODO: Hide interest groups if subscribe checkbox is empty.
        // TODO: field_sub[0][value][subscribe]
        '#states' => array(
          'invisible' => array(
            ':input[name="' . $instance->getFieldDefinition()->getName() . '[0][value][subscribe]"]' => array('checked' => FALSE),
          ),
        ),
      );
      // TODO: Limit to field settings form only.
      //if ($form_state['build_info']['form_id'] == 'field_ui_field_edit_form') {
      //  $element['interest_groups']['#states']['invisible'] = array(
      //    ':input[name="instance[settings][show_interest_groups]"]' => array('checked' => FALSE),
      //  );
      //}

      $groups_default = array();

      // TODO: Get selected interest groups.
      //$groups_default = isset($instance['default_value'][0]['interest_groups']) ? $instance['default_value'][0]['interest_groups'] : array();
      if ($mc_list['stats']['group_count']) {
        $element['interest_groups'] += mailchimp_interest_groups_form_elements($mc_list, $groups_default, $email);
      }
    }

    return array('value' => $element);
  }

}
