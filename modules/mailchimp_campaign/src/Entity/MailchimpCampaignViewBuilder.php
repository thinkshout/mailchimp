<?php

/**
 * @file
 * Contains \Drupal\mailchimp_campaign\Entity\MailchimpCampaignViewBuilder.
 */

namespace Drupal\mailchimp_campaign\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Url;

/**
 * Defines the render controller for MailchimpCampaign entities.
 */
class MailchimpCampaignViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    // Prepare rendered content.
    $content = $this->renderTemplate($entity->template);
    $rendered = '';
    foreach ($content as $key => $section) {
      $rendered .= "<h3>$key</h3>" . $section;
    }

    // Get the template name.
    $mc_template = mailchimp_campaign_get_template($entity->mc_data['template_id']);
    $mc_template_name = isset($mc_template) ? $mc_template['name'] : '';

    $list_segment_name = 'N/A';

    $list_segments = mailchimp_campaign_get_list_segments($entity->list['id'], 'saved');
    if (isset($entity->mc_data['saved_segment']['id'])) {
      foreach ($list_segments as $list_segment) {
        if ($list_segment['id'] == $entity->mc_data['saved_segment']['id']) {
          $list_segment_name = $list_segment['name'];
        }
      }
    }

    $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $entity->list['web_id']);
    $archive_url = Url::fromUri($entity->mc_data['archive_url']);

    $fields = array(
      'subject' => array(
        'label' => t('Subject'),
        'value' => $entity->mc_data['subject'],
      ),
      'list' => array(
        'label' => t('MailChimp List'),
        'value' => \Drupal::l($entity->list['name'], $list_url, array(
          'attributes' => array('target' => '_blank'),
        )),
      ),
      'list_segment' => array(
        'label' => t('List Segment'),
        'value' => $list_segment_name,
      ),
      'from_email' => array(
        'label' => t('From Email'),
        'value' => $entity->mc_data['from_email'],
      ),
      'from_name' => array(
        'label' => t('From Name'),
        'value' => $entity->mc_data['from_name'],
      ),
      'template' => array(
        'label' => t('Template'),
        'value' => $mc_template_name,
      ),
      'type' => array(
        'label' => t('List type'),
        'value' => $entity->mc_data['type'],
      ),
      'status' => array(
        'label' => t('Status'),
        'value' => $entity->mc_data['status'],
      ),
      'emails_sent' => array(
        'label' => t('Emails sent'),
        'value' => $entity->mc_data['emails_sent'],
      ),
      'send_time' => array(
        'label' => t('Send time'),
        'value' => $entity->mc_data['send_time'],
      ),
      'content' => array(
        'label' => t('Rendered template HTML (!archive)',
          array(
            '!archive' => \Drupal::l('View MailChimp archive', $archive_url, array(
              'attributes' => array('target' => '_blank'),
            )),
          )),
        'value' => $rendered,
      ),
    );

    foreach ($fields as $key => $field) {
      $build[$key] = array(
        '#prefix' => "<div class=\"field campaign-{$key}\"><div class=\"field-label\">{$field['label']}</div>",
        '#markup' => $field['value'],
        '#suffix' => '</div>',
      );
    }

    return $build;
  }

  /**
   * Converts a template into rendered content.
   *
   * @param FieldItemList $template
   *   List of template sections.
   *
   * @return array
   *   Array of template content indexed by section ID.
   */
  private function renderTemplate(FieldItemList $template) {
    $content = array();
    foreach ($template->list as $key => $part) {
      if (isset($part->format)) {
        $content[$key] = check_markup($part->value, $part->format);
      }
    }

    return $content;
  }

}
