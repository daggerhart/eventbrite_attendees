<?php

namespace Drupal\eventbrite_attendees\Eventbrite;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\TransferStats;

/**
 * Class ApiClient
 *
 * @package Drupal\eventbrite_attendees\Eventbrite
 */
class ApiClient {

  /**
   * Base URI for the Eventbrite API.
   */
  const BASE_URI = 'https://www.eventbriteapi.com';

  /**
   * Version of the Eventbrite API used.
   */
  const VERSION = 'v3';

  /**
   * Instance of an http client.
   *
   * @var \GuzzleHttp\Client
   */
  public $client;

  /**
   * Eventbrite Personal OAuth token.
   *
   * @var string
   */
  private $token;

  /**
   * Array of requested URI strings.
   *
   * @var array
   */
  public $effectiveUris = [];

  /**
   * Config object for this module's settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  public $settings;

  /**
   * ApiClient constructor.
   *
   * @param $http_client_factory \Drupal\Core\Http\ClientFactory
   *   Http requests client factory.
   * @param $config_factory \Drupal\Core\Config\ConfigFactoryInterface
   *   Configuration instances for this module's settings.
   */
  public function __construct($http_client_factory, $config_factory) {
    $this->settings = $config_factory->get('eventbrite_attendees.settings');
    $this->setToken($this->settings->get('oauth_token'));

    $this->client = $http_client_factory->fromOptions([
      'base_uri' => self::BASE_URI . '/' . self::VERSION . '/',
    ]);
  }

  /**
   * Standardized request options to include some required values.
   *
   * @param array $query
   *   Associative key value pairs that become the query parameters.
   *
   * @return array
   *   Http Request options array.
   */
  protected function requestOptions(array $query) {
    $this->effectiveUris = [];
    $default_query = [
      'token' => $this->token,
    ];

    return [
      'http_errors' => FALSE,
      'on_stats' => [$this, 'onStats'],
      'query' => array_replace($default_query, $query),
    ];
  }

  /**
   * Set a new Eventbrite OAuth token.
   *
   * @param $token
   */
  public function setToken($token) {
    $this->token = $token;
  }

  /**
   * @param \GuzzleHttp\TransferStats $stats
   */
  public function onStats(TransferStats $stats) {
    // hide the token
    $uri = str_replace($this->token, '[secret]', $stats->getEffectiveUri());
    $this->effectiveUris[] = $uri;
  }

  /**
   * Method for simple single page results.
   *
   * @param $endpoint
   * @param array $options
   *
   * @return array|mixed
   */
  public function getData($endpoint, $options = []) {
    $endpoint = trim($endpoint, '/') . '/';
    $response = $this->client->get($endpoint, $options);
    if (!$response || $response->getStatusCode() != '200') {
      return [];
    }

    return Json::decode($response->getBody());
  }

  /**
   * Method for results that come in pages according to the API.
   *
   * @param string $endpoint
   *   API endpoint Uri.
   * @param array $options
   *   Http Request options array.
   * @param int $page
   *   Page number to request.
   *
   * @return array
   *   Array of results from each page request.
   */
  public function getPaginatedData($endpoint, $options = [], $page = 1) {
    $pages = [];
    $endpoint = trim($endpoint, '/') . '/';
    $options['query']['page'] = $page;

    $response = $this->client->get($endpoint, $options);
    if (!$response || $response->getStatusCode() != '200') {
      return [];
    }

    $data = Json::decode($response->getBody());
    $pages[$page] = $data;

    if ($data['pagination']['page_number'] < $data['pagination']['page_count']) {
      $pages += $this->getPaginatedData($endpoint, $options, $page + 1);
    }

    return $pages;
  }

  /**
   * Get an array of all attendees to an event id.
   * https://www.eventbrite.com/developer/v3/endpoints/events/#ebapi-get-events-id-attendees
   *
   * @param string $event_id
   *   Eventbrite event id.
   *
   * @return array
   *   Array of attendees data.
   */
  public function getEventAttendees($event_id) {
    $pages = $this->getPaginatedData("events/{$event_id}/attendees", $this->requestOptions([
      'status' => 'attending'
    ]));

    return array_merge(...array_column($pages, 'attendees'));
  }

  /**
   * Get an array of data concerning the current authenticated API user.
   * https://www.eventbrite.com/developer/v3/endpoints/users/#ebapi-get-users-id
   *
   * @return array
   */
  public function getUserMe() {
    return $this->getData("users/me", $this->requestOptions([]));
  }

}
