<?php

/**
 * @file
 * Class file for Mailchimp Activity UI Controller.
 */

/**
 * Override EntityDefaultUIController to customize our menu items.
 */
class MailChimpActivityUIController extends EntityDefaultUIController {

  /**
   * Overrides parent::hook_menu().
   */
  public function hook_menu() {
    $items = parent::hook_menu();
    $items[$this->path]['description'] = 'Manage MailChimp Activity entity settings.';
    $items[$this->path]['type'] = MENU_LOCAL_TASK;
    return $items;
  }

}
