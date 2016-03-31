<?php
/**
 * @file
 * Contains \Drupal\mailchimp_signup\Controller\MailchimpSignupListBuilder.
 */

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

    $modes = NULL;
    $mc_lists = mailchimp_get_lists();

    switch ($entity->mode) {
      case MAILCHIMP_SIGNUP_BLOCK:
        $mode_url = Link::fromTextAndUrl('Block', $block_url);
        $modes = $mode_url->toRenderable();

        break;
      case MAILCHIMP_SIGNUP_PAGE:
        $mode_url = Link::fromTextAndUrl('Page', $page_url);
        $modes = $mode_url->toRenderable();
        break;
      case MAILCHIMP_SIGNUP_BOTH:
        $block_link = Link::fromTextAndUrl('Block', $block_url);
        $page_link = Link::fromTextAndUrl('Page', $page_url);

        $modes = array(
          'block_link' => $block_link->toRenderable(),
          'separator' => array(
            '#markup' => ' and ',
          ),
          'page_link' => $page_link->toRenderable(),
        );
        break;
    }

    $list_labels = array();
    foreach ($entity->mc_lists as $list_id) {
      if (!empty($list_id)) {
        $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_lists[$list_id]->id, array('attributes' => array('target' => '_blank')));
        $list_link = Link::fromTextAndUrl($mc_lists[$list_id]->name, $list_url);
        $list_labels[] = $list_link->toRenderable();
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
