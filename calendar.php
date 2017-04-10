<?php

require_once __DIR__ . '/vendor/autoload.php';
define('APPLICATION_NAME', 'WebCalendar');
define('CREDENTIALS_PATH', __DIR__ . '/.credentials/calendar.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
    Google_Service_Calendar::CALENDAR_READONLY)
));

/*
  if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
  }
 */

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
    $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false,),));

    $client = new Google_Client();
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfig(CLIENT_SECRET_PATH);
    $client->setAccessType('offline');
    $client->setApprovalPrompt("force"); //some recomendation
    $client->setHttpClient($guzzleClient);

    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    //echo "created Client";
    $client->setAccessToken($accessToken);
    //print_r($accessToken);
    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        //print_r($client->getRefreshToken());
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        //print_r("fetAccess");
        $newAccessToken = $client->getAccessToken();
        //print_r($newAccessToken);
        $accessToken = array_merge($accessToken, $newAccessToken);
        //print_r($accessToken);
        file_put_contents($credentialsPath, json_encode($accessToken));
        //print_r("End");
    }

    return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
}

/**
 * 
 */
function FeetEvent($results) {

    if (count($results->getItems()) == 0) {
        
    } else {
        $out = array();
        foreach ($results->getItems() as $event) {
            //print_r($event->summary).length()));
            
            /// Check All Day
            if (isset($event->start->date)) {
                $startTime = strtotime($event->start->date);
                $endTime = strtotime($event->end->date);
            } else {
                $startTime = strtotime($event->start->dateTime);
                $endTime = strtotime($event->end->dateTime);
            }
            
            // check no title
            if($event->summary == NULL){
                $event->summary = "Untitle";
            }
            // get Event id
            $event_class = array("event-important","event-success","event-special","event-inverse","event-info");
            //print_r($event_class);
            $out[] = array(
                'id' => $event->id,
                'title' => $event->summary,
                'url' => 'NULL',
                'class' => $event_class[strlen($event->summary)%5],
                'colorId' => $event->colorId,
                'desc' => $event->description,
                'start' => $startTime . '000', //strtotime($row->datetime) . '000',
                'end' => $endTime . '000'
            );
        }
        echo json_encode(array('success' => 1, 'result' => $out));
    }
}

// Get the API client and construct the service object.
//echo "Start";

if (!isset($_GET['from'])) {
    $start = date(DATE_ATOM, mktime(0, 0, 0, date("m"), 1, date("Y")));
    $end = date(DATE_ATOM, mktime(0, 0, 0, date("m") + 1, 1, date("Y")));
} else {
    $start = date(DATE_ATOM, $_GET['from']);
    $end = date(DATE_ATOM, $_GET['to']);
}

$client = getClient();
//echo "created Client";
$service = new Google_Service_Calendar($client);
//echo "End service";
// Print the next 10 events on the user's calendar.
$calendarId = '7ob7ucuo000mrahnfa2v0p752k@group.calendar.google.com';
$optParams = array(
    'maxResults' => 100,
    'orderBy' => 'startTime',
    'singleEvents' => TRUE,
    'timeMin' => $start,
);
$results = $service->events->listEvents($calendarId, $optParams);
FeetEvent($results);
?>