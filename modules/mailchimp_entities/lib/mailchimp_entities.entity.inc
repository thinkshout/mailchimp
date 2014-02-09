<?php

class MailchimpEntity extends Entity {
  public
    $id,
    $name,
    $entity_type,
    $bundle,
    $entity_path,
    $email_property,
    $mc_list_id,
    $label,
    $description,
    $settings;

  /**
   * Override parent constructor to set the entity type.
   */
  public function __construct(array $values = array()) {
    parent::__construct($values, 'mailchimp_entity');
  }

  /**
   * Return a label for a Mailchimp Entity.
   */
  public function label() {
    return $this->label;
  }

}
