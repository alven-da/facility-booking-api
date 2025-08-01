<?php
require './providers/GoogleCalendarProvider.php';
require './providers/PayMongoProvider.php';
require './providers/MySQLProvider.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

require './controller/DotEnvEnvironment.php';

require './common/preload.php';
require './common/constants.php';
require './common/utils.php';
require './common/maps.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function (Request $request, Handler $handler) use ($app): Response {
  if ($request->getMethod() === 'OPTIONS') {
    $response = $app->getResponseFactory()->createResponse();
  } else {
      $response = $handler->handle($request);
  }

  $response = $response
    ->withHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');

  if (ob_get_contents()) {
      ob_clean();
  }

  return $response;
});

$app->get('/api/calendar', function (Request $request, Response $response, $args) {
  $queryParams = $request->getQueryParams();
  $selectedDate = $queryParams['selectedDate'];

  $calendarProvider = Provider\GoogleCalendar::getInstance();
  $list = $calendarProvider->getEventsList($selectedDate);

  $appNameFromEnv = getenv('APPLICATION_NAME');

  $existingEvents = array_map(function($evt) {
    return strtotime($evt['start']['dateTime']);
  }, $list->getItems());

  $updatedHourlyMap = array_map(function($hour) use ($selectedDate, $existingEvents) {
    $timeSlot = strtotime($selectedDate . 'T' . $hour['time']);

    $isExisting = array_search($timeSlot, $existingEvents);

    if ($isExisting !== 0) {
      $hour['isAvailable'] = TRUE;
    }

    return $hour;
  }, $GLOBALS['hourlyMap']);

  return jsonResponse($response, $updatedHourlyMap);
});

$app->get('/api/health', function (Request $request, Response $response,) {
  return jsonResponse($response, ['status' => 'ok']);
});

$app->post('/api/prepayment', function (Request $request, Response $response, $args) {
  $reqJson = $request->getBody();
  $reqArray = json_decode($reqJson, true);

  $reqHeaders = $request->getHeaders();
  
  // Placeholders
  $host = 'localhost';
  $protocol = 'http';

  if (isset($reqHeaders['Referrer'][0])) {
    $hostProtocolArray = explode('/', $reqHeaders['Referrer'][0]);
    $host = $hostProtocolArray[2];
    $protocol = str_replace(':', '', $hostProtocolArray[0]);
  }

  $env = getenv('ENVIRONMENT');

  $baseRedirectUrl = $env ? 'http://localhost:3000' : ($protocol . '://' . $host);
  $payMongo = Provider\PayMongo::getInstance();

  // Generate a unique confirmation ID for the booking
  $confirmationId = uuidv4();
  $bookTime = $GLOBALS['hourlyMap'][$reqArray['index']]['time'];
  $bookDate = $reqArray['date'];

  $paymentResult = $payMongo->createCheckout(array(
    'successUrl' => $baseRedirectUrl . '/success?id=' . $confirmationId,
    'cancelUrl' => $baseRedirectUrl . '/cancelled',
    'details' => array(
      'bookedDate' => $bookDate,
      'bookedTimes' => $bookTime
    )
  ));

  $paymentArr = json_decode($paymentResult, true);
  $paymentData = $paymentArr['data'];

  $checkoutId = $paymentData['id'];
  $checkoutUrl = $paymentData['attributes']['checkout_url'];

  $mysql = Provider\MySQL::getInstance();
  $conn = $mysql->getConnection();

  $sqlInsert = "INSERT INTO facility_booking (booking_id, checkout_id, booking_date, booking_time, status, member_id) VALUES (:booking_id, :checkout_id, :booking_date, :booking_time, :status, NULL);";

  $stmt = $conn->prepare($sqlInsert);
  $stmt->bindParam(':booking_id', $confirmationId);
  $stmt->bindParam(':checkout_id', $checkoutId);
  $stmt->bindParam(':booking_date', $bookDate);
  $stmt->bindParam(':booking_time', $bookTime);
  $stmt->bindParam(':status', $GLOBALS['statusMap']['tentative']);

  $stmt->execute();

  return jsonResponse($response, ['redirectUrl' => $checkoutUrl]);
});

$app->post('/api/validate', function (Request $request, Response $response, $args) {
  $queryParams = $request->getQueryParams();
  $confirmationId = $queryParams['confirmationId'];

  $mysql = Provider\MySQL::getInstance();
  $conn = $mysql->getConnection();

  // Check facility_booking record if there is an existing booking i.e. TENTATIVE status
  $sqlSelect = "SELECT checkout_id, description, details, booking_date, booking_time, status from facility_booking WHERE booking_id = :booking_id;";

  $stmt = $conn->prepare($sqlSelect);
  $stmt->bindParam(':booking_id', $confirmationId);
  $stmtResult = $stmt->execute();
  $resultArray = array();

  while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    array_push($resultArray, $row);
  }

  $resultSingle = $resultArray[0];

  $payMongo = Provider\PayMongo::getInstance();
  $result = $payMongo->getCheckoutById($resultSingle['checkout_id']);

  $resultJson = json_decode($result, true);

  $payments = $resultJson['data']['attributes']['payments'];

  if (count($payments) < 1) {
    $response = jsonResponse($response, [
      'message' => 'Your booking is not yet completed. Kindly settle the payment first to confirm payment'
    ], 402);

    return $response->withStatus(402);
  }

  // Update the facility_booking record to set the status to CONFIRMED
  $sqlUpdate = "UPDATE facility_booking SET status = :status WHERE booking_id = :booking_id;";

  $stmt = $conn->prepare($sqlUpdate);
  $stmt->bindParam(':booking_id', $confirmationId);
  $stmt->bindParam(':status', $GLOBALS['statusMap']['confirmed']);

  $stmt->execute();

  return jsonResponse($response, array('booking' => array(
    'bookingId' => $confirmationId,
    'description' => $resultSingle['description'],
    'details' => $resultSingle['details'],
    'date' => $resultSingle['booking_date'],
    'time' => $resultSingle['booking_time'],
    'status' => $GLOBALS['statusMap']['confirmed'],
    'paymentRef' => $payments[0]['id']
  )));
});

$app->get('/api/member/{memberId}', function (Request $request, Response $response, $args) {
  $pathParams = $args['memberId'];

  // TODO: a real member lookup
  if ($pathParams != 'SPRTS-12345') {
    $response = jsonResponse($response, array(
      'message' => 'Record Not Found'
    ));
    return $response->withStatus(404);
  }

  // TODO: Connect to MySQL to Fetch member record
  return jsonResponse($response, array(
    'id' => 'SPRTS-12345',
    'firstName' => 'Juan',
    'lastName' => 'dela Cruz'
  ));
});

$app->run();

?>