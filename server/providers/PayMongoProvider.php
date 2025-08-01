<?php

namespace Provider;

use GuzzleHttp\Client as Http_Client;

class PayMongo {
  private static $instance = null;

  protected function __construct() {}

  public static function getInstance(): PayMongo {
    if (!isset(self::$instance)) {
        self::$instance = new PayMongo();
    }

    return self::$instance;
  }

  private function getBase64Key() {
    $secretKey = getenv('PAYMONGO_SECRET_KEY');

    return base64_encode($secretKey . ':');
  }

  private function getSecurityHeaders() {
    $secretKey = $this->getBase64Key();

    return array(
        'authorization' => 'Basic ' . $secretKey,
        'content-type' => 'application/json',
        'accept' => 'application/json'
    );
  }

  public function createCheckout($args = array()) {
    $cancelUrl = $args['cancelUrl'];
    $successUrl = $args['successUrl'];
    $bookedDate = $args['details']['bookedDate'];
    $bookedTime = $args['details']['bookedTimes'];

    // TODO: Http Request
    // TODO: Environment variables for PayMongo credentials

    $baseUrl = getenv('PAYMONGO_API_BASE_URL');

    $reqBody = array(
      'data' => array(
        'attributes' => array(
          'send_email_receipt' => true,
          'show_description' => true,
          'show_line_items' => true,

          // TODO: to enums
          'description' => 'Tennis Court Reservation',
          'line_items' => array(
            array(
              'amount' => 100 * 100,

              // TODO: to enums
              'currency' => 'PHP',
              'name' => 'Date: ' . $bookedDate . ' @ ' . $bookedTime,
              'quantity' => 1
            )
            ),

            // TODO: to enums
            'payment_method_types' => array(
              'gcash',
              'card'
            ),
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl
        )
      )
    );

    $reqOptions = array(
      'headers' => $this->getSecurityHeaders(),
      'json' => $reqBody
    );

    $client = new Http_Client();
    $response = $client->request('POST', $baseUrl . 'v1/checkout_sessions', $reqOptions);

    return $response->getBody()->read(4096);
  }

  public function getCheckoutById($checkoutId) {
    $baseUrl = getenv('PAYMONGO_API_BASE_URL');
    $secretKey = $this->getBase64Key();

    $fullUrl = $baseUrl . 'v1/checkout_sessions/' . $checkoutId;

    $reqOptions = array(
      'headers' => $this->getSecurityHeaders()
    );

    $client = new Http_Client();
    $response = $client->request('GET', $fullUrl, $reqOptions);

    return $response->getBody()->read(4096);
  }
}

?>