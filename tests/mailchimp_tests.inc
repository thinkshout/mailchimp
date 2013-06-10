<?php

global $mailchimp_list_data;

class MailChimpTest extends MailChimp {

  public function __construct() {
  }

  public function listMemberInfo($id, $email_address) {
    $lists = $this->loadLists();
    $ret = array(
      'success' => 0,
      'errors' => 0,
      'data' => array(),
    );
    if (isset($lists[$id]['data'][$email_address[0]])) {
      $member = $lists[$id]['data'][$email_address[0]];
      $ret['success'] = 1;
      $ret['data'][] = array(
        'email' => $email_address[0],
        'email_type' => $member['email_type'],
        'merges' => $member['merge_vars'],
        'status' => $member['subscribed'] ? 'subscribed' : 'unsubscribed',
      );
    }
    else {
      $ret['errors'] = 1;
    }
    return $ret;
  }
  }

  public function listSubscribe($id, $email_address, $merge_vars = NULL, $email_type = 'html', $double_optin = true, $update_existing = false, $replace_interests = true, $send_welcome = false) {
    $lists = $this->loadLists();
    if (isset($lists[$id])) {
      if (isset($lists[$id]['data'][$email_address])) {
        $lists[$id]['data'][$email_address]['subscribed'] = TRUE;
      }
      else {
        $lists[$id]['data'][$email_address] = array(
          'subscribed' => TRUE,
          'email_type' => $email_type,
          'merge_vars' => $merge_vars,
        );
      }
      $this->writeLists($lists);

      return TRUE;
    }
    else {
      $this->errorMessage = "Could not add " . $email_address . " to non-existant list: " . $id;
      return FALSE;
    }
  }

  public function listUnsubscribe($id, $email_address, $delete_member = false, $send_goodbye = true, $send_notify = true) {
    $lists = $this->loadLists();
    if (isset($lists[$id])) {
      if (isset($lists[$id]['data'][$email_address])) {
        if ($lists[$id]['data'][$email_address]['subscribed']) {
          if ($delete_member) {
            unset($lists[$id]['data'][$email_address]);
          }
          else {
            $lists[$id]['data'][$email_address]['subscribed'] = FALSE;
          }
          $this->writeLists($lists);

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
    $lists = $this->loadLists();
    if (isset($lists[$id])) {
      if (isset($lists[$id]['data'][$email_address])) {
        foreach ($merge_vars as $var => $value) {
          $lists[$id]['data'][$email_address]['merge_vars'][$var] = $value;
        }
        if (isset($merge_vars['EMAIL']) && strcmp($email_address, $merge_vars['EMAIL'])) {
          $lists[$id][$merge_vars['EMAIL']] = $lists[$id]['data'][$email_address];
          unset($lists[$id]['data'][$email_address]);
        }
        $this->writeLists($lists);

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
    $lists = $this->loadLists();
    $ret = array(
      'data' => array(),
      'total' => 0,
    );
    foreach ($lists as $list_id => $list_array) {
      $ret['data'][] = array(
        'id' => $list_id,
        'name' => $list_array['name'],
      );
      $ret['total']++;
    }
    return $ret;
  }

  protected function loadLists() {
    $list_data = &$GLOBALS['mailchimp_list_data'];
    if (!isset($list_data['lists'])) {
      $lists = array(
        'test_list_A' => array(
          'name' => 'Test List A',
          'data' => array(),
        ),
        'test_list_B' => array(
          'name' => 'Test List B',
          'data' => array(),
        ),
        'test_list_C' => array(
          'name' => 'Test List C',
          'data' => array(),
        ),
      );
      $this->writeLists($lists);
    }

    return $list_data['lists'];
  }

  protected function writeLists($lists) {
    $list_data = &$GLOBALS['mailchimp_list_data'];
    $list_data['lists'] = $lists;
  }
}
