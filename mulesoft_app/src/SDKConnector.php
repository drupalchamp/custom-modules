<?php

/**
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by the
 * Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public
 * License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

namespace Drupal\mulesoft_app;

use Drupal\apiinfo\Entity\ApiinfoEntity;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\key\KeyInterface;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\Exception\RequestException;


/**
 * Provides an Apigee Edge SDK connector.
 */
class SDKConnector implements SDKConnectorInterface {

  /**
   * The client object.
   *
   * @var null|\Http\Client\HttpClient
   */
  private static $client = NULL;


  /**
   * Custom user agent prefix.
   *
   * @var null|string
   */
  private static $userAgentPrefix = NULL;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The info parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  private $clientFactory;

  /**
   * Constructs a new SDKConnector.
   *
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   *   Http client.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   Info file parser service.
   */
  public function __construct(ClientFactory $client_factory, KeyRepositoryInterface $key_repository, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, InfoParserInterface $info_parser) {
    $this->clientFactory = $client_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->keyRepository = $key_repository;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->infoParser = $info_parser;
  }

  /**
   * Get HTTP client overrides for Apigee Edge API client.
   *
   * Allows to override some configuration of the http client built by the
   * factory for the API client.
   *
   * @return array
   *   Associative array of configuration settings.
   *
   * @see http://docs.guzzlephp.org/en/stable/request-options.html
   */
  protected function httpClientConfiguration(): array {
    return [
      'connect_timeout' => $this->configFactory->get('mulesoft_app.client')
          ->get('http_client_connect_timeout') ?? 30,
      'timeout' => $this->configFactory->get('mulesoft_app.client')
          ->get('http_client_timeout') ?? 30,
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function getClient() {
    $credentials = new Credentials(
      $this->configFactory->get('mulesoft_app.auth')
        ->get('access_key_id'),
      $this->configFactory->get('mulesoft_app.auth')
        ->get('secret_access_key')
    );
    $apigatewayclient = new ApiGatewayClient([
      'version' => $this->configFactory->get('mulesoft_app.auth')
        ->get('version'),
      'version' => '2020-10-01',
      //      'region' => $this->configFactory->get('mulesoft_app.auth')->get('region'),
      'region' => 'us-west-2',
      'credentials' => $credentials,
      'http' => $this->httpClientConfiguration(),
    ]);
    return $apigatewayclient;
  }

  /**
   * {@inheritdoc}
   */
  private function getxsrftoken() {
    $xsrftokenvalitime = $this->configFactory->get('mulesoft_app.auth')
        ->get('xsrftokentokevalidtime') ?? 24;
    $timezone = date_default_timezone_get();
    $objDateTime = new \DateTime('now', new \DateTimeZone($timezone));
    //check if time is expired for access token
    $xsrftokentime = $this->configFactory->get('mulesoft_app.auth')
      ->get('xsrftokentime');
    $xsrftokenvalue = $this->configFactory->get('mulesoft_app.auth')
      ->get('xsrftokenvalue');
    if (!empty($xsrftokenvalue) && !empty($xsrftokentime)) {
      $time = \DateTime::createFromFormat('Y/m/d H:i:s', $xsrftokentime, new \DateTimeZone($timezone));
      $diff = $time->diff(new \DateTime('now', new \DateTimeZone($timezone)));
      $hours = ($diff->days * 24) + ($diff->h);
      // reduce by 1 hour for safely making request and return the stored value
      if ($hours < ($xsrftokenvalitime - 1)) {
        return $this->configFactory->get('mulesoft_app.auth')
          ->get('xsrftokenvalue');
      }
    }
    // generate new access token
    //$config_factory = \Drupal::configFactory();
    $config = $this->configFactory->getEditable('mulesoft_app.auth');
    $config->set('xsrftokentime', $objDateTime->format('Y/m/d H:i:s'));
    $config->save(TRUE);
    $url = 'https://' . $this->configFactory->get('mulesoft_app.auth')
        ->get('baseurl') . '/accounts/login';
    $curlObj = curl_init();
    $postRequest = [
      'username' => $this->configFactory->get('mulesoft_app.auth')
        ->get('username'),
      'password' => $this->decryptPassword($this->configFactory->get('mulesoft_app.auth')
        ->get('password')),
    ];
    curl_setopt($curlObj, CURLOPT_URL, $url);
    curl_setopt($curlObj, CURLOPT_POSTFIELDS, $postRequest);
    curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlObj, CURLOPT_HEADER, 1);
    curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($curlObj, CURLOPT_CONNECTTIMEOUT, $this->configFactory->get('mulesoft_app.client')
      ->get('http_client_connect_timeout'));
    curl_setopt($curlObj, CURLOPT_TIMEOUT, $this->configFactory->get('mulesoft_app.client')
      ->get('http_client_timeout')); //timeout in seconds
    $result = curl_exec($curlObj);
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi',
      $result, $match_found);
    $cookies = [];
    foreach ($match_found[1] as $item) {
      parse_str($item, $cookie);
      $cookies = array_merge($cookies, $cookie);
    }
    curl_close($curlObj);
    $config = $this->configFactory->getEditable('mulesoft_app.auth');
    $config->set('xsrftokenvalue', $cookies['XSRF-TOKEN']);
    $config->save(TRUE);
    \Drupal::logger('mulesoft_xsrf')->info($cookies['XSRF-TOKEN']);
    return $cookies['XSRF-TOKEN'];
  }


  /**
   * {@inheritdoc}
   */
  public function gethttpclient() {
    $client = \Drupal::httpClient('', [
      'request.options' => [
        'timeout' => $this->configFactory->get('mulesoft_app.client')
          ->get('request_timeout'),
        'connect_timeout' => $this->configFactory->get('mulesoft_app.client')
          ->get('connect_timeout'),
      ],
    ]);
    return $client;
  }

  /**
   * {@inheritdoc}
   */
  public function getaccesstoken($password = NULL) {
    $accesstokenvalitime = $this->configFactory->get('mulesoft_app.auth')
        ->get('accesstokevalidtime') ?? 30;
    $timezone = date_default_timezone_get();
    $objDateTime = new \DateTime('now', new \DateTimeZone($timezone));
    //check if time is expired for access token
    $accesstokentime = $this->configFactory->get('mulesoft_app.auth')
      ->get('accesstokentime');
    $accesstokenvalue = $this->configFactory->get('mulesoft_app.auth')
      ->get('accesstokenvalue');
    if (!$password && !empty($accesstokenvalue) && !empty($accesstokentime)) {
      $time = \DateTime::createFromFormat('Y/m/d H:i:s', $accesstokentime, new \DateTimeZone($timezone));
      $diff = $time->diff(new \DateTime('now', new \DateTimeZone($timezone)));
      $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
      // reduce by 3 minutes for safely making request and return the stored value
      if ($minutes < ($accesstokenvalitime - 3)) {
        return $this->configFactory->get('mulesoft_app.auth')
          ->get('accesstokenvalue');
      }
    }
    // generate new access token
    $config = $this->configFactory->getEditable('mulesoft_app.auth');
    $config->set('accesstokentime', $objDateTime->format('Y/m/d H:i:s'));
    $config->save(TRUE);

    $xsrftoken = $this->getxsrftoken();
    if (!$password) {
      $password = $this->decryptPassword($this->configFactory->get('mulesoft_app.auth')
        ->get('password'));
    }
    $serialized_body = json_encode([
      'username' => $this->configFactory->get('mulesoft_app.auth')
        ->get('username'),
      'password' => $password,
    ]);
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => 'https://anypoint.mulesoft.com/accounts/login',
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $serialized_body,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'XSRF-TOKEN: ' . $xsrftoken,
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    $response_body = JSON::decode($response);
    if (isset($response_body['access_token'])) {
      $config = $this->configFactory->getEditable('mulesoft_app.auth');
      $config->set('accesstokenvalue', $response_body->access_token);
      $config->save(TRUE);
      return $response_body['access_token'];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function testConnection($orgid = NULL, $envid = NULL, $password = NULL) {
    $baseurl = $this->configFactory->get('mulesoft_app.auth')
      ->get('baseurl');
    if (!$orgid) {
      $orgid = $this->configFactory->get('mulesoft_app.auth')->get('orgid');
    }
    if (!$envid) {
      $envid = $this->configFactory->get('mulesoft_app.auth')->get('envid');
    }
    $accesstoken = $this->getaccesstoken($password);
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => 'https://' . $baseurl . '/apimanager/api/v1/organizations/' . $orgid . '/environments/' . $envid . '/apis',
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accesstoken,
      ],
    ]);
    $response = curl_exec($curl);
    $response_body = Json::decode($response);
    if (isset($response_body['message']) && isset($response_body['name'])) {
      return $response_body['message'];
    }
    return $response_body;
  }

  /**
   * @param $appID
   *
   * @return mixed
   * Implements to get APP details by ID.
   */
  public function getAppDetails($appID) {
    $mulesoft_config = \Drupal::config('mulesoft_app.auth');
    $client = $this->gethttpclient();
    $baseurl = $mulesoft_config->get('baseurl');
    $org_id = $mulesoft_config->get('orgid');
    $message = '';
    try {
      $access_token = $this->getaccesstoken();
      $response = $client->get('https://' . $baseurl . '/apiplatform/repository/v2/organizations/' . $org_id . '/applications/' . $appID, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);
      if ($response->getBody()) {
        $response_body = JSON::decode($response->getBody());
        return $response_body;
      }
    } catch (RequestException $e) {
      if ($e->hasResponse()) {
        $exception = (string) $e->getResponse()->getBody();
        $exception_arr = JSON::decode($exception);
        if (isset($exception_arr['message'])) {
          $message = $exception_arr['message'];
        }
        elseif (isset($exception_arr['errors'])) {
          $message = $exception_arr['errors'][0]['message'];
        }
      }
    }
    $response_body = [
      'error' => $message,
    ];
    return $response_body;
  }

  /**
   * @param $appID
   *
   * @return mixed
   * Implements to get reset client secret by ID.
   */
  public function resetClientSecret($appId, $mulesoft_id) {
    $mulesoft_config = \Drupal::config('mulesoft_app.auth');
    $client = $this->gethttpclient();
    $baseurl = $mulesoft_config->get('baseurl');
    $org_id = $mulesoft_config->get('orgid');
    $message = '';
    try {
      $access_token = $this->getaccesstoken();
      $response = $client->post('https://' . $baseurl . '/apiplatform/repository/v2/organizations/' . $org_id . '/applications/' . $mulesoft_id . '/secret/reset', [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);
      $code = $response->getStatusCode();
      if ($code == 201) {
        $appDetails = $this->getAppDetails($mulesoft_id);
        $clientSecret = $appDetails['clientSecret'];
        $app_entity = ApiinfoEntity::load($appId);
        $app_entity->set("field_client_secret", $clientSecret);
        $app_entity->save();
        return [
          'code' => $code,
          'secret' => $clientSecret,
          'response' => TRUE,
        ];
      }
    } catch (RequestException $e) {
      if ($e->hasResponse()) {
        $exception = (string) $e->getResponse()->getBody();
        $exception_arr = JSON::decode($exception);
        if (isset($exception_arr['message'])) {
          $message = $exception_arr['message'];
        }
        elseif (isset($exception_arr['errors'])) {
          $message = $exception_arr['errors'][0]['message'];
        }
      }
      \Drupal::messenger()->addMessage($message, 'error');
    }
    return [
      'code' => '500',
      'response' => FALSE,
    ];
  }


  /**
   * @param $access_token
   *
   * @return mixed
   */
  public function getMulesoftAPIs($access_token) {
    try {
      $mulesoft_connector = \Drupal::service('mulesoft_app.sdk_connector');
      $client = $mulesoft_connector->gethttpclient();
      $baseurl = $this->configFactory->get('mulesoft_app.auth')->get('baseurl');
      $orgid = $this->configFactory->get('mulesoft_app.auth')->get('orgid');
      $envid = $this->configFactory->get('mulesoft_app.auth')->get('envid');
      $response = $client->get('https://' . $baseurl . '/apimanager/api/v1/organizations/' . $orgid . '/environments/' . $envid . '/apis', [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);
      $code = $response->getStatusCode();
      if ($code == 200) {
        if ($response->getBody()) {
          $response_body = JSON::decode($response->getBody());
          if (isset($response_body['assets'])) {
            return $response_body['assets'];
          }
        }
      }
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  /**
   * Implements to get MuleSoft API Details from list of APIs.
   */
  public function getMulesoftAPIDetails() {
    // Get the access token for login
    $access_token = $this->getaccesstoken();
    $list_apis = $this->getMulesoftAPIs($access_token);
    $apis_data = [];
    if ($list_apis) {
      foreach ($list_apis as $api) {
        if (isset($api['apis'])) {
          foreach ($api['apis'] as $api_data) {
            if ($api_data['id']) {
              $apis_data[$api_data['id']] = [
                'id' => $api_data['id'],
                'label' => $api_data['instanceLabel'] ? $api_data['instanceLabel'] : $api_data['assetId'] . " - " . $api_data['id'],
                'assetId' => $api['id'],
              ];
            }
          }
        }
      }
    }
    return $apis_data;
  }

  /**
   * @param null $string
   *
   * @return false|string
   * Implements to decrypt password.
   */
  private function decryptPassword($encryption = NULL) {
    $ciphering = "AES-256-CTR";
    $options = 0;
    $decryption_iv = '1234567891011121';
    $decryption_key = "HSBCDEVPORTAL";
    $decryption = openssl_decrypt($encryption, $ciphering, $decryption_key, $options, $decryption_iv);
    return $decryption;
  }

}
