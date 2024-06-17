<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// DEV / Local - uncomment line below for testing locally, comment out prod require
require_once('../omni_api.config.local');
// PROD - uncomment after pull
// require_once('/var/www/www.southern.edu/configs/omni_api.config');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


/**
 * Pending Events Class
 */
class PendingEvents
{
  private string $token;

  public function __construct()
  {
    $this->token = $this->getUserToken();
  }

  /**
   * Log in to the OmniUpdate API and get a user token
   *
   * @return string|bool
   */
  private function getUserToken()
  {
    $body = http_build_query([
      'skin' => 'oucampus',
      'account' => ACCOUNT,
      'username' => CAL_USERNAME,
      'password' => CAL_PASSWORD
    ]);

    $options = [
      'http' => [
        'method' => 'POST',
        'header' =>
        "Content-Type: application/x-www-form-urlencoded\n",
        'content' => $body
      ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents('https://a.cms.omniupdate.com/authentication/login', false, $context);

    if ($response === false) {
      // Handle error
      echo 'Response is false<br>';
      return false;
    } else {
      $decoded = json_decode($response, true);
      return $decoded['gadget_token'];
    }
  }

  /**
   * Fetches data from the OmniUpdate API using the provided endpoint.
   *
   * @param string $endpoint
   * @return array|bool JSON Decoded
   */
  private function getFromAPI($endpoint)
  {
    // Append endpoint to base URL
    $url = 'https://a.cms.omniupdate.com/rs/' . $endpoint;

    // Create an $options array that contains the HTTP header "Accept: application/json" and the X-Auth-Token
    // Double quotes neccesary for Accept but fails if used for X-Auth-Token
    $options = [
      'http' => [
        'method' => 'GET',
        'header' =>
        "Accept: application/json\r\n" .
          'X-Auth-Token: ' . $this->token
      ]
    ];
    // Create a stream context using the $options array
    $context = stream_context_create($options);
    // Make the request, returning the response content
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
      // Handle error
      echo '<br>Response is false<br>';
      return false;
    } else {
      $data = json_decode($response, true);

      if ($data === null) {
        // Handle JSON parsing error
        echo '<br>Data is null<br>';
        return false;
      } else {
        // Process the data
        return $data;
      }
    }
  }

  /**
   * Write string (API response) to Cache File
   * @param string $response
   * @param string $cache_file
   * @return null
   */
  private function writeToCache($response, $cache_file)
  {
    $json_results = json_encode($response);

    if ($response && $json_results) {
      // Will create file if it doesn't exist
      file_put_contents($cache_file, $json_results);
    } else {
      // Remove cache file on error to avoid writing wrong xml
      unlink($cache_file);
    }
  }

  /**
   * Fetch all pending events and write them to cache.
   * Uses a JSON file cache to store response. Modified from: https://www.kevinleary.net/blog/api-request-caching-json-php/
   * - Checks if data is not cached, empty, or too old
   * - If true: fetch, write to cache, and return data
   * - Else: return data
   * @return array
   */
  public function getAllEvents($purge_cache = false)
  {
    $endpoint = 'calendars/www/reports/pending-approvals?category=General&timezone=US%2FEastern';
    $cache_file = '../cache/pending-cache.json';
    // Expires if last cache older than 30 minutes
    $expired = filectime($cache_file) < time() - 30 * 60;

    // If: file is older than expire time OR file is empty OR purge_cache is true: fetch, cache, and return response
    if (!file_exists($cache_file) || file_get_contents($cache_file) == '' || $expired || $purge_cache) {
      echo 'fetching';
      // Fetch response
      $response = $this->getFromAPI($endpoint);
      // Write to cache
      $this->writeToCache($response, $cache_file);
      // Return response
      return $response;
    }
    // Else: return decoded cache file content
    else {
      return json_decode(file_get_contents($cache_file));
    }
  }
  /**
   * Fetch pending event by ID.
   * @param string $eventId
   * @return object 
   */
  public function getEventById($eventId)
  {
    $endpoint = 'calendars/www/events/' . $eventId;
    // Fetch response
    $response = $this->getFromAPI($endpoint);
    // Return response
    return $response;
  }
}


/**
 * Handle Endpoints
 *
 * Init function that checks endpoints and delegates response to Pending Events Class
 * Endpoints:
 * - All Events: /events
 * - Date Range: /events?start={date}&end={date}
 * - Single Event: /events/{event-id}
 *
 * @return string
 */
function endpoint_handler()
{
  // Create Pending Events Instance
  $pendingEvents = new PendingEvents();
  // Get endpoints
  $endpoint = $_SERVER['PATH_INFO'];

  // All Events
  if ($endpoint === '/events') {
    // Get Purge param from URL
    if (isset($_GET['purge'])) {
      $purge = true;
    } else {
      $purge = false;
    }
    // Get All Events
    $events = $pendingEvents->getAllEvents($purge);
    // Return events
    echo json_encode($events);
  }
  // Event By ID
  elseif (preg_match('/^\/events\/(.+)$/', $endpoint, $matches)) {
    $eventId = $matches[1];
    // Get event by ID
    $event = $pendingEvents->getEventById($eventId);
    // Return Event
    echo json_encode($event);
  }
  // Events Withing Date Range
  // elseif (preg_match('/^\/events$/', $endpoint, $matches)) {
  // }
}

endpoint_handler();
