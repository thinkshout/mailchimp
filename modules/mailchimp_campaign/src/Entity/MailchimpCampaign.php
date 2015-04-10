<?php

/**
 * @file
 * Contains \Drupal\mailchimp_campaign\Entity\MailchimpCampaign.
 */

namespace Drupal\mailchimp_campaign\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\mailchimp_campaign\MailchimpCampaignInterface;

/**
 * Defines the MailchimpCampaign entity.
 *
 * @ContentEntityType(
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
   * The last changed timestamp.
   *
   * @var int
   */
  public $changed;

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    // Standard field, used as unique if primary index.
    $fields['mc_campaign_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('MailChimp Campaign ID'))
      ->setDescription(t('MailChimp campaign ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 16);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the campaign.'))
      ->setReadOnly(TRUE);

    $fields['template'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Template'))
      ->setDescription(t('Campaign body template.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The Unix timestamp when the campaign was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The Unix timestamp when the campaign was most recently saved.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->mc_campaign_id;
  }

}
?>
