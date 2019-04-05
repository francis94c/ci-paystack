<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PayStackTest {

  function testStandardPayment(&$ci) {
    $splint = $ci->load->splint("francis94c/ci-paystack");
    $splint->load->config("secrets");
    $params = array("secret_key" => $ci->config->item("paystack_secret_key"));
    $params["verify_host"] = false;
    $splint->load->library("PayStack", $params, "paystack");
    $url = $ci->paystack->authorizeTransaction("francis94c@gmail.com", 3000, "day558taa4t-tranxt47");
    $ci->load->helper("url");
    echo $url;
    echo $ci->paystack->getLastCurlError();
    echo $ci->paystack->getLastApiError();
  }
}
?>
