<?php
/**
 * @file
 * Contains \Drupal\mailchimp_campaign\Form\MailchimpCampaignDeleteForm.
 */

namespace Drupal\mailchimp_campaign\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the MailchimpCampaign entity delete form.
 *
 * @ingroup mailchimp_campaign
 */
class MailchimpCampaignDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name? This action will delete both the MailChimp campaign and Drupal entity and cannot be undone.',
      array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mailchimp_campaign.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('MailChimp Campaign %label has been deleted.', array('%label' => $this->entity->label())));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
?>
