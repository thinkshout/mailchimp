<?php

namespace Drupal\mailchimp_campaign\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\mailchimp_campaign\MailchimpCampaignInterface;

/**
 * Defines the MailchimpCampaign entity.
 *
 * @ingroup mailchimp_campaign
 *
 * @ContentEntityType(
 *   id = "mailchimp_campaign",
 *   label = @Translation("Mailchimp Campaign"),
 *   fieldable = FALSE,
 *   handlers = {
 *     "access" = "Drupal\mailchimp_campaign\MailchimpCampaignAccessControlHandler",
 *     "view_builder" = "Drupal\mailchimp_campaign\Entity\MailchimpCampaignViewBuilder",
 *     "form" = {
 *       "send" = "Drupal\mailchimp_campaign\Form\MailchimpCampaignSendForm",
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
 *     "edit-form" = "/admin/config/services/mailchimp/campaign/{mailchimp_campaign}",
 *     "delete-form" = "/admin/config/services/mailchimp/campaign/{mailchimp_campaign}/delete",
 *     "canonical" = "/admin/config/services/mailchimp/campaign/{mailchimp_campaign}",
 *   }
 * )
 */
class MailchimpCampaign extends ContentEntityBase implements MailchimpCampaignInterface {

  /**
   * {@inheritdoc}
   */
  public function getMcCampaignId() {
    return $this->get('mc_campaign_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate() {
    return unserialize($this->get('template')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMcCampaignId($mc_campaign_id) {
    $this->set('mc_campaign_id', $mc_campaign_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setTemplate($template) {
   $this->set('template', serialize($template));
  }

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
      ->setSetting('max_length', 16);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the campaign.'))
      ->setReadOnly(TRUE);

    $fields['template'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Template'))
      ->setDescription(t('Campaign body template.'))
      ->setSetting('case_sensitive', TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string_long',
      ));

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
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation == 'create') {
      return $this->entityManager()
        ->getAccessControlHandler($this->entityTypeId)
        ->createAccess($this->bundle(), $account, [], $return_as_object);
    }
    return $this->entityManager()
      ->getAccessControlHandler($this->entityTypeId)
      ->access($this, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->mc_data->settings->title;
  }

}
