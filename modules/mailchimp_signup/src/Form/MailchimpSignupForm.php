<?php
/**
 * @file
 * Contains \Drupal\mailchimp_signup\Form\MailchimpSignupForm.
 */

namespace Drupal\mailchimp_signup\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    $signup = $this->entity;

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#size' => 35,
      '#maxlength' => 32,
      '#default_value' => $signup->get('title'),
      '#description' => $this->t('The title for this signup form.'),
      '#required' => TRUE,
    );
    $form['name'] = array(
      '#type' => 'machine_name',
      '#default_value' => $signup->get('name'),
      '#machine_name' => array(
        'source' => array('name'),
        'exists' => 'exist',
      ),
      '#description' => t('A unique machine-readable name for this list. It must only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$signup->isNew(),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => 'Description',
      '#default_value' => isset($signup->settings['description']) ? $signup->settings['description'] : '',
      '#rows' => 2,
      '#maxlength' => 500,
      '#description' => t('This description will be shown on the signup form below the title. (500 characters or less)'),
    );
    $mode_defaults = array(
      MAILCHIMP_SIGNUP_BLOCK => array(MAILCHIMP_SIGNUP_BLOCK),
      MAILCHIMP_SIGNUP_PAGE => array(MAILCHIMP_SIGNUP_PAGE),
      MAILCHIMP_SIGNUP_BOTH => array(MAILCHIMP_SIGNUP_BLOCK, MAILCHIMP_SIGNUP_PAGE),
    );
    $form['mode'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Display Mode',
      '#required' => TRUE,
      '#options' => array(
        MAILCHIMP_SIGNUP_BLOCK => 'Block',
        MAILCHIMP_SIGNUP_PAGE => 'Page',
      ),
      '#default_value' => !empty($signup->mode) ? $mode_defaults[$signup->mode] : array(),
    );

    $form['settings'] = array(
      '#type' => 'fieldset',
      '#title' => 'Settings',
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['settings']['path'] = array(
      '#type' => 'textfield',
      '#title' => 'Page URL',
      '#description' => t('Path to the signup page. ie "newsletter/signup".'),
      '#default_value' => isset($signup->settings['path']) ? $signup->settings['path'] : NULL,
      '#states' => array(
        // Hide unless needed.
        'visible' => array(
          ':input[name="mode[' . MAILCHIMP_SIGNUP_PAGE . ']"]' => array('checked' => TRUE),
        ),
        'required' => array(
          ':input[name="mode[' . MAILCHIMP_SIGNUP_PAGE . ']"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['settings']['submit_button'] = array(
      '#type' => 'textfield',
      '#title' => 'Submit Button Label',
      '#required' => 'TRUE',
      '#default_value' => isset($signup->settings['submit_button']) ? $signup->settings['submit_button'] : 'Submit',
    );

    $form['settings']['confirmation_message'] = array(
      '#type' => 'textfield',
      '#title' => 'Confirmation Message',
      '#description' => 'This message will appear after a successful submission of this form. Leave blank for no message, but make sure you configure a destination in that case unless you really want to confuse your site visitors.',
      '#default_value' => isset($signup->settings['confirmation_message']) ? $signup->settings['confirmation_message'] : 'You have been successfully subscribed.',
    );

    $form['settings']['destination'] = array(
      '#type' => 'textfield',
      '#title' => 'Form destination page',
      '#description' => 'Leave blank to stay on the form page.',
      '#default_value' => isset($signup->settings['destination']) ? $signup->settings['destination'] : NULL,
    );

    $form['mc_lists_config'] = array(
      '#type' => 'fieldset',
      '#title' => t('MailChimp List Selection & Configuration'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $lists = mailchimp_get_lists();
    $options = array();
    foreach ($lists as $mc_list) {
      $options[$mc_list['id']] = $mc_list['name'];
    }
    $mc_admin_url = Url::fromUri('https://admin.mailchimp.com');
    $form['mc_lists_config']['mc_lists'] = array(
      '#type' => 'checkboxes',
      '#title' => t('MailChimp Lists'),
      '#description' => t('Select which lists to show on your signup form. You can create additional lists at !MailChimp.',
        array('!MailChimp' => \Drupal::l(t('MailChimp'), $mc_admin_url))),
      '#options' => $options,
      '#default_value' => is_array($signup->mc_lists) ? $signup->mc_lists : array(),
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => '::mergefields_callback',
        'wrapper' => 'mergefields-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => array(
          'type' => 'throbber',
          'message' => t('Retrieving merge fields for this list.'),
        ),
      ),
    );

    $form['mc_lists_config']['mergefields'] = array(
      '#prefix' => '<div id="mergefields-wrapper">',
      '#suffix' => '</div>',
    );

    // Show merge fields if changing list field or editing existing list.
    if ($form_state->getValue('mc_lists') || !isset($signup->is_new) || !$signup->is_new) {
      $form['mc_lists_config']['mergefields'] = array(
        '#type' => 'fieldset',
        '#title' => t('Merge Field Display'),
        '#description' => t('Select the merge fields to show on registration forms. Required fields are automatically displayed.'),
        '#id' => 'mergefields-wrapper',
        '#tree' => TRUE,
        '#weight' => 20,
      );
      $mc_lists = $form_state->getValue('mc_lists') ? $form_state->getValue('mc_lists') : $signup->mc_lists;
      $mergevar_settings = mailchimp_get_mergevars(array_filter($mc_lists));
      $mergevar_options = array();
      foreach ($mergevar_settings as $list_mergevars) {
        foreach ($list_mergevars['merge_vars'] as $mergevar) {
          if ($mergevar['public']) {
            $mergevar_options[$mergevar['tag']] = $mergevar;
          }
        }
      }
      foreach ($mergevar_options as $mergevar) {
        $form['mc_lists_config']['mergefields'][$mergevar['tag']] = array(
          '#type' => 'checkbox',
          '#title' => String::checkPlain($mergevar['name']),
          '#default_value' => isset($signup->settings['mergefields'][$mergevar['tag']]) ? !empty($signup->settings['mergefields'][$mergevar['tag']]) : TRUE,
          '#required' => $mergevar['req'],
          '#disabled' => $mergevar['req'],
        );
      }
    }

    $form['subscription_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Subscription Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['subscription_settings']['doublein'] = array(
      '#type' => 'checkbox',
      '#title' => t('Require subscribers to Double Opt-in'),
      '#description' => t('New subscribers will be sent a link with an email they must follow to confirm their subscription.'),
      '#default_value' => isset($signup->settings['doublein']) ? $signup->settings['doublein'] : FALSE,
    );

    $form['subscription_settings']['send_welcome'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send a welcome email to new subscribers'),
      '#description' => t('New subscribers will be sent a welcome email once they are confirmed.'),
      '#default_value' => isset($signup->settings['send_welcome']) ? $signup->settings['send_welcome'] : FALSE,
    );

    $form['subscription_settings']['include_interest_groups'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include interest groups on subscription form.'),
      '#default_value' => isset($signup->settings['include_interest_groups']) ? $signup->settings['include_interest_groups'] : FALSE,
      '#description' => t('If set, subscribers will be able to select applicable interest groups on the signup form.'),
    );

    return $form;
  }

  /**
   * AJAX callback handler for MailchimpSignupForm.
   */
  public function mergefields_callback(&$form, FormStateInterface $form_state) {
    return $form['mc_lists_config']['mergefields'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $example = $this->entity;
    $status = $example->save();

    if (isset($form_state['signup'])) {
      $signup = $form_state['signup'];
      $prior_settings = $signup->settings;
      if (!$form_state['values']['mode'][MAILCHIMP_SIGNUP_PAGE]) {
        $form_state['values']['settings']['path'] = '';
      }
    }
    else {
      $signup = mailchimp_signup_create();
      $prior_settings = array();
    }
    $mergefields = $form_state['values']['mergefields'];
    foreach ($mergefields as $id => $val) {
      if ($val) {
        $mergefields[$id] = $form_state['mergevar_options'][$id];
      }
    }
    $signup->title = $form_state['values']['title'];
    $signup->name = $form_state['values']['name'];
    $signup->mode = array_sum($form_state['values']['mode']);
    $signup->mc_lists = array_filter($form_state['values']['mc_lists']);
    $signup->settings = $form_state['values']['settings'];
    $signup->settings['mergefields'] = $mergefields;
    $signup->settings['description'] = $form_state['values']['description'];
    $signup->settings['doublein'] = $form_state['values']['doublein'];
    $signup->settings['send_welcome'] = $form_state['values']['send_welcome'];
    $signup->settings['include_interest_groups'] = $form_state['values']['include_interest_groups'];
    if ($signup->save()) {
      if (isset($form_state['values']['settings']['path'])) {
        if (!isset($prior_settings['path']) || $prior_settings['path'] != $signup->settings['path'] || !($signup->mode & MAILCHIMP_SIGNUP_PAGE)) {
          // We have a new (or removed) path. Rebuild menus.
          menu_rebuild();
        }
      }
      drupal_set_message(t('Signup form @name has been saved.',
        array('@name' => $signup->name)));
      $form_state['redirect'] = 'admin/config/services/mailchimp/signup';
    }
    else {
      drupal_set_message(t('There has been an error saving your signup form.'), 'error');
    }

    if ($status) {
      drupal_set_message($this->t('Saved the %label Example.', array(
        '%label' => $example->get('title'),
      )));
    }
    else {
      drupal_set_message($this->t('The %label Example was not saved.', array(
        '%label' => $example->get('title'),
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
