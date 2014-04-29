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
    $items[$this->path]['title'] = 'Signup Forms';
    $items[$this->path]['description'] = 'Manage MailChimp Signup blocks and pages.';
    $items[$this->path]['type'] = MENU_LOCAL_TASK;
    $items[$this->path]['weight'] = 10;
    $items[$this->path]['access callback'] = 'mailchimp_apikey_ready_access';

    return $items;
  }

}
