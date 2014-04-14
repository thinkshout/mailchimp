<?php
/**
* @file
* A virtual MailChimp Lists API implementation for use in testing.
*/

class MailChimp_ListsTest extends Mailchimp_Lists {
  const MAILCHIMP_TEST_LIST_A = 'mailchimp_test_list_a';
  const MAILCHIMP_TEST_LIST_B = 'mailchimp_test_list_b';
  const MAILCHIMP_TEST_LIST_C = 'mailchimp_test_list_c';

  /**
   * @see Mailchimp_Lists::memberInfo()
   */
  public function memberInfo($id, $emails) {
    // TODO: Populate method.
  }

  /**
   * @see Mailchimp_Lists::subscribe()
   */
  public function subscribe($id, $email, $merge_vars=null, $email_type='html', $double_optin=true, $update_existing=false, $replace_interests=true, $send_welcome=false) {
    // TODO: Populate method.
  }

  /**
   * @see Mailchimp_Lists::unsubscribe()
   */
  public function unsubscribe($id, $email, $delete_member=false, $send_goodbye=true, $send_notify=true) {
    // TODO: Populate method.
  }

  /**
   * @see Mailchimp_Lists::unsubscribe()
   */
  public function getList($filters=array(), $start=0, $limit=25, $sort_field='created', $sort_dir='DESC') {
    $lists = $this->loadLists();

    $response = array(
      'data' => array(),
      'total' => 0,
    );

    foreach ($lists as $list_id => $list_data) {
      $list_data['id'] = $list_id;
      $response['data'][] = array(
        'id' => $list_id,
        'name' => $list_data['name'],
      );
      $response['count']++;
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
      MAILCHIMP_TEST_LIST_A => array(
        'name' => 'Test List A',
        'data' => array(),
        'mergevars' => $default_mergevars,
      ),
      MAILCHIMP_TEST_LIST_B => array(
        'name' => 'Test List B',
        'data' => array(),
        'mergevars' => $default_mergevars,
      ),
      MAILCHIMP_TEST_LIST_C => array(
        'name' => 'Test List C',
        'data' => array(),
        'mergevars' => $default_mergevars,
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