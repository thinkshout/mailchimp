<?php

class MailchimpList extends Entity {
  public
    $id,
    $name,
    $mc_list_id,
    $label,
    $description,
    $settings,
    $created,
    $updated;

  /**
   * Override parent constructor to set the entity type.
   */
  public function __construct(array $values = array()) {
    parent::__construct($values, 'mailchimp_list');
  }

  /**
   * Override parent save to set timestamps.
   */
  public function save() {
    $this->updated = REQUEST_TIME;
    // Set the creation timestamp if not set.
    if (!isset($this->created) || empty($this->created)) {
      $this->created = REQUEST_TIME;
    }

    return parent::save();
  }

  /**
   * Return a label for a list.
   */
  public function label() {
    return $this->label();
  }

}
