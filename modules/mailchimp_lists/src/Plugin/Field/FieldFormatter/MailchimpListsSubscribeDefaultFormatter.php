<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists\Plugin\Field\FieldFormatter\MailchimpListsSubscribeDefaultFormatter.
 */

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
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'markup',
        '#markup' => check_plain($item->mc_list_id),
      );
    }
    return $elements;
  }

}
