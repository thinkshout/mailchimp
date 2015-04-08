<?php
/**
 * @file
 * Contains \Drupal\mailchimp_campaign\Form\MailchimpCampaignForm.
 */

namespace Drupal\mailchimp_campaign\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MailchimpCampaignForm
 *
 * @package Drupal\mailchimp_campaign\Form
 */
class MailchimpCampaignForm extends EntityForm {

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

    $campaign = $this->entity;

    $form_state->set('campaign', $campaign);

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('An internal name to use for this campaign. By default, the campaign subject will be used.'),
      '#required' => FALSE,
      '#default_value' => ($campaign) ? $campaign->mc_data['title'] : '',
    );
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#required' => TRUE,
      '#default_value' => ($campaign) ? $campaign->mc_data['subject'] : '',
    );
    $mailchimp_lists = mailchimp_get_lists();
    $form['list_id'] = array(
      '#type' => 'select',
      '#title' => t('List'),
      '#description' => t('Select the list this campaign should be sent to.'),
      '#options' => $this->buildOptionList($mailchimp_lists),
      '#default_value' => ($campaign) ? $campaign->mc_data['list_id'] : -1,
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => 'mailchimp_campaign_list_segment_callback',
        'method' => 'replace',
        'wrapper' => 'list-segments-wrapper',
      ),
    );

    if (!empty($form_state->get('list_id'))) {
      $list_id = $form_state->get('list_id');
    }
    elseif ($campaign && $campaign->mc_data) {
      $list_id = $campaign->mc_data['list_id'];
      if (isset($campaign->mc_data['saved_segment']['id'])) {
        $segment_id = $campaign->mc_data['saved_segment']['id'];
      }
    }

    $list_segments = array();
    if (isset($list_id)) {
      $list_segments = mailchimp_campaign_get_list_segments($list_id, 'saved');
    }

    if (!empty($list_segments)) {
      $form['list_segment_id'] = array(
        '#type' => 'select',
        '#title' => t('List Segment'),
        '#description' => t('Select the list segment this campaign should be sent to.'),
        '#options' => $this->buildOptionList($list_segments, '-- Entire list --'),
        '#default_value' => (isset($segment_id)) ? $segment_id : '',
      );
    }
    else {
      $form['list_segment_id'] = array();
    }
    $form['list_segment_id']['#prefix'] = '<div id="list-segments-wrapper">';
    $form['list_segment_id']['#suffix'] = '</div>';

    $form['from_email'] = array(
      '#type' => 'textfield',
      '#title' => t('From Email'),
      '#description' => t('the From: email address for your campaign message.'),
      '#default_value' => ($campaign) ? $campaign->mc_data['from_email'] : variable_get('site_mail'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['from_name'] = array(
      '#type' => 'textfield',
      '#title' => t('From Name'),
      '#description' => t('the From: name for your campaign message (not an email address)'),
      '#default_value' => ($campaign) ? $campaign->mc_data['from_name'] : variable_get('site_name'),
      '#size' => 40,
      '#maxlength' => 255,
    );
    $template_type_labels = array(
      'user' => 'My Custom Templates',
      'basic' => 'Basic Templates',
      'gallery' => 'Themes',
    );
    $form['template_id'] = array(
      '#type' => 'select',
      '#title' => t('Template'),
      '#description' => t('Select a MailChimp user template to use. Due to a limitation in the API, only templates that do not contain repeating sections are available. If empty, the default template will be applied.'),
      '#options' => $this->buildOptionList(mailchimp_campaign_list_templates(), '-- Select --', $template_type_labels),
      '#default_value' => ($campaign) ? $campaign->mc_data['template_id'] : -1,
      '#ajax' => array(
        'callback' => 'mailchimp_campaign_template_callback',
        'wrapper' => 'content-sections',
      ),
    );
    $form['content'] = array(
      '#id' => 'content-sections',
      '#type' => 'fieldset',
      '#title' => t('Content sections'),
      '#description' => t('The HTML content or, if a template is selected, the content for each section.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    );

    $mc_template = NULL;
    if (!empty($form_state->get('template_id'))) {
      $mc_template = mailchimp_campaign_get_template($form_state->get('template_id'));
    }
    else {
      if (($campaign) && $campaign->mc_template) {
        $mc_template = $campaign->mc_template;
      }
    }

    if (isset($list_id)) {
      $merge_vars_list = mailchimp_get_mergevars(array($list_id));
      $merge_vars = $merge_vars_list[$list_id]['merge_vars'];
    }
    else {
      $merge_vars = array();
    }

    if ($mc_template) {
      if (strpos($mc_template['info']['source'], 'mc:repeatable')) {
        drupal_set_message(t('WARNING: This template has repeating sections, which are not supported. You may want to select a different template.'), 'warning');
      }
      foreach ($mc_template['info']['default_content'] as $section => $content) {
        // Set the default value and text format to either saved campaign values
        // or defaults coming from the MailChimp template.
        $default_value = $content;
        $format = 'mailchimp_campaign';
        if ($campaign && $campaign->template[$section]) {
          $default_value = $campaign->template[$section]['value'];
          $format = $campaign->template[$section]['format'];
        }
        $form['content'][$section . '_wrapper'] = array(
          '#type' => 'fieldset',
          '#title' => check_plain(drupal_ucfirst($section)),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        );
        $form['content'][$section . '_wrapper'][$section] = array(
          '#type' => 'text_format',
          '#format' => $format,
          '#title' => check_plain(drupal_ucfirst($section)),
          '#default_value' => $default_value,
        );

        $campaign_content = $form_state->get('content');

        $entity_type = NULL;
        if (isset($campaign_content[$section . '_wrapper']['entity_import']['entity_type'])) {
          $entity_type = $campaign_content[$section . '_wrapper']['entity_import']['entity_type'];
        }

        $form['content'][$section . '_wrapper'] += mailchimp_campaign_get_entity_import_form_elements($entity_type, $section);
        $form['content'][$section . '_wrapper'] += mailchimp_campaign_get_merge_vars_form_elements($merge_vars, $mailchimp_lists[$list_id]['name']);
      }
    }
    else {
      $section = 'html';

      $form['content']['html_wrapper'] = array(
        '#type' => 'fieldset',
        '#title' => t('Content'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['content']['html_wrapper']['html'] = array(
        '#type' => 'text_format',
        '#format' => ($campaign) ? $campaign->template['html']['format'] : 'mailchimp_campaign',
        '#title' => t('Content'),
        '#description' => t('The HTML content of the campaign.'),
        '#access' => empty($form_state->get('template_id')),
        '#default_value' => ($campaign) ? $campaign->template['html']['value'] : '',
      );

      $entity_type = NULL;
      if (isset($campaign_content[$section . '_wrapper']['entity_import']['entity_type'])) {
        $entity_type = $campaign_content[$section . '_wrapper']['entity_import']['entity_type'];
      }

      $form['content'][$section . '_wrapper'] += mailchimp_campaign_get_entity_import_form_elements($entity_type, $section);

      $list_name = (!empty($list_id)) ? $mailchimp_lists[$list_id]['name'] : '';
      $form['content'][$section . '_wrapper'] += mailchimp_campaign_get_merge_vars_form_elements($merge_vars, $list_name);
    }

    // Message preview:
    if (!empty($form_state->get('mailchimp_campaign_campaign_preview'))) {
      $form['preview_wrapper'] = array(
        '#title' => t('Campaign content preview'),
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );
      $form['preview_wrapper']['preview'] = array(
        '#markup' => $form_state->get('mailchimp_campaign_campaign_preview'),
      );
    }

    $form['actions']['preview'] = array(
      '#type' => 'submit',
      '#value' => t('Preview content'),
      '#weight' => 10,
      '#submit' => array('mailchimp_campaign_campaign_preview'),
    );
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save as draft'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // TODO: Save entity.

    $form_state->setRedirect('mailchimp_campaign.overview');
  }

  /**
   * Returns an options list for a given array of items.
   *
   * @param array $list
   *   Array of item data containing 'id' and 'name' properties.
   * @param string $no_selection_label
   *   The option value to display when no option is selected.
   * @param array $labels
   *   Optional associative array of list indexes to custom labels.
   *
   * @return array
   *   Associative array of item IDs to name.
   */
  private function buildOptionList($list, $no_selection_label = '-- Select --', $labels = array()) {
    $options = array();
    if ($no_selection_label) {
      $options[''] = $no_selection_label;
    }
    foreach ($list as $index => $item) {
      if (!isset($item['id'])) {
        $label = isset($labels[$index]) ? $labels[$index] : $index;
        if (count($item)) {
          $options[$label] = $this->buildOptionList($item, FALSE, $labels);
        }
      }
      else {
        $options[$item['id']] = $item['name'];
      }
    }

    return $options;
  }

}

?>
