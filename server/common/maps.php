<?php

$GLOBALS['hourlyMap'] = array(
  array(
    'time' => '06:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '07:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '08:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '09:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '10:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '11:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '12:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '13:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '14:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '15:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '16:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '17:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '18:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '19:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '20:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '21:00',
    'isAvailable' => FALSE
  ),
  array(
    'time' => '22:00',
    'isAvailable' => FALSE
  )
);

$GLOBALS['payMongo'] = array(
  'paths' => array(
    'createCheckoutSession' => 'v1/checkout_sessions',
    'getCheckoutSessionById' => 'v1/checkout_sessions/{sessionId}'
  )
);

$GLOBALS['statusMap'] = array(
  'tentative' => 'TENTATIVE',
  'confirmed' => 'CONFIRMED',
  'cancelled' => 'CANCELLED'
);

?>