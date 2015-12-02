<?php
/**
 * @file
 * Contains \Drupal\mailchimp_campaign_test\DrupalMailchimpCampaigns.
 *
 * A virtual MailChimp API implementation for use in testing.
 */

namespace Drupal\mailchimp_campaign_test;

class DrupalMailchimpCampaigns {

  const TEST_CAMPAIGN_A = 'mailchimp_test_campaign_a';
  const TEST_CAMPAIGN_B = 'mailchimp_test_campaign_b';
  const TEST_CAMPAIGN_C = 'mailchimp_test_campaign_c';

  const TEST_LIST_A = 'mailchimp_test_list_a';
  const TEST_LIST_B = 'mailchimp_test_list_b';
  const TEST_LIST_C = 'mailchimp_test_list_c';

  /**
   * Campaign data used in tests.
   *
   * @var array
   */
  protected $campaign_data;

  public function __construct() {
    $this->campaign_data = $this->getInitialTestCampaignData();
  }

  /**
   * @see Mailchimp_Campaigns::getList()
   */
  public function getList($filters=array(), $start=0, $limit=25, $sort_field='create_time', $sort_dir='DESC') {
    $campaigns = $this->campaign_data;

    $response = array(
      'total' => 0,
      'data' => array(),
    );

    foreach ($campaigns as $campaign) {
      foreach ($filters as $filter => $value) {
        if ($filter == 'campaign_id') {
          $filter = 'id';
        }
        if ($campaign[$filter] != $value) {
          continue;
        }
      }
      $response['data'][] = $campaign;
      $response['total']++;
    }

    return $response;
  }

  /**
   * Creates initial campaign values.
   *
   * @return array
   *   Basic campaigns.
   */
  protected function getInitialTestCampaignData() {
    $campaigns = array(
      self::TEST_CAMPAIGN_A => array(
        'id' => self::TEST_CAMPAIGN_A,
        'name' => 'Test Campaign A',
        'list_id' => self::TEST_LIST_A,
        'status' => 'sent',
        'type' => 'regular',
      ),
      self::TEST_CAMPAIGN_B => array(
        'id' => self::TEST_CAMPAIGN_B,
        'name' => 'Test Campaign B',
        'list_id' => self::TEST_LIST_B,
        'status' => 'save',
        'type' => 'plaintext',
      ),
      self::TEST_CAMPAIGN_C => array(
        'id' => self::TEST_CAMPAIGN_C,
        'name' => 'Test Campaign C',
        'list_id' => self::TEST_LIST_C,
        'status' => 'paused',
        'type' => 'absplit',
      ),
    );

    return $campaigns;
  }

  protected function saveTestCampaignData($campaigns) {
    $this->campaign_data = $campaigns;
  }

}
