<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PayStackEvents {

  const SUBSCRIPTION_CREATE = "subscritpion.create";

  const SUBSCRIPTION_ENABLE = "subscription.enable";

  const SUBSCRIPTION_DISABLE = "subscription.disable";

  const INVOICE_CREATE = "invoice.create";

  const INVOICE_UPDATE = "invoice.update";

  const CHARGE_SUCCESS = "charge.success";

  const TRANSFER_SUCCESS = "transfer.success";

  const TRANSFER_FAILED = "transfer.failed";

  const INVOICE_FAILED = "invoice.failed";

}
?>
