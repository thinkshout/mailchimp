<?php

namespace Drupal\mailchimp_signup\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the MailchimpSignup entity delete form.
 *
 * @ingroup mailchimp_signup
 */
class MailchimpSignupDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mailchimp_signup.admin');
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

    \Drupal::service('router.builder')->setRebuildNeeded();

    drupal_set_message($this->t('Signup Form %label has been deleted.', array('%label' => $this->entity->label())));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
