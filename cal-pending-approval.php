<?php

/**
 * API Request Caching
 *
 *  Use server-side caching to store API request's as JSON at a set 
 *  interval, rather than each pageload.
 *  Pulled and modified from: https://www.kevinleary.net/blog/api-request-caching-json-php/
 * 
 * @arg Argument description and usage info
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

    if (!file_exists($cache_file))
        die("Cache file is missing: $cache_file");

    // Check that the file is older than the expire time and that it's not empty
    if (filectime($cache_file) < $expires || file_get_contents($cache_file) == '' || $purge_cache) {

        // File is too old, refresh cache
        $api_results = pending_api_request();
        $json_results = json_encode($api_results);

        // Remove cache file on error to avoid writing wrong xml
        if ($api_results && $json_results) {
            file_put_contents($cache_file, $json_results);
        } else {
            unlink($cache_file);
        }
    } else {
        $json_results = file_get_contents($cache_file);
    }

    return json_decode($json_results);
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