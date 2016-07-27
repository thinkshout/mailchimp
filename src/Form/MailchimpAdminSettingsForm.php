<?php

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

    $mc_api_url = Url::fromUri('http://admin.mailchimp.com/account/api', array('attributes' => array('target' => '_blank')));
    $form['api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Mailchimp API Key'),
      '#required' => TRUE,
      '#default_value' => $config->get('api_key'),
      '#description' => t('The API key for your MailChimp account. Get or generate a valid API key at your @apilink.',
        array('@apilink' => \Drupal::l(t('MailChimp API Dashboard'), $mc_api_url))),
    );
    $form['cron'] = array(
      '#type' => 'checkbox',
      '#title' => 'Use batch processing.',
      '#description' => 'Puts all Mailchimp subscription operations into the cron queue. (Includes subscribe, update, and unsubscribe operations.) <i>Note: May cause confusion if caches are cleared, as requested changes will appear to have failed until cron is run.</i>',
      '#default_value' => $config->get('cron'),
    );
    $form['batch_limit'] = array(
      '#type' => 'select',
      '#options' => array(
        '1' => '1',
        '10' => '10',
        '25' => '25',
        '50' => '50',
        '75' => '75',
        '100' => '100',
        '250' => '250',
        '500' => '500',
        '750' => '750',
        '1000' => '1000',
        '2500' => '2500',
        '5000' => '5000',
        '7500' => '7500',
        '10000' => '10000',
      ),
      '#title' => t('Batch limit'),
      '#description' => t('Maximum number of entities to process in a single cron run. Mailchimp suggest keeping this at 5000 or below. <i>This value is also used for batch Merge Variable updates on the Fields tab (part of mailchimp_lists).</i>'),
      '#default_value' => $config->get('batch_limit'),
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
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('cron', $form_state->getValue('cron'))
      ->set('batch_limit', $form_state->getValue('batch_limit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
