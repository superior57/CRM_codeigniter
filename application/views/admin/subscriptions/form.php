<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if(isset($subscription_error)) { ?>
<div class="alert alert-warning">
   <?php echo $subscription_error; ?>
</div>
<?php } ?>
<?php echo form_open('', array('id'=>'subscriptionForm','class'=>'_transaction_form')); ?>
<div class="row">
   <div class="col-md-12">
         <div class="bg-stripe mbot15">
         <div class="form-group select-placeholder">
         <label for="stripe_plan_id"><?php echo _l('billing_plan'); ?></label>
         <select id="stripe_plan_id"
          name="stripe_plan_id"
          class="selectpicker"
          data-live-search="true"
          data-width="100%"
          data-none-selected-text="<?php echo _l('stripe_subscription_select_plan'); ?>">
            <option value=""></option>
            <?php if(isset($plans->data)){ ?>
            <?php foreach($plans->data as $plan) {
               $selected = '';
               if(isset($subscription) && $subscription->stripe_plan_id == $plan->id) {
                 $selected = ' selected';
               }
               $subtext = app_format_money(strcasecmp($plan->currency, 'JPY') == 0 ? $plan->amount : $plan->amount / 100, strtoupper($plan->currency));
               if($plan->interval_count == 1) {
                  $subtext .= ' / ' . $plan->interval;
               } else {
                  $subtext .= ' (every '.$plan->interval_count.' '.$plan->interval.'s)';
               }
               ?>
            <option value="<?php echo $plan->id; ?>" data-interval-count="<?php echo $plan->interval_count; ?>" data-interval="<?php echo $plan->interval; ?>" data-amount="<?php echo $plan->amount; ?>" data-subtext="<?php echo $subtext; ?>"<?php echo $selected; ?>>
               <?php
                  if(empty($plan->nickname)) {
                    echo '[Plan Name Not Set in Stripe, ID:'.$plan->id.']';
                  } else {
                    echo $plan->nickname;
                  }
                  ?>
            </option>
            <?php } ?>
            <?php } ?>
         </select>
      </div>
      <?php echo render_input('quantity', _l('item_quantity_placeholder'), isset($subscription) ? $subscription->quantity : 1, 'number'); ?>
      <?php
        $params = array('data-lazy'=>'false', 'data-date-min-date' => date('Y-m-d', strtotime('+1 days', strtotime(date('Y-m-d')))));
        if(isset($subscription) && !empty($subscription->stripe_subscription_id)){
            $params['disabled'] = true;
        }
       echo '<div id="first_billing_date_wrapper">';
        if(!isset($params['disabled'])){
          echo '<i class="fa fa-question-circle pull-left" data-toggle="tooltip" data-placement="right" data-title="'._l('subscription_first_billing_date_info').'"></i>';
        }
        echo render_date_input('date', 'first_billing_date', isset($subscription) ? _d($subscription->date) : '', $params);
        echo '</div>';
        if(isset($subscription) && !empty($subscription->stripe_subscription_id) && $subscription->status != 'canceled' && $subscription->status != 'future') { ?>
           <div class="checkbox checkbox-info hide" id="prorateWrapper">
                <input type="checkbox" id="prorate" class="ays-ignore" checked name="prorate">
                <label for="prorate"><a href="https://stripe.com/docs/billing/subscriptions/prorations" target="_blank"><i class="fa fa-link"></i></a> Prorate</label>
            </div>
        <?php } ?>
     </div>
      <?php $value = (isset($subscription) ? $subscription->name : ''); ?>
      <?php echo render_input('name','subscription_name',$value,'text',[],[],'','ays-ignore'); ?>
      <?php $value = (isset($subscription) ? $subscription->description : ''); ?>
      <?php echo render_textarea('description','subscriptions_description',$value,[],[],'','ays-ignore'); ?>
       <div class="form-group">
        <div class="checkbox checkbox-primary">
          <input type="checkbox" id="description_in_item" class="ays-ignore" name="description_in_item"<?php if(isset($subscription) && $subscription->description_in_item == '1'){echo ' checked';} ?>>
          <label for="description_in_item"><i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('description_in_invoice_item_help'); ?>"></i> <?php echo _l('description_in_invoice_item'); ?></label>
        </div>
       </div>
      <div class="form-group select-placeholder f_client_id">
         <label for="clientid" class="control-label"><?php echo _l('client'); ?></label>
         <select id="clientid" name="clientid" data-live-search="true" data-width="100%" class="ajax-search ays-ignore" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>"<?php if(isset($subscription) && !empty($subscription->stripe_subscription_id)){echo ' disabled'; } ?>>
         <?php $selected = (isset($subscription) ? $subscription->clientid : '');
            if($selected == ''){
              $selected = (isset($customer_id) ? $customer_id: '');
            }
            if($selected != ''){
             $rel_data = get_relation_data('customer',$selected);
             $rel_val = get_relation_values($rel_data,'customer');
             echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
            } ?>
         </select>
      </div>
        <div class="form-group select-placeholder projects-wrapper<?php if((!isset($subscription)) || (isset($subscription) && !customer_has_projects($subscription->clientid))){ echo ' hide';} ?>">
               <label for="project_id"><?php echo _l('project'); ?></label>
              <div id="project_ajax_search_wrapper">
                   <select name="project_id" id="project_id" class="projects ajax-search ays-ignore" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                   <?php
                     if(isset($subscription) && $subscription->project_id != 0){
                        echo '<option value="'.$subscription->project_id.'" selected>'.get_project_name_by_id($subscription->project_id).'</option>';
                     }
                   ?>
               </select>
               </div>
            </div>
      <?php
         $s_attrs = array('disabled'=>true, 'data-show-subtext'=>true);
         foreach($currencies as $currency){
          if($currency['isdefault'] == 1){
           $s_attrs['data-base'] = $currency['id'];
         }
         if(isset($subscription)){
          if($currency['id'] == $subscription->currency){
           $selected = $currency['id'];
         }
         } else {
           if($currency['isdefault'] == 1){
             $selected = $currency['id'];
           }
         }
         }
         ?>
      <?php if(isset($subscription) && isset($stripeSubscription)) { ?>
      <?php
      if(strtolower($subscription->currency_name) != strtolower($stripeSubscription->plan->currency)) {  ?>
        <div class="alert alert-warning">
           <?php echo _l('subscription_plan_currency_does_not_match'); ?>
        </div>
      <?php } ?>
      <?php } ?>
      <?php echo render_select('currency', $currencies, array('id', 'name', 'symbol'), 'currency', $selected,  $s_attrs, [], '', 'ays-ignore'); ?>
      <div class="form-group select-placeholder">
         <label class="control-label" for="tax"><?php echo _l('tax'); ?> (Stripe)</label>
         <select class="selectpicker" data-width="100%" name="stripe_tax_id" data-none-selected-text="<?php echo _l('no_tax'); ?>">
            <option value=""></option>
            <?php foreach($stripe_tax_rates->data as $tax){
                if($tax->inclusive) {
                  continue;
                }
             ?>
            <option value="<?php echo $tax->id; ?>" data-subtext="<?php echo $tax->display_name; ?>"<?php if(isset($subscription) && $subscription->stripe_tax_id == $tax->id){echo ' selected';} ?>><?php echo $tax->percentage; ?>%</option>
            <?php } ?>
         </select>
      </div>
     <?php echo render_textarea('terms', 'terms_and_conditions', $value, [ 'placeholder'=> _l('subscriptions_terms_info') ], [], '','ays-ignore'); ?>
   </div>
</div>
<?php if((isset($subscription) && has_permission('subscriptions','','edit')) || !isset($subscription)){ ?>
<div class="btn-bottom-toolbar text-right">
   <button type="submit" class="btn btn-info" data-loading-text="<?php echo _l('wait_text'); ?>" data-form="#subscriptionForm">
   <?php echo _l('save'); ?>
   </button>
</div>
<?php } ?>
<?php echo form_close(); ?>
