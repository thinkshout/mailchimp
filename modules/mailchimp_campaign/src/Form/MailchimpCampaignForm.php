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

        $form['content'][$section . '_wrapper'] += $this->getEntityImportFormElements($entity_type, $section);
        $form['content'][$section . '_wrapper'] += $this->getMergeVarsFormElements($merge_vars, $mailchimp_lists[$list_id]['name']);
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

      $form['content'][$section . '_wrapper'] += $this->getEntityImportFormElements($entity_type, $section);

      $list_name = (!empty($list_id)) ? $mailchimp_lists[$list_id]['name'] : '';
      $form['content'][$section . '_wrapper'] += $this->getMergeVarsFormElements($merge_vars, $list_name);
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

  /**
   * Returns an options list of entities based on data from entity_get_info().
   *
   * Filters out entities that do not contain a title field, as they cannot
   * be used to import content into templates.
   *
   * @param array $entity_info
   *   Array of entities as returned by entity_get_info().
   *
   * @return array
   *   Associative array of entity IDs to name.
   */
  private function buildEntityOptionList($entity_info) {
    $options = array(
      '' => '-- Select --',
    );

    foreach ($entity_info as $entity_id => $entity_data) {
      // Exclude MailChimp entities.
      if (strpos($entity_id, 'mailchimp') === FALSE) {
        $options[$entity_id] = $entity_data->getLabel();
      }
    }

    return $options;
  }

  /**
   * Returns an options list of entity view modes.
   *
   * @param array $entity
   *   Array of entity data as returned by entity_get_info().
   *
   * @return array
   *   Associative array of view mode IDs to name.
   */
  private function buildEntityViewModeOptionList(array $entity) {
    $options = array();
    foreach ($entity['view modes'] as $view_mode_id => $view_mode_data) {
      $options[$view_mode_id] = $view_mode_data['label'];
    }

    return $options;
  }

  /**
   * Gets form elements used in the entity import feature.
   *
   * @param string $entity_type
   *   The type of entity to import.
   * @param string $section
   *   The content section these fields are displayed in.
   *
   * @return array
   *   Array of form elements used to display entity imports.
   */
  private function getEntityImportFormElements($entity_type, $section) {
    $form = array();

    // Get available entity types.
    $entity_info = $this->getEntitiesForContentImport();
    $entity_options = $this->buildEntityOptionList($entity_info);

    $form['entity_import'] = array(
      '#id' => 'entity-import',
      '#type' => 'fieldset',
      '#title' => t('Insert site content'),
      '#description' => t('<b>For use only with text filters that use the MailChimp Campaign filter</b><br />You can insert an entity of a given type and pick the view mode that will be rendered within this campaign section.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      // '#states' => array(
      //   'visible' => array(
      //     ':input[name="content[' . $section . '_wrapper][' . $section . '][format]"]' => array('value' => 'mailchimp_campaign'),
      //   ),
      // ),
    );

    $form['entity_import']['entity_type'] = array(
      '#type' => 'select',
      '#title' => t('Entity Type'),
      '#options' => $entity_options,
      '#default_value' => $entity_type,
      '#ajax' => array(
        'callback' => 'mailchimp_campaign_entity_type_callback',
        'wrapper' => $section . '-content-entity-lookup-wrapper',
      ),
    );
    $form['entity_import']['entity_type']['#attributes']['class'][] = $section . '-entity-import-entity-type';

    $form['entity_import']['entity_import_wrapper'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => $section . '-content-entity-lookup-wrapper',
      ),
    );

    if ($entity_type != NULL) {
      // Get available entity view modes.
      $entity_view_mode_options = buildEntityViewModeOptionList($entity_info[$entity_type]);

      $form['entity_import']['entity_id'] = array(
        '#type' => 'textfield',
        '#title' => t('Entity Title'),
        // Pass entity type as first parameter to autocomplete callback.
        '#autocomplete_path' => 'admin/config/services/mailchimp/campaigns/entities/' . $entity_type,
      );
      $form['entity_import']['entity_id']['#attributes']['id'] = $section . '-entity-import-entity-id';

      $form['entity_import']['entity_view_mode'] = array(
        '#type' => 'select',
        '#title' => t('View Mode'),
        '#options' => $entity_view_mode_options,
        '#attributes' => array(
          'id' => $section . '-entity-import-entity-view-mode',
        ),
      );
    }

    $form['entity_import']['entity_import_link'] = array(
      '#type' => 'item',
      '#markup' => '<a id="' . $section . '-add-entity-token-link" class="add-entity-token-link" href="javascript:void(0);">' . t('Insert entity token') . '</a>',
      '#states' => array(
        'invisible' => array(
          ':input[name="content[' . $section . '_wrapper][entity_import][entity_type]"]' => array('value' => ''),
        ),
      ),
    );

    $form['entity_import']['entity_import_tag'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => $section . '-entity-import-tag-field',
      ),
      '#states' => array(
        'invisible' => array(
          ':input[name="content[' . $section . '_wrapper][entity_import][entity_type]"]' => array('value' => ''),
        ),
      ),
    );

    return $form;
  }

  /**
   * Returns an array of entities based on data from entity_get_info().
   *
   * Filters out entities that do not contain a title field, as they cannot
   * be used to import content into templates.
   *
   * @return array
   *   Filtered entities from entity_get_info().
   */
  private function getEntitiesForContentImport() {
    $entity_info = \Drupal::entityManager()->getDefinitions();

    foreach ($entity_info as $key => $entity) {
      $entity_keys = $entity->getKeys();
      foreach ($entity_keys as $entity_key => $value) {
        if ($value == 'title') {
          $filtered_entities[$key] = $entity;
          continue;
        }
      }
    }

    return $filtered_entities;
  }

  /**
   * Gets form elements used in the merge vars feature.
   *
   * @param array $merge_vars
   *   Array of MailChimp merge vars for the current list.
   * @see mailchimp_get_mergevars
   * @param string $list_name
   *   The name of the current list.
   *
   * @return array
   *   Array of form elements used to display merge vars.
   */
  private function getMergeVarsFormElements($merge_vars, $list_name) {
    $form = array();

    $form['merge_vars'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'merge-vars-wrapper'
        ),
      ),
    );

    $form['merge_vars']['content'] = array(
      '#type' => 'item',
      '#title' => 'MailChimp merge variables',
      '#markup' => $this->buildMergeVarsHtml($merge_vars),
      '#description' => t(
        'Insert merge variables from the %list_name list or one of the !standard_link.',
        array(
          '%list_name' => $list_name,
          '!standard_link' => l(
            t('standard MailChimp merge variables'), 'http://kb.mailchimp.com/article/all-the-merge-tags-cheatsheet',
            array('attributes' => array('target' => '_blank'))),
        )
      ),
    );

    return $form;
  }

  /**
   * Builds a HTML string used to render merge vars on the campaign form.
   *
   * @param array $merge_vars
   *   Array of merge vars. @see mailchimp_lists_get_merge_vars
   *
   * @return string
   *   HTML string containing formatted merge vars.
   */
  function buildMergeVarsHtml($merge_vars) {
    if (!empty($merge_vars)) {
      $rows = array();
      foreach ($merge_vars as $var) {
        $rows[] = array(
          $var['name'],
          '<a id="merge-var-' . $var['tag'] . '" class="add-merge-var" href="javascript:void(0);">*|' . $var['tag'] . '|*</a>',
        );
      }
      $table = theme('table', array('rows' => $rows));
      return render($table);
    }
    else {
      return t('No custom merge vars exist for the current list.');
    }
  }

}

?>
