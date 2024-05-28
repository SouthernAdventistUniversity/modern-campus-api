<?php

/**
 * Get API Response and write to Cache File
 *
 * @param string $cache_file
 * @return null
 */
function response_to_cache($cache_file)
{
  $api_results = pending_api_request();
  $json_results = json_encode($api_results);

  if ($api_results && $json_results) {
    // Will create file if it doesn't exist
    file_put_contents($cache_file, $json_results);
  } else {
    // Remove cache file on error to avoid writing wrong xml
    unlink($cache_file);
  }
}

/**
 * API Request Caching
 *
 *  Use server-side caching to store API request's as JSON at a set 
 *  interval, rather than each pageload.
 *  Pulled and modified from: https://www.kevinleary.net/blog/api-request-caching-json-php/
 * 
 * @param string $cache_file
 * @param int $expires
 * @return object
 */
function json_cached_api_results($cache_file = NULL, $expires = NULL, $purge_cache = false)
{
  if (!$cache_file)
    $cache_file = dirname(__FILE__) . '/pending-cache.json';
  if (!$expires)
    $expires = time() - 30 * 60;

  // If file doesn't exist: get response and create cache
  if (!file_exists($cache_file)) {
    response_to_cache($cache_file);
  }
  // Else if: file is older than expire time OR file is empty OR purge_cache is true
  elseif (filectime($cache_file) < $expires || file_get_contents($cache_file) == '' || $purge_cache) {
    response_to_cache($cache_file);
  }

  // Return decoded cache file contents
  return json_decode(file_get_contents($cache_file));
}

/**
 * Log in to the OmniUpdate API and get a user token
 * 
 * @param string $calUser
 * @param string $calPass
 * @return string|bool
 */
function getUserToken($calUser, $calPass)
{
  $body = http_build_query([
    'skin' => 'oucampus',
    'account' => 'southern',
    'username' => $calUser,
    'password' => $calPass
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
 * Fetches data from the OmniUpdate API using the provided endpoint and user token
 * 
 * @param string $endpoint
 * @param string $userToken
 * @return array|bool
 */
function getFromAPI($endpoint, $userToken)
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
        'X-Auth-Token: ' . $userToken
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
 * Get pending approval events from OmniUpdate API
 * 
 * 
 */
function pending_api_request()
{
  $calUser = 'api-calendar';
  $calPass = '1892api423Calendar!';

  $userToken = getUserToken($calUser, $calPass);

  // echo 'User Token: ' . $userToken . '<br>';

  $calPendingURL = 'calendars/www/reports/pending-approvals?category=General&timezone=US%2FEastern';

  $response = getFromAPI($calPendingURL, $userToken);

  return $response;
}

// Get Purge param from URL - urlencode()
if (isset($_GET['purge'])) {
  $purge = $_GET['purge'];
} else {
  $purge = false;
}

$calPendingData = json_cached_api_results(NULL, NULL, $purge);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode($calPendingData);
