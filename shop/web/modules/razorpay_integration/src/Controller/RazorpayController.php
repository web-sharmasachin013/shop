<?php

namespace Drupal\razorpay_integration\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RazorpayController {

  public function notify(Request $request) {
    // Handle webhook payload here
    $data = json_decode($request->getContent(), TRUE);

    // For now, just log it
    \Drupal::logger('razorpay_integration')->notice('Webhook: @data', ['@data' => print_r($data, TRUE)]);

    return new Response('Webhook received', 200);
  }

}
