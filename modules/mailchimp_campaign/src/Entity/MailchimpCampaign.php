<?php

/**
 * @file
 * Contains \Drupal\mailchimp_campaign\Entity\MailchimpCampaign.
 */

namespace Drupal\mailchimp_campaign\Entity;

use Drupal\Core\Config\Entity\ContentEntityBase;
use Drupal\mailchimp_campaign\MailchimpCampaignInterface;

/**
 * Defines the MailchimpCampaign entity.
 *
 * @ConfigEntityType(
 *   id = "mailchimp_campaign",
 *   label = @Translation("Mailchimp Campaign"),
 *   fieldable = FALSE,
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\mailchimp_campaign\Form\MailchimpCampaignForm",
 *       "edit" = "Drupal\mailchimp_campaign\Form\MailchimpCampaignForm",
 *       "delete" = "Drupal\mailchimp_campaign\Form\MailchimpCampaignDeleteForm"
 *     }
 *   },
 *   base_table = "mailchimp_campaign",
 *   admin_permission = "administer mailchimp",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "mc_campaign_id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "entity.mailchimp_campaign.edit_form",
 *     "delete-form" = "entity.mailchimp_campaign.delete_form"
 *   }
 * )
 */
class MailchimpCampaign extends ContentEntityBase implements MailchimpCampaignInterface {

  /**
   * The MailChimp campaign ID.
   *
   * @var string
   */
  public $mc_campaign_id;

  /**
   * The campaign body template.
   *
   * @var string
   */
  public $template;

  /**
   * The created timestamp.
   *
   * @var int
   */
  public $created;

  /**
   * The updated timestamp.
   *
   * @var int
   */
  public $updated;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->mc_campaign_id;
  }

}
?>
