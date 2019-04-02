<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Paystack {

  const CURL_RETURN_ERROR = "curl_return_error";

  const API_ERROR = "api_error";

  private $secretKey;

  private $lastCurlError;

  private $lastAPIError;

  function __construct($params=null) {
    if (isset($params["secret_key"])) $this->secretKey = $params["secret_key"];
  }
  /**
   * [authorizeTransaction description]
   * @param  [type] $email  [description]
   * @param  [type] $amount [description]
   * @return [type]         [description]
   */
  function authorizeTransaction($email, $amount) {
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        "amount" => $amount,
        "email"  => $email,
      ]),
      CURLOPT_HTTPHEADER => [
        "authorization: Bearer $this->secretKey",
        "content-type: application/json",
        "cache-control: no-cache"
      ],
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    if ($err) {
      $this->$lastCurlError = $err;
      return self::CURL_RETURN_ERROR;
    }
    $transaction = json_decode($response);
    if (!$transaction->status) {
      $this->lastAPIError = $transaction->message;
      return self::API_ERROR;
    }
    return $transaction->data->authorization_url;
  }
  /**
   * [getLastCurlError description]
   * @return [type] [description]
   */
  function getLastCurlError() {
    return $this->$lastCurlError;
  }
  /**
   * [getLastApiError description]
   * @return [type] [description]
   */
  function getLastApiError() {
    return $this->lastAPIError;
  }
  /**
   * [handleChargeSuccessEvent description]
   * @return [type] [description]
   */
  function handleChargeSuccessEvent() {
    // Retrieve the request's body
    $body = @file_get_contents("php://input");
    $signature = (isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE']) ? $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] : '');
    // TODO: Log Events.
    if (!$signature) return false;
    // confirm the event's signature
    if($signature !== hash_hmac('sha512', $body, $this->secretKey)) return false;
    // Tell Paystack we recieved the reequest.
    http_response_code(200);
    $event = json_decode($body);
    return $event->event === "charge.success";
  }
}
?>
