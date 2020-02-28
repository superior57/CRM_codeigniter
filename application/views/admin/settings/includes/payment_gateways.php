<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="horizontal-scrollable-tabs">
  <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
  <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
  <div class="horizontal-tabs">
    <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
     <li role="presentation" class="active">
      <a href="#payment_modes_general" aria-controls="payment_modes_general" role="tab" data-toggle="tab"><?php echo _l('settings_group_general'); ?></a>
    </li>
    <?php
    foreach ($payment_gateways as $gateway) {
        ?>
      <li role="presentation">
        <a href="#online_payments_<?php echo $gateway['id']; ?>_tab"
          aria-controls="online_payments_paypal_tab"
          role="tab"
          data-toggle="tab">
            <?php echo $gateway['instance']->getName(); ?>
          </a>
      </li>
    <?php
    } ?>
  </ul>
  <div class="tab-content mtop30">
   <div role="tabpanel" class="tab-pane active" id="payment_modes_general">
    <?php render_yes_no_option('notification_when_customer_pay_invoice', 'notification_when_customer_pay_invoice'); ?>
    <hr />
    <?php render_yes_no_option('allow_payment_amount_to_be_modified', 'settings_allow_payment_amount_to_be_modified'); ?>
  </div>
  <?php
  foreach ($payment_gateways as $gateway) {
      ?>
    <div role="tabpanel" class="tab-pane" id="online_payments_<?php echo $gateway['id']; ?>_tab">
     <h4><?php echo $gateway['instance']->getName(); ?></h4>
     <?php hooks()->do_action('before_render_payment_gateway_settings', $gateway); ?>
     <hr />
    <?php
     $settings = $gateway['instance']->getSettings();

      foreach ($settings as $option) {
          $value = get_option($option['name']);

          $value = isset($option['encrypted']) && $option['encrypted'] == true ? $this->encryption->decrypt($value) : $value;

          if (!isset($option['type'])) {
              $option['type'] = 'input';
          }

          $fieldAttributes = (isset($option['field_attributes']) ? $option['field_attributes'] : []);
          $optionName      = 'settings[' . $option['name'] . ']';
          $optionLabel     = $option['label'];

          if ($option['type'] == 'yes_no') {
              render_yes_no_option($option['name'], $option['label']);
          } elseif ($option['type'] == 'input') {
              echo render_input($optionName, $optionLabel, $value, (isset($option['input_type']) ? $option['input_type'] : 'text'), $fieldAttributes);
          } elseif ($option['type'] == 'textarea') {
              echo render_textarea($optionName, $optionLabel, $value, $fieldAttributes);
          }

          if (isset($option['after'])) {
              echo $option['after'];
          }
      }
    ?>
  </div>
<?php
  } ?>
</div>
</div>
</div>
