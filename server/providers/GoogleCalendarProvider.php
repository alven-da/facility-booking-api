<?php
namespace Provider;

use Google\Client as Google_Client;
use Google\Service\Calendar as Google_Service_Calendar;
use Google\Service\Calendar\Event as Google_Service_Calendar_Event;

class GoogleCalendar {
  private static $instance = null;
  private $googleService = null;

  protected function __construct() {
    $client = new Google_Client();
    $client->setAuthConfig('credentials.json');

    $appName = getenv('APPLICATION_NAME');

    // TODO - define in variable file
    $client->setApplicationName($appName);
    $client->addScope(Google_Service_Calendar::CALENDAR);

    $this->googleService = new Google_Service_Calendar($client);
  }

  public static function getInstance(): GoogleCalendar {
    if (!isset(self::$instance)) {
        self::$instance = new GoogleCalendar();
    }

    return self::$instance;
  }

  public function getEventsList($currentDateStr) {
    $from = strtotime($currentDateStr . 'T00:00:00');
    $to = strtotime($currentDateStr . 'T23:59:59');

    $startDateIso = date('c', $from);
    $endDateIso = date('c', $to);

    $optParams = array(
      'maxResults' => CALENDAR_RESULTS,
      'orderBy' => 'startTime',
      'singleEvents' => true,
      'timeMin' => $startDateIso,
      'timeMax' => $endDateIso
    );

    $calendarId = getenv('CALENDAR_ID');

    return $this->googleService->events->listEvents($calendarId, $optParams);
  }

  public function addEventToCalendar($date, $startTime, $ref) {
    $endTime = reserveByAnHour($startTime);

    $startDateTime = $date . 'T' . $startTime . ':00';
    $endDateTime = $date . 'T' . $endTime;

    $event = new Google_Service_Calendar_Event(array(
      'summary' => 'Court Facility',
      'description' => 'Booking Reference: ' . $ref[0] . ' / Payment Ref: ' . $ref[1],
      'start' => array(
        'dateTime' => $startDateTime,
        'timeZone' => 'Asia/Manila'
      ),
      'end' => array(
        'dateTime' => $endDateTime,
        'timeZone' => 'Asia/Manila'
      )
    ));

    $calendarId = getenv('CALENDAR_ID');

    return $this->googleService->events->insert($calendarId, $event);
  }
}

?>