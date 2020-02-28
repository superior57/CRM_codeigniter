<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="mtop15 preview-top-wrapper">
   <div class="row">
      <div class="col-md-3">
         <div class="mbot30">
            <div class="subscription-html-logo">
               <?php echo get_dark_company_logo(); ?>
            </div>
         </div>
      </div>
      <div class="clearfix"></div>
   </div>
   <div class="top" data-sticky data-sticky-class="preview-sticky-header">
      <div class="container preview-sticky-container">
           <div class="row">
            <div class="col-md-12">
             <div class="pull-left">
              <h4 class="bold no-mtop subscription-html-name"><?php echo $subscription->name; ?><br />
               <small class="proposal-html-description"><?php echo $subscription->description; ?></small>
             </h4>
           </div>
           <div class="visible-xs">
            <div class="clearfix"></div>
          </div>
          <?php
          if(!empty($publishableKey)) {
           echo '<div class="pull-right">';
           if(empty($subscription->stripe_subscription_id)) {
             echo form_open(site_url('subscription/subscribe/' . $hash), ['class' => 'action-button pull-right mbot15', 'id'=>'sourceForm', 'onsubmit'=>'document.getElementById(\'subscribe-button\').setAttribute(\'disabled\', true);']);
             echo '<button type="submit" name="subscribe" id="subscribe-button" value="true" class="btn btn-success action-button">';
             echo _l('subscribe');
             echo '</button>';
             echo form_close();
           } else if(isset($stripeSubscription) && $stripeSubscription->status === 'incomplete') {
             echo '<a href="'.$stripeSubscription->latest_invoice->hosted_invoice_url.'" class="btn btn-info">'._l('subscription_complete_payment').'</a>';
           }
           echo '</div>';
         }
         if (can_logged_in_contact_view_subscriptions()) {
           ?>
           <a href="<?php echo site_url('clients/subscriptions/'); ?>"
            class="btn btn-default pull-right mright5 action-button go-to-portal">
            <?php echo _l('client_go_to_dashboard'); ?>
          </a>
        <?php } ?>
        <div class="clearfix"></div>
      </div>
    </div>
   </div>
</div>
</div>
<div class="clearfix"></div>
<div class="panel_s mtop20">
   <div class="panel-body">
      <div class="col-md-10 col-md-offset-1">
         <div class="row mtop20">
            <div class="col-md-6 col-sm-6 transaction-html-info-col-left">
               <address class="subscription-html-company-info">
                  <?php echo format_organization_info(); ?>
               </address>
            </div>
            <div class="col-sm-6 text-right transaction-html-info-col-right">
               <span class="bold subscription-html-bill-to"><?php echo _l('invoice_bill_to'); ?>:</span>
               <address class="subscription-html-customer-billing-info">
                  <?php echo format_customer_info($invoice, 'invoice', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if (isset($invoice->include_shipping) && $invoice->include_shipping == 1 && $invoice->show_shipping_on_invoice == 1) {
                  ?>
                  <span class="bold subscription-html-ship-to"><?php echo _l('ship_to'); ?>:</span>
                  <address class="subscription-html-customer-shipping-info">
                     <?php echo format_customer_info($invoice, 'invoice', 'shipping'); ?>
                  </address>
               <?php } ?>
               <p class="no-mbot subscription-number">
                  <span class="bold">
                     <?php echo _l('subscription'); ?> #:
                  </span>
                  <?php echo $subscription->id; ?>
               </p>
               <p class="no-mbot subscription-date">
                  <span class="bold">
                     <?php echo _l('subscription_date'); ?>:
                  </span>
                  <?php
                  echo !empty($subscription->stripe_subscription_id)
                  ? _d(date('Y-m-d', strtotime($subscription->date_subscribed)))
                  : _d(date('Y-m-d'));
                  ?>
               </p>
               <?php if (!empty($subscription->date)) {
                  ?>
                  <p class="no-mbot subscription-first-billing-date">
                     <span class="bold">
                        <?php echo _l('first_billing_date'); ?>:
                     </span>
                     <?php if (!empty($subscription->stripe_subscription_id)) {
                        echo _d($subscription->date);
                     } else {
                        if ($subscription->date <= date('Y-m-d')) {
                          echo _d(date('Y-m-d'));
                       } else {
                          echo _d($subscription->date);
                       }
                    } ?>
                 </p>
              <?php } ?>
              <?php if ($invoice->project_id != 0 && get_option('show_project_on_invoice') == 1) {
               ?>
               <p class="no-mbot subscription-project">
                  <span class="bold"><?php echo _l('project'); ?>:</span>
                  <?php echo get_project_name_by_id($invoice->project_id); ?>
               </p>
            <?php } ?>
         </div>
      </div>
      <div class="row">
         <div class="col-md-12">
            <div class="table-responsive">
               <?php
               $items = get_items_table_data($invoice, 'invoice');
               echo $items->table();
               ?>
            </div>
         </div>
         <div class="col-md-6 col-md-offset-6">
            <table class="table text-right">
               <tbody>
                  <tr id="subtotal">
                     <td><span class="bold"><?php echo _l('invoice_subtotal'); ?></span>
                     </td>
                     <td class="subtotal">
                        <?php echo app_format_money($invoice->subtotal, $invoice->currency_name); ?>
                     </td>
                  </tr>
                  <?php
                  foreach ($items->taxes() as $tax) {
                    echo '<tr class="tax-area"><td class="bold">' . $tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)</td><td>' . app_format_money($tax['total_tax'], $invoice->currency_name) . '</td></tr>';
                 }
                 ?>
                 <tr>
                  <td><span class="bold"><?php echo _l('invoice_total'); ?></span>
                  </td>
                  <td class="total">
                     <?php echo app_format_money($invoice->total, $invoice->currency_name); ?>
                  </td>
               </tr>
               <?php if (get_option('show_amount_due_on_invoice') == 1
                  && $invoice->status != Invoices_model::STATUS_CANCELLED
                  && empty($subscription->stripe_subscription_id)) {
                    ?>
                    <tr>
                     <td>
                        <span class="<?php if ($invoice->total_left_to_pay > 0) { echo 'text-danger '; } ?>bold">
                           <?php echo _l('invoice_amount_due'); ?>
                        </span>
                     </td>
                     <td>
                        <span class="<?php if ($invoice->total_left_to_pay > 0) { echo 'text-danger'; } ?>">
                           <?php echo app_format_money($invoice->total_left_to_pay, $invoice->currency_name); ?>
                        </span>
                     </td>
                  </tr>
               <?php } ?>
            </tbody>
         </table>
      </div>
      <?php if (!empty($invoice->clientnote)) {
         ?>
         <div class="col-md-12 subscription-html-note">
            <b><?php echo _l('invoice_note'); ?></b><br /><br /><?php echo $invoice->clientnote; ?>
         </div>
      <?php } ?>
      <?php if (!empty($invoice->terms) || !empty($subscription->terms)) {
         ?>
         <div class="col-md-12 subscription-html-terms-and-conditions">
            <hr />
            <b><?php echo _l('terms_and_conditions'); ?></b><br /><br />
            <?php
              echo empty($subscription->terms)
              ? $invoice->terms
              : $subscription->terms;
            ?>
         </div>
      <?php } ?>
      <?php if (count($child_invoices) > 0) {
         ?>
         <div class="col-md-12 subscription-child-invoices">
            <hr />
            <h4><?php echo _l('invoices'); ?></h4>
            <table class="table">
               <thead>
                  <tr>
                     <th><?php echo _l('invoice_add_edit_number'); ?></th>
                     <th><?php echo _l('invoice_dt_table_heading_date'); ?></th>
                     <th><?php echo _l('invoice_total'); ?></th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($child_invoices as $child_invoice) {
                     ?>
                     <tr>
                        <td>
                           <a href="<?php echo site_url('invoice/' . $child_invoice->id . '/' . $child_invoice->hash); ?>" target="_blank">
                              <?php echo format_invoice_number($child_invoice->id); ?>
                           </a>
                        </td>
                        <td><?php echo _d($child_invoice->date); ?></td>
                        <td><?php echo app_format_money($child_invoice->total, $child_invoice->currency_name); ?></td>
                     </tr>
                  <?php } ?>
               </tbody>
            </table>
         </div>
      <?php } ?>
   </div>
</div>
</div>
</div>
<script>
   $(function(){
    new Sticky('[data-sticky]');
 });
</script>
