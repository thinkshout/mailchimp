<?php

/**
 * @file
 * Contains \Drupal\mailchimp_campaign\Entity\MailchimpCampaign.
 */

namespace Drupal\mailchimp_campaign\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
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
 *   config_prefix = "mailchimp_campaign",
 *   admin_permission = "administer mailchimp",
 *   entity_keys = {
 *     "mc_campaign_id" = "mc_campaign_id",
 *   },
 *   links = {
 *     "edit-form" = "entity.mailchimp_campaign.edit_form",
 *     "delete-form" = "entity.mailchimp_campaign.delete_form"
 *   }
 * )
 */
class MailchimpCampaign extends ConfigEntityBase implements MailchimpCampaignInterface {

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
