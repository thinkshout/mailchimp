<?php

namespace Drupal\mailchimp_signup\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\mailchimp_signup\MailchimpSignupInterface;

/**
 * Defines the MailchimpSignup entity.
 *
 * @ingroup mailchimp_signup
 *
 * @ConfigEntityType(
 *   id = "mailchimp_signup",
 *   label = @Translation("Mailchimp Signup Form"),
 *   fieldable = FALSE,
 *   handlers = {
 *     "list_builder" = "Drupal\mailchimp_signup\Controller\MailchimpSignupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\mailchimp_signup\Form\MailchimpSignupForm",
 *       "edit" = "Drupal\mailchimp_signup\Form\MailchimpSignupForm",
 *       "delete" = "Drupal\mailchimp_signup\Form\MailchimpSignupDeleteForm"
 *     }
 *   },
 *   config_prefix = "mailchimp_signup",
 *   admin_permission = "administer mailchimp_signup",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/mailchimp/signup/{mailchimp_signup}",
 *     "delete-form" = "/admin/config/services/mailchimp/signup/{mailchimp_signup}/delete"
 *   }
 * )
 */
class MailchimpSignup extends ConfigEntityBase implements MailchimpSignupInterface {

  /**
   * The Signup ID.
   *
   * @var int
   */
  public $id;

  /**
   * The Signup Form Machine Name.
   *
   * @var string
   */
  public $name;

  /**
   * The Signup Form Title.
   *
   * @var string
   */
  public $title;

  /**
   * The Signup Form Mailchimp Lists.
   *
   * @var array
   */
  public $mc_lists;

  /**
   * The Signup Form Mode (Block, Page, or Both).
   *
   * @var int
   */
  public $mode;

  /**
   * The Signup Form Settings array.
   *
   * @var array
   */
  public $settings;

  /**
   * The Signup Form Status.
   *
   * @var boolean
   */
  public $status;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->title;
  }

}
