<?php

/**
 * @file
 * Contains \Drupal\mailchimp_lists\Controller\MailchimpFieldsController.
 */

namespace Drupal\mailchimp_lists\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * MailChimp Fields controller.
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
        When entities with MailChimp Subscription Fields are updated,
        the Merge Variables configured through Field UI are automatically updated if necessary.
        However, if you have existing subscribers on MailChimp and matching Entities
        on Drupal when you configure your Merge Variables, the existing values
        are not synced automatically, as this could be a slow process.
        You can manually force updates of all existing Merge Values to existing
        MailChimp subscribers for each field configuration using the \'Batch Update\'
        option on this table. The MailChimp Subscription Field is provided by the
        Mailchimp Lists (mailchimp_lists) module.')
    );

    $content['fields_table'] = array(
      '#type' => 'table',
      '#header' => array(t('Entity Type'), t('Bundle'), t('Field'), t('Batch Update'),),
      '#empty' => '',
    );

    // TODO: Get all fields.
    $fields = array();
    $row_id = 1;
    foreach ($fields as $field) {
      if ($field['type'] == 'mailchimp_lists_subscription') {
        foreach ($field['bundles'] as $entity_type => $bundles) {
          foreach ($bundles as $bundle) {
            // TODO: Correct bulk update URL.
            //$link = 'admin/config/services/mailchimp/lists/update_mergevars/' . $entity_type . '/' . $bundle . '/' . $field['field_name'];
            $batch_update_url = Url::fromUri('');

            $content['fields_table'][$row_id]['entity_type'] = array(
              '#markup' => $entity_type,
            );
            $content['fields_table'][$row_id]['bundle'] = array(
              '#markup' => $bundle,
            );
            $content['fields_table'][$row_id]['field'] = array(
              '#markup' => $field['field_name'],
            );
            $content['lists_table'][$row_id]['batch_update'] = array(
              '#markup' => \Drupal::l(t('Update Mailchimp Mergevar Values'), $batch_update_url),
            );

            $row_id++;
          }
        }
      }
    }

    return $content;
  }

}
