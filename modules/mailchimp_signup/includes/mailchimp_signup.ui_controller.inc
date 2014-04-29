<?php

/**
 * @file
 * Class file for Mailchimp Signup UI Controller.
 */

/**
 * Override EntityDefaultUIController to customize our menu items.
 */
class MailChimpSignupUIController extends EntityDefaultUIController {

  /**
   * Overrides parent::hook_menu().
   */
  public function hook_menu() {
    $items = parent::hook_menu();
    return $items;
  }

}
