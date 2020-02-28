<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
 <div class="content">
  <div class="row">
   <div class="col-md-5">
    <div class="panel_s">
     <div class="panel-body accounting-template">
      <?php if(isset($subscription)) {
       if(!empty($subscription->stripe_subscription_id)
        && $subscription->status != 'canceled' && $subscription->status != 'incomplete_expired' && $subscription->status != 'incomplete'){
         ?>
         <div class="alert alert-success">
          <b><?php echo _l('customer_is_subscribed_to_subscription_info'); ?></b><br />
          Subscription ID: <?php echo $subscription->stripe_subscription_id; ?>
        </div>
        <?php } ?>
        <input type="hidden" name="isedit">
        <?php if(isset($subscription)) { ?>
          <a href="#" class="btn btn-default" data-target="#subscription_send_to_client_modal" data-toggle="modal">
            <span class="btn-with-tooltip" data-toggle="tooltip" data-title="<?php echo _l('send_to_email'); ?>" data-placement="bottom">
              <i class="fa fa-envelope"></i></span>
            </a>
            <a href="<?php echo site_url('subscription/'.$subscription->hash); ?>" class="btn btn-default" target="_blank">
              <?php echo _l('view_subscription'); ?>
            </a>
            <?php } ?>
            <?php
            if(!empty($subscription->stripe_subscription_id) && $subscription->status != 'canceled' && $subscription->status != 'incomplete_expired' && empty($subscription->ends_at)){ ?>
             <?php if(has_permission('subscriptions','','edit')){ ?>
              <div class="btn-group">
               <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                 <?php echo _l('cancel'); ?> <span class="caret"></span></a>
                 <ul class="dropdown-menu dropdown-menu-right">
                  <li><a href="<?php echo admin_url('subscriptions/cancel/'.$subscription->id.'?type=immediately'); ?>">
                    <?php echo _l('cancel_immediately'); ?></a></li>
                    <li><a href="<?php echo admin_url('subscriptions/cancel/'.$subscription->id.'?type=at_period_end'); ?>">
                      <?php echo _l('cancel_at_end_of_billing_period'); ?>
                    </a>
                  </ul>
                </div>
                <?php } ?>
                <?php } ?>
                <hr />
                <?php } ?>
                <?php $this->load->view('admin/subscriptions/form'); ?>
              </div>
            </div>
          </div>
          <?php if(isset($subscription)) { ?>
           <div class="col-md-7">
            <div class="panel_s">
             <div class="panel-body">
              <div class="horizontal-scrollable-tabs preview-tabs-top">
                 <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
                 <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
                 <div class="horizontal-tabs">
                    <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
               <li role="presentation" class="active">
                <a href="#upcoming_invoice" aria-controls="upcoming_invoice" role="tab" data-toggle="tab">
                  <?php echo _l('upcoming_invoice'); ?>
                </a>
              </li>
              <li role="presentation" class="tab-separator">
                <a href="#child_invoices" aria-controls="child_invoices" role="tab" data-toggle="tab">
                  <?php echo _l('child_invoices'); ?>
                </a>
              </li>
              <li role="presentation" data-toggle="tooltip" title="<?php echo _l('emails_tracking'); ?>" class="tab-separator">
                <a href="#tab_emails_tracking" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab">
                     <?php if(!is_mobile()){ ?>
                     <i class="fa fa-envelope-open-o" aria-hidden="true"></i>
                     <?php } else { ?>
                     <?php echo _l('emails_tracking'); ?>
                     <?php } ?>
                </a>
              </li>
            </ul>
          </div>
        </div>
            <div class="tab-content">
             <div role="tabpanel" class="tab-pane active" id="upcoming_invoice">
              <?php if(!empty($subscription->stripe_subscription_id) && $subscription->status != 'canceled' && !empty($subscription->ends_at)) { ?>
                <div class="alert alert-info">
                 <a href="https://stripe.com/docs/subscriptions/canceling-pausing#canceling" target="_blank"><i class="fa fa-link"></i></a> <?php echo _l('subscription_will_be_canceled_at_end_of_billing_period'); ?>
                 <?php if(has_permission('subscriptions','','edit')){ ?>
                   <br />
                   <a href="<?php echo admin_url('subscriptions/resume/'.$subscription->id); ?>">
                     <?php echo _l('resume_now'); ?>
                   </a>
                   <?php } ?>
                 </div>
                 <?php if(isset($upcoming_invoice) && $upcoming_invoice->total > 0) { ?>
                  <div class="alert alert-success">
                    <a href="https://stripe.com/docs/subscriptions/canceling-pausing#invoices-and-invoice-items" target="_blank"><i class="fa fa-link"></i></a> After canceling a subscription, a customer could still be charged if there are invoice items you created or there was a proration and the subscription is canceled at period end.
                  </div>
                  <?php } ?>
                  <?php } else if(!empty($subscription->stripe_subscription_id) && $subscription->status == 'canceled') { ?>
                    <div class="alert alert-info">
                     <a href="https://stripe.com/docs/subscriptions/canceling-pausing#reactivating-canceled-subscriptions" target="_blank"><i class="fa fa-link"></i></a> <?php echo _l('subscription_is_canceled_no_resume'); ?>
                   </div>
                   <?php } else if(!empty($subscription->stripe_subscription_id) && $subscription->status == 'incomplete_expired') { ?>
                   <div class="alert alert-warning">
                     <a href="https://stripe.com/docs/billing/lifecycle#incomplete" target="_blank"><i class="fa fa-link"></i></a> <?php echo _l('subscription_is_subscription_is_expired'); ?>
                   </div>
                   <?php } else if(empty($subscription->stripe_subscription_id)) { ?>
                     <div class="alert alert-info no-mbot">
                        <?php echo _l('subscription_not_yet_subscribed'); ?>
                     </div>
                   <?php } ?>
                   <?php if(!empty($subscription->stripe_subscription_id) && isset($upcoming_invoice)) {
                    if(!empty($subscription->stripe_subscription_id) && $subscription->status != 'canceled' && !empty($subscription->ends_at) && $upcoming_invoice->total == 0) {
                    } else {
                      $this->load->view('admin/invoices/invoice_preview_html', array('invoice'=>$upcoming_invoice));
                    }
                    ?>
                    <?php } ?>
                  </div>
                  <div role="tabpanel" class="tab-pane" id="child_invoices">
                    <?php if(count($child_invoices)){ ?>
                      <p class="mtop20 bold"><?php echo _l('invoices'); ?></p>
                      <br />
                      <ul class="list-group">
                       <?php foreach($child_invoices as $invoice){ ?>
                         <li class="list-group-item">
                          <a href="<?php echo admin_url('invoices/list_invoices/'.$invoice->id); ?>" target="_blank"><?php echo format_invoice_number($invoice->id); ?>
                          <span class="pull-right bold"><?php echo app_format_money($invoice->total, $invoice->currency_name); ?></span>
                        </a>
                        <br />
                        <span class="inline-block mtop10">
                          <?php echo '<span class="bold">'._d($invoice->date).'</span>'; ?><br />
                          <?php echo format_invoice_status($invoice->status,'',false); ?>
                        </span>
                      </li>
                      <?php } ?>
                    </ul>
                    <?php } else { ?>
                      <div class="alert alert-info no-mbot">
                       <?php echo _l('no_child_found',_l('invoices')); ?>
                     </div>
                     <?php } ?>
                   </div>
                   <div role="tabpanel" class="tab-pane" id="tab_emails_tracking">
                    <?php
                    $this->load->view('admin/includes/emails_tracking',array(
                      'tracked_emails'=>
                      get_tracked_emails($subscription->id, 'subscription'))
                  );
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>
  <?php if(isset($subscription)) { ?>
    <?php $this->load->view('admin/subscriptions/send_to_client'); ?>
    <?php } ?>
    <?php init_tail(); ?>
    <script>
     $(function(){
        // Project ajax search
        init_ajax_project_search_by_customer_id();
          appValidateForm('#subscriptionForm',{
           name:'required',
           clientid:'required',
           stripe_plan_id:'required',
           currency:'required',
           quantity: {
             required:true,
             min:1,
           }
         });

        <?php if(!isset($subscription) || (isset($subscription) && empty($subscription->stripe_subscription_id))) { ?>

            checkFirstBillingDate($('#stripe_plan_id').selectpicker('val'));

            $('#stripe_plan_id').on('change', function () {
                var selectedPlan = $(this).val();
                checkFirstBillingDate(selectedPlan);
                var selectedOption = $('#stripe_plan_id').find('option[value="'+selectedPlan+'"]');
                var interval = selectedOption.data('interval');
                var $firstBillingDate = $('#date');
                var firstBillingDate = $firstBillingDate.val();
                if(interval == 'month') {
                    var currentDate = moment().add(1, 'day').format('YYYY-MM-DD');
                    var futureMonth = moment(currentDate).add(selectedOption.data('interval-count'), 'M');
                    $firstBillingDate.attr('data-date-end-date', futureMonth.format('YYYY-MM-DD'));
                    $firstBillingDate.datetimepicker('destroy');
                    init_datepicker($firstBillingDate);
                }
            });
        <?php } ?>

        $('#subscriptionForm').on('dirty.areYouSure', function() {
          $('#prorateWrapper').removeClass('hide');
        });

        $('#subscriptionForm').on('clean.areYouSure', function() {
          $('#prorateWrapper').addClass('hide');
        });

      });
     function checkFirstBillingDate(selectedPlan) {
        if(selectedPlan == '') {
          return;
        }
        var interval = $('#stripe_plan_id').find('option[value="'+selectedPlan+'"]').data('interval');
        if(interval == 'week' || interval == 'day') {
          $('#first_billing_date_wrapper').addClass('hide');
          $('#date').val('');
        } else {
          $('#first_billing_date_wrapper').removeClass('hide');
        }
    }
    </script>
