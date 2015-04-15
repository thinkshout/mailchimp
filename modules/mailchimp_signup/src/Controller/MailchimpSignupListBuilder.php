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
    $header['access'] = $this->t('Page Access');

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
    $block_only = FALSE;
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
        $list_url = Url::fromUri('https://admin.mailchimp.com/lists/dashboard/overview?id=' . $mc_lists[$list_id]['web_id']);
        $list_labels[] = \Drupal::l($mc_lists[$list_id]['name'], $list_url);
      }
    }

    if ($block_only) {
      $access = 'N/A - this form only exists as a block';
    }
    else {
      $all_roles_allowed = user_roles(FALSE, 'mailchimp_signup_all_forms' . $entity->name);
      $page_roles_allowed = user_roles(FALSE, 'mailchimp_signup_form_' . $entity->name);
      $roles_allowed = array_merge($all_roles_allowed, $page_roles_allowed);
      $access = implode(', ', $roles_allowed);
      $permissions_url = Url::fromRoute('user.admin_permissions');
      $actions[] = \Drupal::l(t('Permissions'), $permissions_url, array('fragment' => 'edit-mailchimp-signup-all-forms'));
    }

    $row['label'] = $this->getLabel($entity) . ' (Machine name: ' . $entity->id() . ')';
    $row['display_modes'] = $modes;
    $row['lists'] = implode(', ', $list_labels);
    $row['access'] = $access;

    return $row + parent::buildRow($entity);
  }

}
