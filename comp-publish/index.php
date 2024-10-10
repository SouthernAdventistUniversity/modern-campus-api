<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

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
 * Pending Events Class
 */
class PublishDependencies
{
  private string $token;

  public function __construct()
  {
    $this->token = $this->getUserToken();
    echo $this->publishDependencies();
    // $url = 'https://a.cms.omniupdate.com/rs/workers/3320068';
    //
    // $options = [
    //   'http' => [
    //     'method' => 'GET',
    //     'header' => [
    //       'X-Auth-Token: ' . $this->token,
    //     ]
    //   ]
    // ];
    //
    // // Create a stream context using the $options array
    // $context = stream_context_create($options);
    // // Make the request, returning the response content
    // $response = file_get_contents($url, false, $context);
    //
    // $decoded = json_decode($response, true);
    // var_dump($decoded);
    // var_dump($decoded['detail']['failed'] == true);
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
      'username' => PUB_USERNAME,
      'password' => PUB_PASSWORD
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
      echo json_encode(array('message' => 'Response is false'));
      return false;
    } else {
      $decoded = json_decode($response, true);
      return $decoded['gadget_token'];
    }
  }

  /**
   * Fetch component dependencies from the MC API
   *
   * @param string $comp
   * @return array|bool JSON Decoded
   */
  private function getDependencies($comp)
  {
    $url = 'https://a.cms.omniupdate.com/rs/components/dependents/generic/' . $comp;

    // Create an $options array that contains the HTTP header "Accept: application/json" and the X-Auth-Token
    $options = [
      'http' => [
        'method' => 'GET',
        'header' => [
          'Content-Type: application/x-www-form-urlencoded',
          'X-Auth-Token: ' . $this->token,
        ],
      ]
    ];
    // Create a stream context using the $options array
    $context = stream_context_create($options);
    // Make the request, returning the response content
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
      // Handle error
      echo json_encode(array('message' => 'Dependencies response is false'));
      return false;
    } else {
      $data = json_decode($response, true);

      if ($data === null) {
        // Handle JSON parsing error
        echo json_encode(array('message' => 'Dependencies data is null'));
        return false;
      } else {
        // Process the data
        return $data;
      }
    }
  }

  /**
   * Publish file for a given site and path
   *
   * @param string $site
   * @param string $path
   * @param string $comp
   * @returns string $key
   */
  private function publishFile($site, $path, $comp)
  {
    $url = 'https://a.cms.omniupdate.com/files/publish';

    $body = http_build_query([
      'site' => $site,
      'path' => $path,
      'log' => '[Script] Dependency Publish: ' . urldecode($comp),
      'include_scheduled_publish' => false,
      'include_pending_approval' => false,
      'include_checked_out' => false,
    ]);

    // Create an $options array that contains the HTTP header "Accept: application/json" and the X-Auth-Token
    $options = [
      'http' => [
        'method' => 'POST',
        'header' => [
          'Content-Type: application/x-www-form-urlencoded',
          'X-Auth-Token: ' . $this->token,
        ],
        'content' => $body
      ]
    ];

    // Create a stream context using the $options array
    $context = stream_context_create($options);
    // Make the request, returning the response content
    $response = json_decode(file_get_contents($url, false, $context), true);

    if ($response === false) {
      // Handle error
      echo json_encode(array('message' => 'Invalid Publish Request: ' . $site . ' ' . $path));
      return false;
    } else {
      return $response['key'];
    }
  }

  private function getWorker($key)
  {
    $url = 'https://a.cms.omniupdate.com/rs/workers';
    $worker = null;

    $options = [
      'http' => [
        'method' => 'GET',
        'header' => [
          'X-Auth-Token: ' . $this->token,
        ]
      ]
    ];

    // Create a stream context using the $options array
    $context = stream_context_create($options);
    // Make the request, returning the response content
    $response = file_get_contents($url, false, $context);

    $decoded = json_decode($response, true);

    foreach ($decoded as $worker) {
      // Modify publish key to match key formating in workers 
      $modifiedKey = $key;
      // Replace '/'  and '.' with '_'
      $underscore = ["/", "."];
      $modifiedKey = str_replace($underscore, "_", $modifiedKey);
      // Replace ':' with '-'
      $modifiedKey = str_replace(":", "-", $modifiedKey);

      if ($worker['key'] == $modifiedKey) {
        $worker = file_get_contents($url . '/' . $worker['worker'], false, $context);
        break;
      }
    }

    return json_decode($worker, true);
  }


  private function publishDependencies()
  {
    $requestURI = $_SERVER['REQUEST_URI'];

    // Event By ID
    if (preg_match('/^\/comp-publish\/(.+)$/', $requestURI, $matches)) {
      $comp = $matches[1];

      if ($comp != null) {
        // Get component dependencies by ID
        $deps = $this->getDependencies($comp);

        // Publish dependent pages 
        $successCount = 0;
        foreach ($deps as $file) {
          $site = $file['sitename'];
          $path = $file['path'];
          // Display item being published
          echo "Publishing --- ", $site, " : ", $path, "\n";
          // Publish File and return key
          $key = $this->publishFile($site, $path, $comp);
          // Fetch worker for key
          $worker = $this->getWorker($key);
          var_dump($worker);
          // If worker in progress: wait 5s and check again
          if ($worker['status'] == 'IN_PROGRESS') {
            // sleep(5);
            // TODO: refactor status into function
            echo 'In progress';
          }
          // If worker doesn't succeed: show error message
          elseif ($worker['detail']['failed'] == true) {
            echo 'Failed: ' . $worker['detail']['failed'][$site][0][$path];
          }
          // If worker success: up count
          elseif ($worker['detail']['success'] == true) {
            $successCount += 1;
          } else {
            echo 'Unknown Error';
          }
        }

        echo "\nSuccessfully Published: " . $successCount;
      }
    } else {
      http_response_code(400);
      return json_encode(array('message' => 'No ID Provided'));
    }
  }
}

// Entry point
new PublishDependencies();
