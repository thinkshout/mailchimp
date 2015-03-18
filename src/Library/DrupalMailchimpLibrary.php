<?php

/**
 * @file
 * Wrapper class around the Mailchimp API.
 */

namespace Drupal\mailchimp\Library;

use Drupal\Component\Serialization\Json;

/**
 * Class DrupalMailchimpLibrary
 *
 * Extend the MailChimp class to add some Drupalisms.
 */
class DrupalMailchimpLibrary extends Mailchimp {

  protected $timeout;
  protected $userAgent = 'MailChimp-PHP/2.0.4';

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
      throw new Mailchimp_Error(t('You must provide a MailChimp API key'));
    }
    $this->apikey = $apikey;
    $dc = "us1";
    if (strstr($this->apikey, "-")) {
      $key_parts = explode("-", $this->apikey, 2);
      $dc = (isset($key_parts[1])) ? $key_parts[1] : 'us1';
    }
    $this->root = str_replace('https://api', 'https://' . $dc . '.api', $this->root);
    $this->root = rtrim($this->root, '/') . '/';

    $this->timeout = (isset($opts['timeout']) && is_int($opts['timeout'])) ? $opts['timeout'] : 600;

    $this->folders = new Mailchimp_Folders($this);
    $this->templates = new Mailchimp_Templates($this);
    $this->users = new Mailchimp_Users($this);
    $this->helper = new Mailchimp_Helper($this);
    $this->mobile = new Mailchimp_Mobile($this);
    $this->ecomm = new Mailchimp_Ecomm($this);
    $this->neapolitan = new Mailchimp_Neapolitan($this);
    $this->lists = new Mailchimp_Lists($this);
    $this->campaigns = new Mailchimp_Campaigns($this);
    $this->vip = new Mailchimp_Vip($this);
    $this->reports = new Mailchimp_Reports($this);
    $this->gallery = new Mailchimp_Gallery($this);

  }

  /**
   * Override the parent to eliminate the call to curl_close().
   */
  public function __destruct() {}

  /**
   * Override MCAPI::call() to leverage Drupal's core HTTP handling.
   */
  public function call($url, $params) {
    // @todo this is totally untested
    $params['apikey'] = $this->apikey;
    $params = Json::decode($params);
    $post_options = array(
      'body' => $params,
      'headers' => array(
        'Content-type' => 'application/json',
        'Accept-Language' => language_default()->language,
        'User-Agent' => $this->userAgent,
      ),
      'timeout' => $this->timeout,
    );
    try {
      $response = Drupal::httpClient()->post($this->root . $url . '.json', $post_options);
      // Expected result.
      $data = $response->getBody(TRUE);
    }
    catch (Exception $e) {
      throw new Mailchimp_HttpError(t("MailChimp API call to %url failed: @msg", array('%url' => $url, '@msg' => $response->error)));
    }

    $result = Json::decode($data);

    if (floor($response->code / 100) >= 4) {
      throw $this->castError($result);
    }

    return $result;
  }

}
