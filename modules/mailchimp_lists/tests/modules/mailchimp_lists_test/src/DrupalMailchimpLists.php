<?php
/**
 * @file
 * Contains \Drupal\mailchimp_lists_test\DrupalMailchimpLists.
 *
 * A virtual MailChimp API implementation for use in testing.
 */

namespace Drupal\mailchimp_lists_test;

class DrupalMailchimpLists {

  const TEST_LIST_A = 'mailchimp_test_list_a';
  const TEST_LIST_B = 'mailchimp_test_list_b';
  const TEST_LIST_C = 'mailchimp_test_list_c';
  const TEST_LIST_INVALID = 'mailchimp_invalid_list';

  const TEST_SEGMENT_A = 1;
  const TEST_SEGMENT_B = 2;
  const TEST_SEGMENT_C = 3;

  /**
   * List data used in tests.
   *
   * @var array
   */
  protected $list_data;

  public function __construct() {
    $this->list_data = $this->getInitialTestListData();
  }

  /**
   * @see Mailchimp_Lists::memberInfo()
   */
  public function memberInfo($id, $emails) {
    $lists = $this->list_data;

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
            $response['data'][] = $list_data['data']['members'][$email_address];
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
    $lists = $this->list_data;

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
    $lists = $this->list_data;

    $response = array(
      'static' => array(),
      'saved' => array(),
    );
    if (isset($lists[$id])) {
      $response['static'] = $lists[$id]['segments'];
      $response['saved'] = $lists[$id]['segments'];
    }
    return $response;
  }

  /**
   * @see Mailchimp_Lists::segmentAdd()
   */
  public function segmentAdd($id, $opts) {
    $lists = $this->list_data;

    $response = array();
    if (isset($lists[$id])) {
      $new_id = uniqid();
      $new_segment = array(
        'id' => $new_id,
        'name' => $opts['name'],
      );
      if (isset($opts['segment_opts'])) {
        $new_segment['segment_opts'] = $opts['segment_opts'];
      }
      $lists[$id]['segments'][] = $new_segment;
      $response['id'] = $new_id;
    }
    else
    {
      $response['status'] = 'error';
      $response['code'] = 200;
      $response['name'] = 'List_DoesNotExist';
    }
    return $response;
  }

  /**
   * @see Mailchimp_Lists::staticSegmentMembersAdd()
   */
  public function staticSegmentMembersAdd($id, $seg_id, $batch) {
    $lists = $this->list_data;

    $response = array();
    if (isset($lists[$id])) {
      $response['success_count'] = 0;
      foreach ($batch as $batch_email) {
        if (isset($lists[$id]['data']['members'][$batch_email['email']])) {
          // No need to store this data, just increment success count.
          $response['success_count']++;
        }
      }
    }
    else
    {
      $response['status'] = 'error';
      $response['code'] = 200;
      $response['name'] = 'List_DoesNotExist';
    }
    return $response;
  }

  /**
   * @see Mailchimp_Lists::subscribe()
   */
  public function subscribe($id, $email, $merge_vars=null, $email_type='html', $double_optin=true, $update_existing=false, $replace_interests=true, $send_welcome=false) {
    $lists = $this->list_data;

    $email_address = $email['email'];

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
          'leid' => $leid,
          'status' => 'subscribed',
          'email' => $email_address,
          'email_type' => $email_type,
          'merge_vars' => $merge_vars,
        );
      }
      $this->saveTestListData($lists);

      $response = array(
        'email' => $email_address,
        'euid' => $euid,
        'leid' => $leid,
      );

      return $response;
    }
    else {
      return NULL;
    }
  }

  /**
   * @see Mailchimp_Lists::unsubscribe()
   */
  public function unsubscribe($id, $email, $delete_member=false, $send_goodbye=true, $send_notify=true) {
    $lists = $this->list_data;

    $email_address = $email['email'];

    if (isset($lists[$id])) {
      if (isset($lists[$id]['data']['members'][$email_address])) {
        if ($lists[$id]['data']['members'][$email_address]['status'] == 'subscribed') {
          if ($delete_member) {
            unset($lists[$id]['data']['members'][$email_address]);
          }
          else {
            $lists[$id]['data']['members'][$email_address]['status'] = 'unsubscribed';
          }
          $this->saveTestListData($lists);

          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * @see Mailchimp_Lists::updateMember()
   */
  public function updateMember($id, $email, $merge_vars, $email_type='', $replace_interests=TRUE) {
    $lists = $this->list_data;

    $email_address = $email['email'];

    $response = array();
    if (isset($lists[$id])) {
      if (isset($lists[$id]['data']['members'][$email_address])) {
        if (!empty($merge_vars)) {
          $lists[$id]['data']['members'][$email_address]['merge_vars'] = $merge_vars;
        }
        $lists[$id]['data']['members'][$email_address]['email_type'] = $email_type;

        $response['email'] = $email_address;
      }
    }
    else
    {
      $response['status'] = 'error';
      $response['code'] = 200;
      $response['name'] = 'List_DoesNotExist';
    }
    return $response;
  }

  /**
   * @see Mailchimp_Lists::webhookAdd()
   */
  public function webhookAdd($id, $url, $actions=array(), $sources=array()) {
    $lists = $this->list_data;

    if (isset($lists[$id])) {
      $new_id = (count($lists[$id]['webhooks']) + 1);
      $webhook = array(
        'id' => $new_id,
        'url' => $url,
        'actions' => $actions,
        'sources' => $sources,
      );
      $lists[$id]['webhooks'][] = $webhook;

      $response = array(
        'id' => $new_id,
      );

      $this->saveTestListData($lists);
    }
    else
    {
      $response = array(
        'status' => 'error',
        'code' => 200,
        'name' => 'List_DoesNotExist',
      );
    }
    return $response;
  }

  /**
   * @see Mailchimp_Lists::webhookDel()
   */
  public function webhookDel($id, $url) {
    $lists = $this->list_data;

    if (isset($lists[$id])) {
      $complete = FALSE;
      foreach ($lists[$id]['webhooks'] as $key => $webhook) {
        if ($webhook['url'] == $url) {
          unset($lists[$id]['webhooks'][$key]);
          $this->saveTestListData($lists);
          $complete = TRUE;
          continue;
        }
      }

      $response = array(
        'complete' => $complete,
      );
    }
    else
    {
      $response = array(
        'status' => 'error',
        'code' => 200,
        'name' => 'List_DoesNotExist',
      );
    }
    return $response;
  }

  /**
   * @see Mailchimp_Lists::webhooks()
   */
  public function webhooks($id) {
    $lists = $this->list_data;

    if (isset($lists[$id])) {
      $response = $lists[$id]['webhooks'];
    }
    else
    {
      $response = array(
        'status' => 'error',
        'code' => 200,
        'name' => 'List_DoesNotExist',
      );
    }
    return $response;
  }

  /**
   * @see Mailchimp_Lists::getList()
   */
  public function getList($filters=array(), $start=0, $limit=25, $sort_field='created', $sort_dir='DESC') {
    $lists = $this->list_data;

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
   * Creates initial list data.
   *
   * @return array
   *   Basic lists.
   */
  protected function getInitialTestListData() {
    $default_mergevars = array(
      array(
        'name' => 'Email',
        'order' => 0,
        'tag' => 'EMAIL',
        'req' => TRUE,
        'web_id' => 'test',
        'field_type' => 'text',
        'size' => 40,
        'default' => '',
        'public' => TRUE,
      ),
      array(
        'name' => 'First Name',
        'order' => 1,
        'tag' => 'FIRSTNAME',
        'req' => FALSE,
        'web_id' => 'test',
        'field_type' => 'text',
        'size' => 40,
        'default' => '',
        'public' => TRUE,
      ),
      array(
        'name' => 'Last Name',
        'order' => 2,
        'tag' => 'LASTNAME',
        'req' => FALSE,
        'web_id' => 'test',
        'field_type' => 'text',
        'size' => 40,
        'default' => '',
        'public' => TRUE,
      ),
    );

    $default_segments = array(
      array(
        'id' => self::TEST_SEGMENT_A,
        'name' => 'Test Segment A',
      ),
      array(
        'id' => self::TEST_SEGMENT_B,
        'name' => 'Test Segment B',
      ),
      array(
        'id' => self::TEST_SEGMENT_C,
        'name' => 'Test Segment C',
      ),
    );

    $default_webhooks = array(
      array(
        'url' => 'http://example.org/web-hook-subscribe',
        'actions' => array(
          'subscribe' => TRUE
        ),
        'sources' => array(
          'user' => TRUE,
          'admin' => TRUE,
          'api' => TRUE,
        ),
      ),
      array(
        'url' => 'http://example.org/web-hook-unsubscribe',
        'actions' => array(
          'unsubscribe' => TRUE
        ),
        'sources' => array(
          'user' => TRUE,
          'admin' => TRUE,
          'api' => TRUE,
        ),
      ),
    );

    $lists = array(
      self::TEST_LIST_A => array(
        'name' => 'Test List A',
        'data' => array(),
        'merge_vars' => $default_mergevars,
        'segments' => $default_segments,
        'webhooks' => $default_webhooks,
        'stats' => array(
          'group_count' => 0,
        ),
      ),
      self::TEST_LIST_B => array(
        'name' => 'Test List B',
        'data' => array(),
        'merge_vars' => $default_mergevars,
        'segments' => $default_segments,
        'webhooks' => $default_webhooks,
        'stats' => array(
          'group_count' => 0,
        ),
      ),
      self::TEST_LIST_C => array(
        'name' => 'Test List C',
        'data' => array(),
        'merge_vars' => $default_mergevars,
        'segments' => $default_segments,
        'webhooks' => $default_webhooks,
        'stats' => array(
          'group_count' => 0,
        ),
      ),
    );

    return $lists;
  }

  protected function saveTestListData($lists) {
    $this->list_data = $lists;
  }

}
