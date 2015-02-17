<?php
/**
 * @file
 * Contains \Drupal\mailchimp\Form\MailchimpAdminSettingsForm.
 */

namespace Drupal\mailchimp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure Mailchimp settings for this site.
 */
class MailchimpAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mailchimp_admin_settings';
  }

  protected function getEditableConfigNames() {
    return ['mailchimp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mailchimp.settings');

    $form['general'] = array(
      '#type' => 'details',
      '#title' => t('General settings'),
      '#open' => TRUE,
    );

    $form['general']['google_analytics_account'] = array(
      '#default_value' => $config->get('account'),
      '#description' => t('This ID is unique to each site you want to track separately, and is in the form of UA-xxxxxxx-yy. To get a Web Property ID, <a href="@analytics">register your site with Google Analytics</a>, or if you already have registered your site, go to your Google Analytics Settings page to see the ID next to every site profile. <a href="@webpropertyid">Find more information in the documentation</a>.', array('@analytics' => 'http://www.google.com/analytics/', '@webpropertyid' => Url::fromUri('https://developers.google.com/analytics/resources/concepts/gaConceptsAccounts', array('fragment' => 'webProperty'))->toString())),
      '#maxlength' => 20,
      '#placeholder' => 'UA-',
      '#required' => TRUE,
      '#size' => 15,
      '#title' => t('Web Property ID'),
      '#type' => 'textfield',
    );

    $form['tracking']['domain_tracking'] = array(
      '#type' => 'details',
      '#title' => t('Domains'),
      '#group' => 'tracking_scope',
    );


    $form['tracking']['domain_tracking']['google_analytics_domain_mode'] = array(
      '#type' => 'radios',
      '#title' => t('What are you tracking?'),
      '#options' => array(
        0 => t('A single domain (default)') . '<div class="description">' . t('Domain: @domain', array('@domain' => $_SERVER['HTTP_HOST'])) . '</div>',
        1 => t('One domain with multiple subdomains') . '<div class="description">' . t('Examples: @domains', array('@domains' => implode(', ', $multiple_sub_domains))) . '</div>',
        2 => t('Multiple top-level domains') . '<div class="description">' . t('Examples: @domains', array('@domains' => implode(', ', $multiple_toplevel_domains))) . '</div>',
      ),
      '#default_value' => $config->get('domain_mode'),
    );

    $form['advanced']['google_analytics_debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable debugging'),
      '#description' => t('If checked, the Google Universal Analytics debugging script will be loaded. You should not enable your production site to use this version of the JavaScript. The analytics_debug.js script is larger than the analytics.js tracking code and it is not typically cached. Using it in your production site will slow down your site for all of your users. Again, this is only for your own testing purposes. Debug messages are printed to the <code>window.console</code> object.'),
      '#default_value' => $config->get('debug'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mailchimp.settings');
    $config
      ->set('account', $form_state->getValue('google_analytics_account'))
      ->set('visibility.custom', $form_state->getValue('google_analytics_custom'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
