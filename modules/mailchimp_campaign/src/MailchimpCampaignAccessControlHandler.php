<?php

namespace Drupal\mailchimp_campaign;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the MailchimpCampaign entity.
 */
class MailchimpCampaignAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /* @var $entity \Drupal\mailchimp_campaign\Entity\MailchimpCampaign */
    $status = $entity->mc_data->status;
    switch ($operation) {
      case 'send':
      case 'edit':
      case 'delete':
        return ($status == MAILCHIMP_STATUS_SENT) ? AccessResult::forbidden() : AccessResult::allowed();
        break;
      case 'stats':
        return ($status == MAILCHIMP_STATUS_SENT) ? AccessResult::allowed() : AccessResult::forbidden();
      default:
        return parent::access($entity, $operation, $account, $return_as_object);
    }
  }

}
