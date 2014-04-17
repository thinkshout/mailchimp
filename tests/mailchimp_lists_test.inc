<?php
/**
* @file
* A virtual MailChimp Lists API implementation for use in testing.
*/

class MailChimp_ListsTest extends Mailchimp_Lists {
  const MAILCHIMP_TEST_LIST_A = 'mailchimp_test_list_a';
  const MAILCHIMP_TEST_LIST_B = 'mailchimp_test_list_b';
  const MAILCHIMP_TEST_LIST_C = 'mailchimp_test_list_c';

  /** @var string $errorMessage */
  public $errorMessage;

  public function __construct(MailChimpTest $master) {
    parent::__construct($master);
  }

  /**
   * @see Mailchimp_Lists::memberInfo()
   */
  public function memberInfo($id, $emails) {
    $lists = $this->loadLists();

    $response = array(
      'success_count' => 0,
      'error_count' => 0,
      'data' => array(),
    );
    foreach ($lists as $list_data) {
      if (isset($list_data['data']['members'])) {
        $response['success_count'] += count($list_data['data']['members']);
        foreach ($emails as $email) {
          $email_address = $email['email'];
          if (isset($list_data['data']['members'][$email_address])) {
            $member_data = $list_data['data']['members'][$email_address];
            $response['data'][] = array(
              'id' => $email_address,
              'email' => $email_address,
              'email_type' => $member_data['email_type'],
              'status' => 'subscribed',
            );
          }
        }
      }
    }
    return $response;
  }

  /**
   * @see Mailchimp_Lists::mergeVars()
   */
  public function mergeVars($id) {
    $lists = $this->loadLists();

    $response = array(
      'success_count' => 1,
      'error_count' => 0,
      'data' => array(),
    );
    foreach ($lists as $list_id => $list_data) {
      if (in_array($list_id, $id)) {
        $response['data'][] = array(
          'id' => $list_id,
          'name' => $list_data['name'],
          'merge_vars' => $list_data['merge_vars'],
        );
      }
    }
    return $response;
  }

  /**
   * @see Mailchimp_Lists::segments()
   */
  public function segments($id, $type=null) {

  }

  /**
   * @see Mailchimp_Lists::segmentAdd()
   */
  public function segmentAdd($id, $opts) {

  }

  /**
   * @see Mailchimp_Lists::staticSegmentMembersAdd()
   */
  public function staticSegmentMembersAdd($id, $seg_id, $batch) {

  }

  /**
   * @see Mailchimp_Lists::subscribe()
   */
  public function subscribe($id, $email, $merge_vars=null, $email_type='html', $double_optin=true, $update_existing=false, $replace_interests=true, $send_welcome=false) {
    $email_address = $email['email'];
    $lists = $this->loadLists();
    if (isset($lists[$id])) {
      if (!isset($lists[$id]['data']['members'])) {
        $lists[$id]['data']['members'] = array();
      }

      $leid = NULL;
      $euid = NULL;

      if (isset($lists[$id]['data']['members'][$email_address])) {
        $lists[$id]['data']['members'][$email_address]['status'] = 'subscribed';
      }
      else {
        $leid = uniqid();
        $euid = uniqid();

        $lists[$id]['data']['members'][$email_address] = array(
          'id' => $euid,
          'status' => 'subscribed',
          'email_type' => $email_type,
          'merge_vars' => $merge_vars,
        );
      }
      $this->writeLists($lists);

      $response = array(
        'email' => $email_address,
        'euid' => $euid,
        'leid' => $leid,
      );

      return $response;
    }
    else {
      $this->errorMessage = 'Could not add ' . $email_address . ' to non-existant list: ' . $id;
      return NULL;
    }
  }

  /**
   * @see Mailchimp_Lists::unsubscribe()
   */
  public function unsubscribe($id, $email, $delete_member=false, $send_goodbye=true, $send_notify=true) {
    $email_address = $email['email'];
    $lists = $this->loadLists();
    if (isset($lists[$id])) {
      if (isset($lists[$id]['data']['members'][$email_address])) {
        if ($lists[$id]['data']['members'][$email_address]['status'] == 'subscribed') {
          if ($delete_member) {
            unset($lists[$id]['data']['members'][$email_address]);
          }
          else {
            $lists[$id]['data']['members'][$email_address]['status'] = 'unsubscribed';
          }
          $this->writeLists($lists);

          return TRUE;
        }
        else {
          $this->errorMessage = 'Could not unsubscribe ' . $email_address . ' from: ' . $id . ': not currently subscribed.';
        }
      }
      else {
        $this->errorMessage = 'Could not unsubscribe ' . $email_address . ' from: ' . $id . ': address not on list';
      }
    }
    else {
      $this->errorMessage = 'Could not unsubscribe ' . $email_address . ' from non-existant list: ' . $id;
    }
    return FALSE;
  }

  /**
   * @see Mailchimp_Lists::updateMember()
   */
  public function updateMember($id, $email, $merge_vars, $email_type='', $replace_interests=true) {
    $email_address = $email['email'];
    $lists = $this->loadLists();
    if (isset($lists[$id])) {
      if (isset($lists[$id]['data']['members'][$email_address])) {
        if (!empty($merge_vars)) {
          $lists[$id]['data']['members'][$email_address]['merge_vars'] = $merge_vars;
        }
        $lists[$id]['data']['members'][$email_address]['email_type'] = $email_type;

        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @see Mailchimp_Lists::webhookAdd()
   */
  public function webhookAdd($id, $url, $actions=array(), $sources=array()) {

  }

  /**
   * @see Mailchimp_Lists::webhookDel()
   */
  public function webhookDel($id, $url) {

  }

  /**
   * @see Mailchimp_Lists::webhooks()
   */
  public function webhooks($id) {

  }

  /**
   * @see Mailchimp_Lists::getList()
   */
  public function getList($filters=array(), $start=0, $limit=25, $sort_field='created', $sort_dir='DESC') {
    $lists = $this->loadLists();

    $response = array(
      'data' => array(),
      'total' => 0,
    );

    foreach ($lists as $list_id => $list_data) {
      $list_data['id'] = $list_id;
      $response['data'][] = $list_data;
      $response['total']++;
    }

    return $response;
  }

  /**
   * Loads list values, initializing if necessary.
   *
   * @return array
   *   Stored lists.
   */
  protected function loadLists() {
    $list_data = variable_get('mailchimp_test_list_data', $this->defaultLists());

    return $list_data;
  }

  /**
   * Creates initial list values.
   *
   * @return array
   *   Basic lists.
   */
  protected function defaultLists() {
    $default_mergevars = array();
    $default_mergevars[] = array(
      'name' => 'Email',
      'order' => 0,
      'tag' => 'EMAIL',
      'req' => TRUE,
      'web_id' => 'test',
      'field_type' => 'text',
      'size' => 40,
      'default' => '',
      'public' => TRUE,
    );
    $default_mergevars[] = array(
      'name' => 'First Name',
      'order' => 1,
      'tag' => 'FIRSTNAME',
      'req' => FALSE,
      'web_id' => 'test',
      'field_type' => 'text',
      'size' => 40,
      'default' => '',
      'public' => TRUE,
    );
    $default_mergevars[] = array(
      'name' => 'Last Name',
      'order' => 2,
      'tag' => 'LASTNAME',
      'req' => FALSE,
      'web_id' => 'test',
      'field_type' => 'text',
      'size' => 40,
      'default' => '',
      'public' => TRUE,
    );
    $lists = array(
      self::MAILCHIMP_TEST_LIST_A => array(
        'name' => 'Test List A',
        'data' => array(),
        'merge_vars' => $default_mergevars,
        'stats' => array(
          'group_count' => 0,
        ),
      ),
      self::MAILCHIMP_TEST_LIST_B => array(
        'name' => 'Test List B',
        'data' => array(),
        'merge_vars' => $default_mergevars,
        'stats' => array(
          'group_count' => 0,
        ),
      ),
      self::MAILCHIMP_TEST_LIST_C => array(
        'name' => 'Test List C',
        'data' => array(),
        'merge_vars' => $default_mergevars,
        'stats' => array(
          'group_count' => 0,
        ),
      ),
    );
    return $lists;
  }

  /**
   * Saves list changes.
   */
  protected function writeLists($lists) {
    variable_set('mailchimp_test_list_data', $lists);
  }
}