<?php

$calUser = 'api-calendar';
$calPass = '1892api423Calendar!';

// Get User Token
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

// Fetch from API
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

function getFromPublicAPI($endpoint)
{
    // Append endpoint to base URL
    $url = 'https://api.calendar.moderncampus.net/' . $endpoint;

    // Create an $options array that contains the HTTP header "Accept: application/json" and the X-Auth-Token
    // Double quotes neccesary for Accept but fails if used for X-Auth-Token
    $options = [
        'http' => [
            'method' => 'GET',
            'header' =>
                "Accept: application/json\r\n"
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

// $userToken = getUserToken($calUser, $calPass);

// $calPending = 'calendars/www/reports/pending-approvals?category=&category=General&timezone=US%2FEastern';

// $calPendingData = getFromAPI($calPending, $userToken);

$singleEvent = '/pubcalendar/6b7cb250-b6eb-4060-a2bf-c17c97707aa5/events/';

// Get ID from URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $singleEvent = $singleEvent . $id;
}

$calPendingData = getFromPublicAPI($singleEvent);

echo '<pre>' . $calPendingData['title'] . '</pre>';