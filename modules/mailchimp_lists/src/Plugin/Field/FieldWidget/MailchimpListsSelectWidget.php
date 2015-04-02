<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists\Plugin\Field\FieldWidget\MailchimpListsSelectWidget.
 */

namespace Drupal\mailchimp_lists\Plugin\Field\FieldWidget;

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
  public function formElement(FieldItemListInterface $items, $delta,
                              array $element, array &$form, FormStateInterface $form_state) {
    $element += array(
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->mc_list_id ?: NULL,
      '#placeholder' => $this->getSetting('placeholder'),
    );
    return array('value' => $element);
  }

}
