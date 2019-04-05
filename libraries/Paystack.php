<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PayStack {

  const PACKAGE = "francis94c/ci-paystack";

  const CURL_RETURN_ERROR = "curl_return_error";

  const API_ERROR = "api_error";

  const NO_REFERENCE = "no_reference";

  private $ci;

  private $secretKey;

  private $verifyHost = true;

  private $lastCurlError;

  private $lastAPIError;

  private $lastResponseData;

  function __construct($params=null) {
    if (isset($params["secret_key"])) $this->secretKey = $params["secret_key"];
    if (isset($params["verify_host"])) $this->verifyHost = $params["verify_host"];
    $this->ci =& get_instance();
    $this->ci->load->splint(self::PACKAGE, "+PayStackEvents");
  }
  /**
   * [authorizeTransaction description]
   * @param  [type] $email  [description]
   * @param  [type] $amount [description]
   * @return [type]         [description]
   */
  function authorizeTransaction($email, $amount, $reference=null, $callback=null) {
    $data = array();
    if (is_array($email)) {
      $data["email"] = $email["email"];
      $data["amount"] = $email["amount"];
      if ($email["reference"]) $data["reference"] = $email["reference"];
      if ($email["quantity"]) $data["quantity"] = $email["quantity"];
      if ($email["callback_url"]) $data["callback_url"] = $email["callback_url"];
      if ($email["plan"]) $data["plan"] = $email["plan"];
      if ($email["invoice_limit"]) $data["invoice_limit"] = $email["invoice_limit"];
      if ($email["metadata"]) $data["metadata"] = $email["metadata"];
      if ($email["cancel_action"]) $data["metadata.cancel_action"] = $email["cancel_action"];
      if ($email["subaccount"]) $data["subaccount"] = $email["subaccount"];
      if ($email["transaction_charge"]) $data["transaction_charge"] = $email["transaction_charge"];
      if ($email["bearer"]) $data["bearer"] = $email["bearer"];
      if ($email["channels"]) $data["channels"] = $email["channels"];
    } else {
      $data["email"] = $email;
      $data["amount"] = $amount;
      if ($reference != null) $data["reference"] = $reference;
      if ($callback != null) $data["callback_url"] = $callback;
    }
    $curl = curl_init();
    if (!$this->verifyHost) {
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    }
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode($data),
      CURLOPT_HTTPHEADER => [
        "authorization: Bearer $this->secretKey",
        "content-type: application/json",
        "cache-control: no-cache"
      ],
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    if ($err) {
      $this->lastCurlError = $err;
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
    return $this->lastCurlError;
  }
  /**
   * [getLastApiError description]
   * @return [type] [description]
   */
  function getLastApiError() {
    return $this->lastAPIError;
  }
  /**
   * [handleEvent description]
   * @return [type] [description]
   */
  function handleEvent() {
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
    return $event->event;
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
