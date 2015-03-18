<?php

/**
 * @file
 * Contains \Drupal\mailchimp_signup\Entity\MailchimpSignup.
 */

namespace Drupal\mailchimp_signup\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\mailchimp_signup\MailchimpSignupInterface;

/**
 * Defines the Example entity.
 *
 * @ConfigEntityType(
 *   id = "mailchimp_signup",
 *   label = @Translation("Mailchimp Signup Form"),
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
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/mailchimp/signup/{mailchimp_signup}",
 *     "delete-form" = "/admin/config/services/mailchimp/signup/{mailchimp_signup}/delete"
 *   }
 * )
 */
class MailchimpSignup extends ConfigEntityBase implements MailchimpSignupInterface {

  /**
   * The Example ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Example label.
   *
   * @var string
   */
  public $label;

  // Your specific configuration property get/set methods go here,
  // implementing the interface.
}
?>
