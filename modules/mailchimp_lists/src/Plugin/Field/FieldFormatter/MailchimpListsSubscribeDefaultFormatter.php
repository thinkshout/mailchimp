<?php

namespace Drupal\mailchimp_lists\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'mailchimp_lists_subscribe_default' formatter.
 *
 * @FieldFormatter (
 *   id = "mailchimp_lists_subscribe_default",
 *   label = @Translation("Subscription Info"),
 *   field_types = {
 *     "mailchimp_lists_subscription"
 *   }
 * )
 */
class MailchimpListsSubscribeDefaultFormatter extends FormatterBase {

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
      $elements[$delta] = array();

      $field_settings = $this->getFieldSettings();

      $mc_list = mailchimp_get_list($field_settings['mc_list_id']);
      $email = mailchimp_lists_load_email($item, $item->getEntity(), FALSE);

      if ($email) {
        if (mailchimp_is_subscribed($field_settings['mc_list_id'], $email)) {
          $status = t('Subscribed to %list', array('%list' => $mc_list->name));
        }
        else {
          $status = t('Not subscribed to %list', array('%list' => $mc_list->name));
        }
      }
      else {
        $status = t('Invalid email configuration.');
      }
      $elements[$delta]['status'] = array(
        '#markup' => $status,
        '#description' => t('@mc_list_description', array(
          '@mc_list_description' => $item->getFieldDefinition()
            ->getDescription()
        )),
      );

      if ($field_settings['show_interest_groups'] && $this->getSetting('show_interest_groups')) {
        $member_info = mailchimp_get_memberinfo($field_settings['mc_list_id'], $email);

        if (!empty($mc_list->intgroups)) {
          $elements[$delta]['interest_groups'] = array(
            '#type' => 'fieldset',
            '#title' => t('Interest Groups'),
            '#weight' => 100,
          );

          foreach ($mc_list->intgroups as $interest_group) {
            $items = array();
            foreach ($interest_group->interests as $interest) {
              if (isset($member_info->interests->{$interest->id}) && ($member_info->interests->{$interest->id} === TRUE)) {
                $items[] = $interest->name;
              }
            }

            if (count($items) > 0) {
              $elements[$delta]['interest_groups'][$interest_group->id] = array(
                '#title' => $interest_group->title,
                '#theme' => 'item_list',
                '#items' => $items,
                '#type' => 'ul',
              );
            }
          }
        }

      }
    }

    return $elements;
  }
}
