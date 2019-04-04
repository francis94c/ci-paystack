<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Paystack {

  const CURL_RETURN_ERROR = "curl_return_error";

  const API_ERROR = "api_error";

  const NO_REFERENCE = "no_reference";

  private $ci;

  private $secretKey;

  private $lastCurlError;

  private $lastAPIError;

  private $lastResponseData;

  function __construct($params=null) {
    if (isset($params["secret_key"])) $this->secretKey = $params["secret_key"];
    $this->ci =& get_instance();
  }
  /**
   * [authorizeTransaction description]
   * @param  [type] $email  [description]
   * @param  [type] $amount [description]
   * @return [type]         [description]
   */
  function authorizeTransaction($email, $amount, $reference) {
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
    $this->lastResponseData = $transaction;
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
    // Verify request type.
    if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST' ) ||
    !array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER)) return false;
    // Verify IP Addresses (Whitelisted PayStack IPs);
    $ips = array(
      "52.31.139.75",
      "52.49.173.169",
      "52.214.14.220"
    );
    if (!in_array($this->ci->input->ip_address(), $ips)) return false;
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
    $this->lastResponseData = $event;
    return $event->event === "charge.success";
  }
  /**
   * [verifyTransaction description]
   * @return [type] [description]
   */
  function verifyTransaction() {
    $curl = curl_init();
    $reference = $this->ci->input->get("reference") != "" ? $this->ci->input->get("reference") : "";
    if (!$reference) return self::NO_REFERENCE;
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "authorization: Bearer $this->secret_key",
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
    $this->lastResponseData = $transaction;
    return $transaction->status && $transaction->data->status == "success";
  }
  /**
   * [getData description]
   * @return [type] [description]
   */
  function getData() {
    return $this->lastResponseData;
  }
}
?>
