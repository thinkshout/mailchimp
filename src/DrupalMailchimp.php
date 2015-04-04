<?php

/**
 * @file
 * Wrapper class around the Mailchimp API.
 */

namespace Drupal\mailchimp;

use Drupal\Component\Serialization\Json;
use Mailchimp;


/**
 * Class DrupalMailchimp
 *
 * Extend the MailChimp class to add some Drupalisms.
 */
class DrupalMailchimp extends Mailchimp {

  protected $timeout;
  protected $userAgent = 'MailChimp-PHP/2.0.6';

  /**
   * Override __construct().
   *
   * The parent constructor sets curl settings that we need to avoid. Much of
   * code is duplicated from the parent.
   */
  public function __construct($apikey = NULL, $opts = array()) {
    if (!$apikey) {
      $apikey = getenv('MAILCHIMP_APIKEY');
    }
    if (!$apikey) {
      $apikey = $this->readConfigs();
    }
    if (!$apikey) {
      throw new \Mailchimp_Error(t('You must provide a MailChimp API key'));
    }
    $this->apikey = $apikey;
    $dc = "us1";
    if (strstr($this->apikey, "-")) {
      $key_parts = explode("-", $this->apikey, 2);
      $dc = (isset($key_parts[1])) ? $key_parts[1] : 'us1';
    }
    $this->root = str_replace('https://api', 'https://' . $dc . '.api', $this->root);
    $this->root = rtrim($this->root, '/') . '/';

    // Set the timeout to something that won't take down the Drupal site. (600
    // is the default in parent class)
    $this->timeout = (isset($opts['timeout']) && is_int($opts['timeout'])) ? $opts['timeout'] : 60;

    $this->debug = isset($opts['debug']) ? TRUE : FALSE;

    $this->folders = new \Mailchimp_Folders($this);
    $this->templates = new \Mailchimp_Templates($this);
    $this->users = new \Mailchimp_Users($this);
    $this->helper = new \Mailchimp_Helper($this);
    $this->mobile = new \Mailchimp_Mobile($this);
    $this->ecomm = new \Mailchimp_Ecomm($this);
    $this->neapolitan = new \Mailchimp_Neapolitan($this);
    $this->lists = new \Mailchimp_Lists($this);
    $this->campaigns = new \Mailchimp_Campaigns($this);
    $this->vip = new \Mailchimp_Vip($this);
    $this->reports = new \Mailchimp_Reports($this);
    $this->gallery = new \Mailchimp_Gallery($this);

    // Temporary code until call() is re-written. Allows parent::call() to 
    // function.
    $this->ch = curl_init();

    if (isset($opts['CURLOPT_FOLLOWLOCATION']) && $opts['CURLOPT_FOLLOWLOCATION'] === true) {
      curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
    }

    curl_setopt($this->ch, CURLOPT_USERAGENT, 'MailChimp-PHP/2.0.6');
    curl_setopt($this->ch, CURLOPT_POST, true);
    curl_setopt($this->ch, CURLOPT_HEADER, false);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
  }

  /**
   * Override the parent to eliminate the call to curl_close().
   */
  public function __destruct() {}

  /**
   * Override MCAPI::call() to leverage Drupal's core HTTP handling.
   */
//  public function call($url, $params) {
//    // @todo this is totally untested
//    $params['apikey'] = $this->apikey;
//    $params = Json::decode($params);
//    $post_options = array(
//      'body' => $params,
//      'headers' => array(
//        'Content-type' => 'application/json',
//        'Accept-Language' => language_default()->language,
//        'User-Agent' => $this->userAgent,
//      ),
//      'timeout' => $this->timeout,
//    );
//    try {
//      $response = \Drupal::httpClient()->post($this->root . $url . '.json', $post_options);
//      // Expected result.
//      $data = $response->getBody(TRUE);
//    }
//    catch (Exception $e) {
//      throw new Mailchimp_HttpError(t("MailChimp API call to %url failed: @msg", array('%url' => $url, '@msg' => $response->error)));
//    }
//
//    $result = Json::decode($data);
//
//    if (floor($response->code / 100) >= 4) {
//      throw $this->castError($result);
//    }
//
//    return $result;
//  }

}
