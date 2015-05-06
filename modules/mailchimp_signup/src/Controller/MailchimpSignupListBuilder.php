<?php
/**
 * @file
 * Contains \Drupal\mailchimp_signup\Controller\MailchimpSignupListBuilder.
 */

namespace Drupal\mailchimp_signup\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of MailchimpSignups.
 *
 * @ingroup mailchimp_signup
 */
class MailchimpSignupListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['display_modes'] = $this->t('Display Modes');
    $header['lists'] = $this->t('MailChimp Lists');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    global $base_url;

    $block_url = Url::fromRoute('block.admin_display');
    $page_url = Url::fromUri($base_url . '/' . $entity->settings['path']);

    $modes = NULL;
    $mc_lists = mailchimp_get_lists();

    switch ($entity->mode) {
      case MAILCHIMP_SIGNUP_BLOCK:
        $modes = \Drupal::l(t('Block'), $block_url);
        $block_only = TRUE;
        break;
      case MAILCHIMP_SIGNUP_PAGE:
        $modes = \Drupal::l(t('Page'), $page_url);
        break;
      case MAILCHIMP_SIGNUP_BOTH:
        $modes = \Drupal::l(t('Block'), $block_url) . ' and ' . \Drupal::l(t('Page'), $page_url);
        break;
    }

    $list_labels = array();
    foreach ($entity->mc_lists as $list_id) {
      if (!empty($list_id)) {
        $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_lists[$list_id]['web_id'], array('attributes' => array('target' => '_blank')));
        $list_labels[] = \Drupal::l($mc_lists[$list_id]['name'], $list_url);
      }
    }

    $row['label'] = $this->getLabel($entity) . ' (Machine name: ' . $entity->id() . ')';
    $row['display_modes'] = $modes;
    $row['lists'] = implode(', ', $list_labels);

    return $row + parent::buildRow($entity);
  }

}
