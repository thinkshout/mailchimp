<?php

/**
 * @file
 * Contains \Drupal\mailchimp_campaign\Entity\MailchimpCampaignViewBuilder.
 */

namespace Drupal\mailchimp_campaign\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Defines the render controller for MailchimpCampaign entities.
 */
class MailchimpCampaignViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    return $build;
  }

}
