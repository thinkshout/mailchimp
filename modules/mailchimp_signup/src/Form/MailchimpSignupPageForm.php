<?php
/**
 * @file
 * Contains \Drupal\mailchimp_signup\Form\MailchimpSignupPageForm.
 */

namespace Drupal\mailchimp_signup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mailchimp_signup\Entity\MailchimpSignup;

/**
 * Subscribe to a MailChimp list.
 */
class MailchimpSignupPageForm extends FormBase {

  /**
   * The ID for this form.
   * Set as class property so it can be overwritten as needed.
   *
   * @var string
   */
  private $formId = 'mailchimp_signup_page_form';

  /**
   * The MailchimpSignup entity used to build this form.
   *
   * @var MailchimpSignup
   */
  private $signup = nULL;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return $this->formId;
  }

  public function setFormID($formId) {
    $this->formId = $formId;
  }

  public function setSignup(MailchimpSignup $signup) {
    $this->signup = $signup;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailchimp_signup.page_form'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    $form['#attributes'] = array('class' => array('mailchimp-signup-subscribe-form'));

    $form['description'] = array(
      '#markup' => $this->signup->settings['description'],
    );

    $form['mailchimp_lists'] = array('#tree' => TRUE);

    $lists = mailchimp_get_lists($this->signup->mc_lists);

    $lists_count = (!empty($lists)) ? count($lists) : 0;

    if (empty($lists)) {
      drupal_set_message('The subscription service is currently unavailable. Please try again later.', 'warning');
    }

    $list = array();
    if ($lists_count > 1) {
      foreach ($lists as $list) {
        // Wrap in a div:
        $wrapper_key = 'mailchimp_' . $list['web_id'];

        $form['mailchimp_lists'][$wrapper_key] = array(
          '#prefix' => '<div id="mailchimp-newsletter-' . $list['web_id'] . '" class="mailchimp-newsletter-wrapper">',
          '#suffix' => '</div>',
        );

        $form['mailchimp_lists'][$wrapper_key]['subscribe'] = array(
          '#type' => 'checkbox',
          '#title' => $list['name'],
          '#return_value' => $list['id'],
          '#default_value' => 0,
        );

        if ($this->signup->settings['include_interest_groups'] && isset($list['intgroups'])) {
          $form['mailchimp_lists'][$wrapper_key]['interest_groups'] = array(
            '#type' => 'fieldset',
            '#title' => t('Interest Groups for %label', array('%label' => $list['name'])),
            '#states' => array(
              'invisible' => array(
                ':input[name="mailchimp_lists[' . $wrapper_key . '][subscribe]"]' => array('checked' => FALSE),
              ),
            ),
          );
          $form['mailchimp_lists'][$wrapper_key]['interest_groups'] += mailchimp_interest_groups_form_elements($list);
        }
      }
    }
    else {
      $list = reset($lists);
      if ($this->signup->settings['include_interest_groups'] && isset($list['intgroups'])) {
        $form['mailchimp_lists']['#weight'] = 9;
        $form['mailchimp_lists']['interest_groups'] = mailchimp_interest_groups_form_elements($list);
      }
    }

    $form['mergevars'] = array(
      '#prefix' => '<div id="mailchimp-newsletter-' . $list['web_id'] . '-mergefields" class="mailchimp-newsletter-mergefields">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    );

    foreach ($this->signup->settings['mergefields'] as $tag => $mergevar) {
      if (!empty($mergevar)) {
        $form['mergevars'][$tag] = mailchimp_insert_drupal_form_tag($mergevar);
        if (empty($lists)) {
          $form['mergevars'][$tag]['#disabled'] = TRUE;
        }
      }
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#weight' => 10,
      '#value' => $this->signup->settings['submit_button'],
      '#disabled' => (empty($lists)),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
