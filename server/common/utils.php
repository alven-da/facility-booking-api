<?php

function responseWithHeader($response) {
  return $response  
    ->withHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
    ->withHeader('Content-Type', 'application/json');
}

function jsonResponse($response, $data, $status = 200) {
  $resp = responseWithHeader($response);

  return $resp->withJson(['status' => $status, 'data' => $data])->withStatus($status);
}

function defaultResponse($response, $body) {
  $resp = responseWithHeader($response);

  $resp->getBody()->write($body);

  return $resp;
}

function inRange($time, $start, $end) {
  return $time >= $start && $time < $end;
}

function reserveByAnHour($startTime) {
  $hour = explode(":", $startTime);
  $nextHour = intval($hour[0]) + 1;

  return str_pad($nextHour, 2, '0', STR_PAD_LEFT) . ':00:00';
}

function uuidv4($data = null) {
  // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
  $data = $data ?? random_bytes(16);
  assert(strlen($data) == 16);

  // Set version to 0100
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
  // Set bits 6-7 to 10
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

  // Output the 36 character UUID.
  return '' . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

?>