<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php render_yes_no_option('show_subscriptions_in_customers_area','show_subscriptions_in_customers_area','show_subscriptions_in_customers_area_help'); ?>
<hr />
<h4 class="mbot20 font-medium"><?php echo _l('after_subscription_payment_succeeded'); ?></h4>
<div class="radio radio-info">
  <input type="radio" id="send_invoice_and_receipt" name="settings[after_subscription_payment_captured]" value="send_invoice_and_receipt"<?php if(get_option('after_subscription_payment_captured') == 'send_invoice_and_receipt'){echo ' checked';} ?>>
  <label for="send_invoice_and_receipt"><?php echo _l('subscription_option_send_payment_receipt_and_invoice'); ?>
    <?php if(is_sms_trigger_active(SMS_TRIGGER_PAYMENT_RECORDED)) { echo ' + <span class="text-has-action" data-toggle="tooltip" title="'._l('invoice_payment_recorded').'">SMS</span>';} ?>
  </label>
</div>
<div class="radio radio-info">
  <input type="radio" id="send_invoice" name="settings[after_subscription_payment_captured]" value="send_invoice"<?php if(get_option('after_subscription_payment_captured') == 'send_invoice'){echo ' checked';} ?>>
  <label for="send_invoice"><?php echo _l('subscription_option_send_invoice'); ?>
    <?php if(is_sms_trigger_active(SMS_TRIGGER_PAYMENT_RECORDED)) { echo ' + <span class="text-has-action" data-toggle="tooltip" title="'._l('invoice_payment_recorded').'">SMS</span>';} ?>
  </label>
</div>
<div class="radio radio-info">
  <input type="radio" id="send_payment_receipt" name="settings[after_subscription_payment_captured]" value="send_payment_receipt"<?php if(get_option('after_subscription_payment_captured') == 'send_payment_receipt'){echo ' checked';} ?>>
  <label for="send_payment_receipt"><?php echo _l('subscription_option_send_payment_receipt'); ?>
    <?php if(is_sms_trigger_active(SMS_TRIGGER_PAYMENT_RECORDED)) { echo ' + <span class="text-has-action" data-toggle="tooltip" title="'._l('invoice_payment_recorded').'">SMS</span>';} ?>
  </label>
</div>
<div class="radio radio-info">
  <input type="radio" id="nothing" name="settings[after_subscription_payment_captured]" value="nothing"<?php if(get_option('after_subscription_payment_captured') == 'nothing'){echo ' checked';} ?>>
  <label for="nothing"><?php echo _l('subscription_option_do_nothing'); ?></label>
</div>
<p><?php echo _l('email_template'); ?>: <b>Subscription Payment Succeeded</b></p>
<hr />
