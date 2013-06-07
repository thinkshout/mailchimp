<?php

class MailChimpTest extends MailChimp {
  
  var $lists;
  
  function MailChimpTest() {
    $lists = array(
      'test_list_A' => 'Test List A',
      'test_list_B' => 'Test List B',
      'test_list_C' => 'Test List C',
    );
  }

  public function listSubscribe($id, $email_address, $merge_vars = NULL, $email_type = 'html', $double_optin = true, $update_existing = false, $replace_interests = true, $send_welcome = false) {
    if (isset($lists[$id])) {
      if (isset($lists[$id][$email_address])) {
        $lists[$id][$email_address]['subscribed'] = TRUE;
      }
      else {
        $lists[$id][$email_address] = array(
          'subscribed' => TRUE,
          'email_type' => $email_type,
          'merge_vars' => $merge_vars,
        );
      }
      return TRUE;
    }
    else {
      $this->errorMessage = "Could not add " . $email_address . " to non-existant list: " . $id;
      return FALSE;
    }
  }

  public function listUnsubscribe($id, $email_address, $delete_member = false, $send_goodbye = true, $send_notify = true) {
    if (isset($lists[$id])) {
      if (isset($lists[$id][$email_address])) {
        if ($lists[$id][$email_address]['subscribed']) {
          if ($delete_member) {
            unset($lists[$id][$email_address]);
          }
          else {
            $lists[$id][$email_address]['subscribed'] = FALSE;
          }
          return TRUE;
        }
        else {
          $this->errorMessage = "Could not unsubscribe " . $email_address . " from: " . $id . ": not currently subscribed.";
        }
      }
      else {
        $this->errorMessage = "Could not unsubscribe " . $email_address . " from: " . $id . ": address not on list";
      }
    }
    else {
      $this->errorMessage = "Could not unsubscribe " . $email_address . " from non-existant list: " . $id;
    }
    return FALSE;
  }

  public function listUpdateMember($id, $email_address, $merge_vars, $email_type = '', $replace_interests = true) {
    if (isset($lists[$id])) {
      if (isset($lists[$id][$email_address])) {
        foreach ($merge_vars as $var => $value) {
          $lists[$id][$email_address]['merge_vars'][$var] = $value;
        }
        if (isset($merge_vars['EMAIL']) && strcmp($email_address, $merge_vars['EMAIL'])) {
          $lists[$id][$merge_vars['EMAIL']] = $lists[$id][$email_address];
          unset($lists[$id][$email_address]);
        }
        return TRUE;
      }
      else {
        $this->errorMessage = "Could not update " . $email_address . " on: " . $id . ": not currently a member.";
      }
    }
    else {
      $this->errorMessage = "Could not update " . $email_address . " on non-existant list: " . $id;
    }
    return FALSE;
  }
  
  public function lists($filters = array(), $start = 0, $limit = 25, $sort_field = 'created', $sort_dir = 'DESC') {
    $ret = array();
    foreach ($this->lists as $list_id => $list_name) {
      $ret[] = array(
        'id' => $list_id,
        'name' => $list_name,
      );
    }
    return $ret;
  }
}
