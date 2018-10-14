<?php

namespace Drupal\mailchimp_campaign\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines the render controller for MailchimpCampaign entities.
 *
 * @ingroup mailchimp_campaign
 */
class MailchimpCampaignViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    // Attach campaign JS and CSS.
    $build['#attached']['library'][] = 'mailchimp_campaign/campaign-view';

    // Prepare rendered content.
    /* @var $entity \Drupal\mailchimp_campaign\Entity\MailchimpCampaign */
    $content = $this->renderTemplate($entity->getTemplate());
    $rendered = '';
    foreach ($content as $key => $section) {
      $rendered .= "<h3>$key</h3>" . $section;
    }

    if (!$entity->isInitialized()) {
      drupal_set_message('The campaign could not be found.', 'error');
      return $build;
    }

    // Get the template name.
    $mc_template = mailchimp_campaign_get_template($entity->mc_data->settings->template_id);
    $mc_template_name = isset($mc_template) ? $mc_template->name : '';

    $list_segment_name = 'N/A';

    $list_segments = mailchimp_campaign_get_list_segments($entity->list->id, 'saved');
    if (isset($entity->mc_data->recipients->segment_opts->saved_segment_id)) {
      foreach ($list_segments as $list_segment) {
        if ($list_segment->id == $this->mc_data->recipients->segment_opts->saved_segment_id) {
          $list_segment_name = $list_segment->name;
        }
      }
    }

    $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $entity->list->id, array('attributes' => array('target' => '_blank')));
    $archive_url = Url::fromUri($entity->mc_data->archive_url, array(
      'attributes' => array('target' => '_blank')));
    $send_time = 'N/A';

    if (isset($entity->mc_data->send_time) && $entity->mc_data->send_time) {
      $send_time = \Drupal::service('date.formatter')
        ->format(strtotime($entity->mc_data->send_time), 'custom', 'F j, Y - g:ia');
    }

    $fields = array(
      'title' => array(
        'label' => t('Title'),
        'value' => $entity->mc_data->settings->title,
      ),

      'subject' => array(
        'label' => t('Subject'),
        'value' => $entity->mc_data->settings->subject_line,
      ),
      'list' => array(
        'label' => t('Mailchimp List'),
        'value' => Link::fromTextAndUrl($entity->list->name, $list_url)->toString(),
      ),
      'list_segment' => array(
        'label' => t('List Segment'),
        'value' => $list_segment_name,
      ),
      'from_email' => array(
        'label' => t('From Email'),
        'value' => $entity->mc_data->settings->reply_to,
      ),
      'from_name' => array(
        'label' => t('From Name'),
        'value' => $entity->mc_data->settings->from_name,
      ),
      'template' => array(
        'label' => t('Template'),
        'value' => $mc_template_name,
      ),
      'type' => array(
        'label' => t('List type'),
        'value' => $entity->mc_data->type,
      ),
      'status' => array(
        'label' => t('Status'),
        'value' => $entity->mc_data->status,
      ),
      'emails_sent' => array(
        'label' => t('Emails sent'),
        'value' => $entity->mc_data->emails_sent,
      ),
      'send_time' => array(
        'label' => t('Send time'),
        'value' => $send_time,
      ),
      'content' => array(
        'label' => t('Rendered template HTML (@archive)',
          array(
            '@archive' => Link::fromTextAndUrl('View Mailchimp archive', $archive_url)->toString(),
            )
          ),
        'value' => $rendered,
      ),
    );

    foreach ($fields as $key => $field) {
      $build[$key] = array(
        '#prefix' => "<div class=\"field campaign-{$key}\"><h3 class=\"field-label\">{$field['label']}</h3>",
        '#markup' => "<p>{$field['value']}</p>",
        '#suffix' => '</div>',
      );
    }

    return $build;
  }

  /**
   * Converts a template into rendered content.
   *
   * @param array $template
   *   Array of template sections.
   *
   * @return array
   *   Array of template content indexed by section ID.
   */
  private function renderTemplate($template) {
    $content = array();
    foreach ($template as $key => $part) {
      if (isset($part['format'])) {
        $content[$key] = check_markup($part['value'], $part['format']);
      }
    }

    return $content;
  }

}
