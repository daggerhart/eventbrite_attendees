<?php

namespace Drupal\eventbrite_attendees\Eventbrite;

use Drupal\Component\Serialization\Json;

class Api {

  /**
   * Build a new array of attendees for an event by querying Eventbrite API
   *
   * @param $event_id
   * @param array $query
   * @return array
   */
  public static function getEventAttendees($event_id, $query = [ 'page' => 1, 'status' => 'attending' ])
  {
    $response = self::query("events/{$event_id}/attendees", $query);

    if (!$response){
      return [];
    }

    $data = Json::decode($response->getBody());

    $attendees = !empty($data['attendees']) ? $data['attendees'] : [];

    // recurse for pagination
    if ($data['pagination']['page_number'] < $data['pagination']['page_count']){
      $attendees = array_merge($attendees, self::getEventAttendees($event_id, [
        'page' => $data['pagination']['page_number'] + 1,
		'status' => 'attending',
      ]));
    }

    return $attendees;
  }

  /**
   * Single request to the Eventbrite API
   *
   * @param $endpoint
   * @param array $query
   * @return mixed
   */
  public static function query($endpoint, $query = [])
  {
    $api_url = "https://www.eventbriteapi.com/v3/{$endpoint}";

    $default_query = [
      'token' => \Drupal::config('eventbrite_attendees.settings')->get('oauth_token')
    ];

    $response = \Drupal::httpClient()->request('GET', $api_url, [
      'query' => array_replace( $default_query, $query ),
      'http_errors' => FALSE,
    ]);

    if ($response->getStatusCode() == '200'){
      return $response;
    }

    return FALSE;
  }

  /**
   * Determine if the provided oauth token is legitimate
   *
   * @param $token
   * @return bool
   */
  public static function testOauthToken($token)
  {
    $response = self::query('users/me', ['token' => $token ]);

    return $response ? TRUE : FALSE;
  }
}