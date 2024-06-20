<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

require_once('pendingEvents.class.php');
// DEV / Local - uncomment line below for testing locally, comment out prod require
// require_once('../omni_api.config.local');
// PROD - uncomment before push to remote
require_once('/var/www/www.southern.edu/configs/omni_api.config');

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

  $request_uri = $_SERVER['REQUEST_URI'];
  $endpoint = null;
  $query = null;

  // Get endpoint and query
  // Capture group #1 finds endpoints up to '?'
  // Capture group #2 finds any characters after '?'
  if (preg_match('/^.*calendar-pending(.*?)(\?.*)?$/', $request_uri, $matches)) {
    switch (count($matches)) {
      case 2:
        $endpoint = $matches[1];
        break;
      case 3:
        $endpoint = $matches[1];
        $query = $matches[2];
        break;
      default:
        http_response_code(400);
        return json_encode(array('message' => 'No Paramater Passed'));
    }
  }

  // All Events
  if ($endpoint === '/events') {
    // IF: query string passed
    // RETURN: date range
    if ($query && str_contains($query, 'start')) {
      // Get Events in Range
      $events = $pendingEvents->getEventsByDate($query);
      // Return Events
      return json_encode($events);
    }
    // ELSE
    // RETURN: All Events
    else {
      // Get Purge param from URL
      if ($query && str_contains($query, 'purge')) {
        $purge = true;
      } else {
        $purge = false;
      }
      // Get All Events
      $events = $pendingEvents->getAllEvents($purge);
      // Return events
      return json_encode($events);
    }
  }
  // Event By ID
  elseif (preg_match('/^\/events\/(.+)$/', $endpoint, $matches)) {
    $eventId = $matches[1];
    // Get event by ID
    $event = $pendingEvents->getEventById($eventId);
    // Return Event
    return json_encode($event);
  } else {
    http_response_code(400);
    return json_encode(array('message' => 'No Endpoint Matched'));
  }
}

echo endpoint_handler();
