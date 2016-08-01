<?php

namespace Drupal\mailchimp_campaign\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to add content to and convert URLs for MailChimp campaigns.
 *
 * @Filter(
 *   id = "filter_mailchimp_campaign",
 *   title = @Translation("MailChimp Campaign filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {}
 * )
 */
class FilterMailchimpCampaign extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // Replace node macros with entity content.
    $pattern = '/\[mailchimp_campaign\|entity_type=(\w+)\|entity_id=(\d+)\|view_mode=(\w+)\]/s';
    $text = preg_replace_callback($pattern, array($this, 'processCallback'), $text);

    // Convert URL to absolute.
    $text = $this->convertUrl($text);

    $result->setProcessedText($text);

    return $result;
  }

  /**
   * Callback for preg_replace in process()
   */
  public static function processCallback($matches = array()) {
    $content = '';
    $entity_type = $entity_id = $view_mode = '';
    foreach ($matches as $key => $match) {
      switch ($key) {
        case 1:
          $entity_type = $match;
          break;
        case 2:
          $entity_id = $match;
          break;
        case 3:
          $view_mode = $match;
          break;
      }
    }

    $entity_manager = \Drupal::entityManager();
    $entity = $entity_manager->getStorage($entity_type)->load($entity_id);
    if (!empty($entity)) {
      $render_controller = \Drupal::entityManager()->getViewBuilder($entity->getEntityTypeId());
      $render_array = $render_controller->view($entity, $view_mode);
      // Remove contextual links.
      if (isset($render_array[$entity_type][$entity_id]['#contextual_links'])) {
        unset($render_array[$entity_type][$entity_id]['#contextual_links']);
      }
      $content = render($render_array);
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $tip = t('Converts content tokens in the format %pattern into the appropriate rendered content and makes all paths absolute. Use the "Insert Site Content" widget below to generate tokens.',
      array('%pattern' => '[mailchimp_campaign|entity_type=node|entity_id=1|view_mode=teaser]')
    );

    return $tip;
  }

  /**
   * Change the relative URLs to absolute ones in the message.
   */
  private function convertUrl($text) {
    global $base_url;
    $matches = array();
    preg_match_all('/<(a|img).*?(href|src)="(.*?)"/', $text, $matches);
    foreach ($matches[3] as $key => $url) {
      if ($url[0] == '/') {
        $new_url = $base_url . $url;
        $new_match = str_replace($url, $new_url, $matches[0][$key]);
        $text = str_replace($matches[0][$key], $new_match, $text);
      }
    }

    return $text;
  }

}
