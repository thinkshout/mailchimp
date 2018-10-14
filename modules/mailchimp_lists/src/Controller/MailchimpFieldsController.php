<?php

namespace Drupal\mailchimp_lists\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Mailchimp Fields controller.
 */
class MailchimpFieldsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function overview() {
    $content = array();

    $content['description'] = array(
      '#markup' => t('This displays a list of all Mailchimp Subscription Fields
        configured on your system, with a row for each unique Instance of that field.
        To edit each field\'s settings, go to the Entity Bundle\'s configuration
        screen and use the Field UI.
        When entities with Mailchimp Subscription Fields are updated,
        the Merge Variables configured through Field UI are automatically updated if necessary.
        However, if you have existing subscribers on Mailchimp and matching Entities
        on Drupal when you configure your Merge Variables, the existing values
        are not synced automatically, as this could be a slow process.
        You can manually force updates of all existing Merge Values to existing
        Mailchimp subscribers for each field configuration using the \'Batch Update\'
        option on this table. The Mailchimp Subscription Field is provided by the
        Mailchimp Lists (mailchimp_lists) module.')
    );

    $content['fields_table'] = array(
      '#type' => 'table',
      '#header' => array(t('Entity Type'), t('Bundle'), t('Field'), t('Batch Update'),),
      '#empty' => '',
    );

    $field_map = \Drupal::entityManager()->getFieldMap();

    $row_id = 1;
    foreach ($field_map as $entity_type => $fields) {
      foreach ($fields as $field_name => $field_properties) {
        if ($field_properties['type'] == 'mailchimp_lists_subscription') {
          foreach ($field_properties['bundles'] as $bundle) {
            $batch_update_url = Url::fromRoute('mailchimp_lists.update_mergevars', array(
              'entity_type' => $entity_type,
              'bundle' => $bundle,
              'field_name' => $field_name,
              'destination' => 'admin/config/services/mailchimp/fields',
            ));

            $content['fields_table'][$row_id]['entity_type'] = array(
              '#markup' => $entity_type,
            );
            $content['fields_table'][$row_id]['bundle'] = array(
              '#markup' => $bundle,
            );
            $content['fields_table'][$row_id]['field'] = array(
              '#markup' => $field_name,
            );
            $content['fields_table'][$row_id]['batch_update'] = array(
              '#markup' => Link::fromTextAndUrl(t('Update Mailchimp Mergevar Values'), $batch_update_url)->toString(),
            );

            $row_id++;
          }
        }
      }
    }

    return $content;
  }

}
