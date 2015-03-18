<?php
/**
 * @file
 * Contains \Drupal\mailchimp_signup\Form\MailchimpSignupForm.
 */

namespace Drupal\mailchimp_signup\Form;

use Drupal\Core\Entity\EntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MailchimpSignupForm
 *
 * @package Drupal\mailchimp_signup\Form
 */
class MailchimpSignupForm extends EntityForm {

  /**
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $example = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $example->label(),
      '#description' => $this->t("Label for the Example."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $example->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exist'),
      ),
      '#disabled' => !$example->isNew(),
    );

    // You will need additional form elements for your custom properties.

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $example = $this->entity;
    $status = $example->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Example.', array(
        '%label' => $example->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label Example was not saved.', array(
        '%label' => $example->label(),
      )));
    }

    $form_state->setRedirect('mailchimp_signup.admin');
  }

  public function exist($id) {
    $entity = $this->entityQuery->get('mailchimp_signup')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }
}
?>
