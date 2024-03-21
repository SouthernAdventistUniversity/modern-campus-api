<?php

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

$calPendingData = pending_api_request();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode($calPendingData);