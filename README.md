# ci-paystack #
----

ci-paystack is PHP library for the integrating the PayStack payment gate way in your Code Igniter Web Applications.

## Installation ##

Download and install Splint from https://splint.cynobit.com/downloads/splint

Open a terminal at the root of your Code Igniter project and run

```bash
splint install francis94c/ci-paystack
```

## Usage ##

__To Load__

```php
$params = array(
    "secret_key" => "skfnljsbkjvxnlkvnkhbvknd34es"
);
$this->load->splint("francis94c/ci-paystack", "+PayStack", $params, "paystack");

// OR

$params = array(-
    "secret_key" => "skfnljsbkjvxnlkvnkhbvknd34es"
);
$package = $this->load->splint("francis94c/ci-paystack");
$package->load->library("PayStack", $params, "paystack");
```

__To Authorize or Begin Transaction__

```php
// After Loading the Library.
$authorization_url = $this->paystack->authorizeTransaction("someone@gmail.com", 40000, "paystack-ref-45");
// 40000 kobo = N40.00
// The last parameter must be unique for each call not random by systematically sequential for easy tracking of transactions and to counter duplicate transactions.
// e.g paystack-ref-46, paystack-ref-47, paystack-ref-48......
// Carry out some Database CRUD processes here maybe... Then.

redirect($authorization);  // URL Helper required.
```

Payments can be made once the user is re-directed to the authorization URL (Payment Gateway Site).

On successful payment, the user is redirected to the callback URL set on your PayStack Control panel at <https://dashboard.paystack.com/#/settings/developer> and attach to the URL, the reference code you supplied when you authorized the transaction.

To verify that the payment was made from your end do the below on the controller PayStack is set to redirect to when the transaction is complete.

```php
// After Loading the Library
if ($this->paystack->verifyTransaction()) {
    // Record in your database that payment was made...
    // Give value.
} else {
    // Payment/Transaction wasn't successful
}
```

PayStack will also fire your webhook whenever an event happens. An even such as a successful payment will prompt PayStack to make a request to your webhook URL.

It's a good practice to handle these requests to further verify the authenticity of your transactions and prevent error in payment because of a network error.

This could be your final check in verify transactions.

You could do this within a controller that will handle the automated request from PayStack

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PayStackWebHook {
    
    function index() {
        $params = array(
            "secret_key" => "sk_fnljsbkjvxnlkvnkhbvknd34es"
        );
        $this->load->splint("francis94c/ci-paystack", "+PayStack", $params, "paystack");
        
        $event = $this->paystack->handleEvent(); // Get Event.
        
        $amount = 40000 // Get this from a database.
        
        if ($event == PayStackEvents::CHARGE_SUCCESS) {
            // Probably verify transaction reference (Optional)
            $reference = $this->paystack->getData()["data"]["reference"];
            $event_amount = $this->paystack->getData()["data"]["amount"];
            if ($this->paystack->verifyTransaction($reference) && $amount = $event_amount) {
                // Give Value
            }
            // You can give value of you don't want to verify.
            // However, you can use the trabsaction reference to track your order.
        }
    }
}
?>
```

## Wiki ##

Please visit https://github.com/francis94c/ci-paystack/wiki

