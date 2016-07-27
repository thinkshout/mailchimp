<?php

namespace Drupal\mailchimp_signup\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

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

    $block_mode = [
      '#title' => $this->t('Block'),
      '#type' => 'link',
      '#url' => $block_url
    ];

    $page_mode = [
      '#title' => $this->t('Page'),
      '#type' => 'link',
      '#url' => $page_url
    ];

    $modes = NULL;
    $mc_lists = mailchimp_get_lists();

    switch ($entity->mode) {
      case MAILCHIMP_SIGNUP_BLOCK:
        $modes = $block_mode;
        break;
      case MAILCHIMP_SIGNUP_PAGE:
        $modes = $page_mode;
        break;
      case MAILCHIMP_SIGNUP_BOTH:
        $modes = array(
          'block_link' => $block_mode,
          'separator' => array(
            '#markup' => ' and ',
          ),
          'page_link' => $page_mode
        );
        break;
    }

    $list_labels = array();
    foreach ($entity->mc_lists as $list_id) {
      if (!empty($list_id)) {
        $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_lists[$list_id]->id, array('attributes' => array('target' => '_blank')));
        $list_link = [
          '#title' => $this->t($mc_lists[$list_id]->name),
          '#type' => 'link',
          '#url' => $list_url
        ];
        $list_labels[] = $list_link;
        $list_labels[] = array('#markup' => ', ');
      }
    }

    // Remove the last comma from the $list_labels array.
    array_pop($list_labels);

    $row['label'] = $this->getLabel($entity) . ' (Machine name: ' . $entity->id() . ')';
    $row['display_modes']['data'] = $modes;
    $row['lists']['data'] = $list_labels;

    return $row + parent::buildRow($entity);
  }

}
