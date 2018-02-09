<?php

namespace Drupal\mailchimp_signup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
      '#markup' => $this->signup->description,
    );

    $form['mailchimp_lists'] = array('#tree' => TRUE);

    $lists = mailchimp_get_lists($this->signup->mc_lists);

    $lists_count = (!empty($lists)) ? count($lists) : 0;

    if (empty($lists)) {
      drupal_set_message($this->t('The subscription service is currently unavailable. Please try again later.'), 'warning');
    }

    $list = array();
    if ($lists_count > 1) {
      foreach ($lists as $list) {
        // Wrap in a div:
        $wrapper_key = 'mailchimp_' . $list->id;

        $form['mailchimp_lists'][$wrapper_key] = array(
          '#prefix' => '<div id="mailchimp-newsletter-' . $list->id . '" class="mailchimp-newsletter-wrapper">',
          '#suffix' => '</div>',
        );

        $form['mailchimp_lists'][$wrapper_key]['subscribe'] = array(
          '#type' => 'checkbox',
          '#title' => $list->name,
          '#return_value' => $list->id,
          '#default_value' => 0,
        );

        if ($this->signup->settings['include_interest_groups'] && isset($list->intgroups)) {
          $form['mailchimp_lists'][$wrapper_key]['interest_groups'] = array(
            '#type' => 'fieldset',
            '#title' => t('Interest Groups for %label', array('%label' => $list->name)),
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
      if ($this->signup->settings['include_interest_groups'] && isset($list->intgroups)) {
        $form['mailchimp_lists']['#weight'] = 9;
        $form['mailchimp_lists']['interest_groups'] = mailchimp_interest_groups_form_elements($list);
      }
    }

    $mergevars_wrapper_id = isset($list->id) ? $list->id : '';
    $form['mergevars'] = array(
      '#prefix' => '<div id="mailchimp-newsletter-' . $mergevars_wrapper_id . '-mergefields" class="mailchimp-newsletter-mergefields">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    );

    foreach ($this->signup->settings['mergefields'] as $tag => $mergevar_str) {
      if (!empty($mergevar_str)) {
        $mergevar = unserialize($mergevar_str);
        $form['mergevars'][$tag] = mailchimp_insert_drupal_form_tag($mergevar);
        if (empty($lists)) {
          $form['mergevars'][$tag]['#disabled'] = TRUE;
        }
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->signup->settings['submit_button'],
      '#disabled' => (empty($lists)),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $signup = $build_info['callback_object']->signup;

    // For forms that allow subscribing to multiple lists
    // ensure at least one list has been selected.

    // Get the enabled lists for this form.
    $enabled_lists = array_filter($signup->mc_lists);
    if (count($enabled_lists) > 1) {

      // Filter the selected lists out of the form values.
      $selected_lists = array_filter($form_state->getValue('mailchimp_lists'),
        function($list) {
          return $list['subscribe'];
        }
      );

      // If a list has been selected, validation passes.
      if (!empty($selected_lists)) {
        return;
      }

      $form_state->setErrorByName('mailchimp_lists', t("Please select at least one list to subscribe to."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;

    $list_details = mailchimp_get_lists($this->signup->mc_lists);

    $subscribe_lists = array();

    // Filter out blank fields so we don't erase values on the Mailchimp side.
    $mergevars = array_filter($form_state->getValue('mergevars'));

    $email = $mergevars['EMAIL'];

    $mailchimp_lists = $form_state->getValue('mailchimp_lists');

    // If we only have one list we won't have checkbox values to investigate.
    if (count(array_filter($this->signup->mc_lists)) == 1) {
      $subscribe_lists[0] = array(
        'subscribe' => reset($this->signup->mc_lists),
        'interest_groups' => isset($mailchimp_lists['interest_groups']) ? $mailchimp_lists['interest_groups'] : NULL,
      );
    }
    else {
      // We can look at the checkbox values now.
      foreach ($mailchimp_lists as $list) {
        if ($list['subscribe']) {
          $subscribe_lists[] = $list;
        }
      }
    }

    $successes = array();

    // Loop through the selected lists and try to subscribe.
    foreach ($subscribe_lists as $list_choices) {
      $list_id = $list_choices['subscribe'];

      $interests = isset($list_choices['interest_groups']) ? $list_choices['interest_groups'] : array();
      if (isset($this->signup->settings['safe_interest_groups']) && $this->signup->settings['safe_interest_groups']) {
        $current_status = mailchimp_get_memberinfo($list_id, $email);
        if (isset($current_status->interests)) {
          $current_interests = array();
          foreach ($current_status->interests as $id => $selected) {
            if ($selected) {
              $current_interests[$id] = $id;
            }
          }
          $interests[] = $current_interests;
        }
      }
      $result = mailchimp_subscribe($list_id, $email, $mergevars, $interests, $this->signup->settings['doublein']);

      if (empty($result)) {
        drupal_set_message(t('There was a problem with your newsletter signup to %list.', array(
          '%list' => $list_details[$list_id]->name,
        )), 'warning');
      }
      else {
        $successes[] = $list_details[$list_id]->name;
      }
    }

    if (count($successes) && strlen($this->signup->settings['confirmation_message'])) {
      drupal_set_message($this->signup->settings['confirmation_message'], 'status');
    }

    $destination = $this->signup->settings['destination'];
    if (empty($destination)) {
      $destination_url = Url::fromRoute('<current>');
    }
    else {
      $destination_url = Url::fromUri($base_url . '/' . $this->signup->settings['destination']);
    }

    $form_state->setRedirectUrl($destination_url);
  }

}
