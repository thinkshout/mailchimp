<?php

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
  public static function defaultSettings() {
    $settings = array(
      'show_interest_groups' => FALSE,
    );

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $field_settings = $this->getFieldSettings();
    $settings = $this->getSettings();

    $form['show_interest_groups'] = array(
      '#title' => t('Show Interest Groups'),
      '#type' => 'checkbox',
      '#description' => $field_settings['show_interest_groups'] ? t('Check to display interest group membership details.') : t('To display Interest Groups, first enable them in the field instance settings.'),
      '#default_value' => $field_settings['show_interest_groups'] && $settings['show_interest_groups'],
      '#disabled' => !$field_settings['show_interest_groups'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $field_settings = $this->getFieldSettings();
    $settings = $this->getSettings();

    $summary = array();

    if ($field_settings['show_interest_groups'] && $settings['show_interest_groups']) {
      $summary[] = t('Display Interest Groups');
    }
    else {
      $summary[] = t('Hide Interest Groups');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
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
