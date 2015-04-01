<?php

/**
 * @file
 * Contains \Drupal\mailchimp_lists\Controller\MailchimpFieldsController.
 */

namespace Drupal\mailchimp_lists\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

use Drupal\Component\Utility\String;

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

    return $content;
  }

}
