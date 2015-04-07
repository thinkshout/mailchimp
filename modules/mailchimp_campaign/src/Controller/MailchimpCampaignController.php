<?php

/**
 * @file
 * Contains \Drupal\mailchimp_campaign\Controller\MailchimpCampaignController.
 */

namespace Drupal\mailchimp_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

use Drupal\Component\Utility\String;

/**
 * MailChimp Campaign controller.
 */
class MailchimpCampaignController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function overview() {
    $content = array();

    return $content;
  }

}
