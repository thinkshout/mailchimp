<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists\Plugin\Field\FieldFormatter\MailchimpListsFieldSubscribeFormatter.
 */

namespace Drupal\mailchimp_lists\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'mailchimp_lists_field_subscribe' formatter.
 *
 * @FieldFormatter (
 *   id = "mailchimp_lists_field_subscribe",
 *   label = @Translation("Subscription Form"),
 *   field_types = {
 *     "mailchimp_lists_subscription"
 *   }
 * )
 */
class MailchimpListsFieldSubscribeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    /* @var $item \Drupal\mailchimp_lists\Plugin\Field\FieldType\MailchimpListsSubscription */
    foreach ($items as $delta => $item) {
      $form = new \Drupal\mailchimp_lists\Form\MailchimpListsSubscribeForm();

      $field_name = $item->getFieldDefinition()->getName();

      // Give each form a unqiue ID in case of mulitiple subscription forms.
      $field_form_id = 'mailchimp_lists_' . $field_name . '_form';
      $form->setFormID($field_form_id);
      $form->setFieldInstance($item);
      $form->setFieldFormatter($this);

      $elements[$delta] = \Drupal::formBuilder()->getForm($form);
    }

    return $elements;
  }

}
