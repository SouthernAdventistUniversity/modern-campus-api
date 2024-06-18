<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('pendingEvents.class.php');
// DEV / Local - uncomment line below for testing locally, comment out prod require
require_once('../omni_api.config.local');
// PROD - uncomment before push to remote
// require_once('/var/www/www.southern.edu/configs/omni_api.config');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

/**
 * Handle Endpoints
 *
 * Init function that checks endpoints and delegates response to Pending Events Class
 * Endpoints:
 * - All Events: /events
 * - Date Range: /events?start={date}&end={date} - Date in YYYY-MM-DD format
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
    // IF: query string passed
    // RETURN: date range
    if (isset($_SERVER['QUERY_STRING'])) {
      $query = $_SERVER['QUERY_STRING'];
      // $date_regex = '(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[1,2])-(19|20)\d{2}/';
      // $start = '';
      // $end = '';
      //
      // if (preg_match('/start=' . $date_regex, $query, $matches)) {
      //   $start = $matches[1];
      // }
      // if (preg_match('/end=' . $date_regex, $query, $matches)) {
      //   $end = $matches[1];
      // }
      //
      // // Check if both start and end params passed
      // if ($start && $end) {
      

      // Get Events in Range
      $events = $pendingEvents->getEventsByDate($query);
      // Return Events
      echo json_encode($events);


      // } else {
      //   echo json_encode('Invalid Start and End Ranges Passed');
      // }
    }
    // ELSE
    // RETURN: All Events
    else {
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
  }
  // Event By ID
  elseif (preg_match('/^\/events\/(.+)$/', $endpoint, $matches)) {
    $eventId = $matches[1];
    // Get event by ID
    $event = $pendingEvents->getEventById($eventId);
    // Return Event
    echo json_encode($event);
  } else {
    http_response_code(404);
    echo json_encode(array('message' => 'No Endpoint Matched'));
  }
}

endpoint_handler();
