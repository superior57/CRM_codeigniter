<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <?php
            if(isset($expense)){
             echo form_hidden('is_edit','true');
            }
            ?>
         <?php echo form_open_multipart($this->uri->uri_string(),array('id'=>'expense-form','class'=>'dropzone dropzone-manual')) ;?>
         <div class="col-md-6">
            <div class="panel_s">
               <div class="panel-body">
                  <?php
                     if(isset($expense) && $expense->recurring_from != NULL){
                       $recurring_expense = $this->expenses_model->get($expense->recurring_from);
                       echo '<div class="alert alert-info">'._l('expense_recurring_from','<a href="'.admin_url('expenses/list_expenses/'.$expense->recurring_from).'" target="_blank">'.$recurring_expense->category_name.(!empty($recurring_expense->expense_name) ? ' ('.$recurring_expense->expense_name.')' : '').'</a></div>');
                     }
                     ?>
                  <h4 class="no-margin"><?php echo $title; ?></h4>
                  <hr class="hr-panel-heading" />
                  <?php if(isset($expense) && $expense->attachment !== ''){ ?>
                  <div class="row">
                     <div class="col-md-10">
                        <i class="<?php echo get_mime_class($expense->filetype); ?>"></i> <a href="<?php echo site_url('download/file/expense/'.$expense->expenseid); ?>"><?php echo $expense->attachment; ?></a>
                     </div>
                     <?php if($expense->attachment_added_from == get_staff_user_id() || is_admin()){ ?>
                     <div class="col-md-2 text-right">
                        <a href="<?php echo admin_url('expenses/delete_expense_attachment/'.$expense->expenseid); ?>" class="text-danger _delete"><i class="fa fa fa-times"></i></a>
                     </div>
                     <?php } ?>
                  </div>
                  <?php } ?>
                  <?php if(!isset($expense) || (isset($expense) && $expense->attachment == '')){ ?>
                  <div id="dropzoneDragArea" class="dz-default dz-message">
                     <span><?php echo _l('expense_add_edit_attach_receipt'); ?></span>
                  </div>
                  <div class="dropzone-previews"></div>
                  <?php } ?>
                  <hr class="hr-panel-heading" />

                  <?php hooks()->do_action('before_expense_form_name', isset($expense) ? $expense : null); ?>

                  <i class="fa fa-question-circle pull-left" data-toggle="tooltip" data-title="<?php echo _l('expense_name_help'); ?> - <?php echo _l('expense_field_billable_help',_l('expense_name')); ?>"></i>
                  <?php $value = (isset($expense) ? $expense->expense_name : ''); ?>
                  <?php echo render_input('expense_name','expense_name',$value); ?>
                  <i class="fa fa-question-circle pull-left" data-toggle="tooltip" data-title="<?php echo _l('expense_field_billable_help',_l('expense_add_edit_note')); ?>"></i>
                  <?php $value = (isset($expense) ? $expense->note : ''); ?>
                  <?php echo render_textarea('note','expense_add_edit_note',$value,array('rows'=>4),array()); ?>
                  <?php
                     $selected = (isset($expense) ? $expense->category : '');
                     if(is_admin() || get_option('staff_members_create_inline_expense_categories') == '1'){
                       echo render_select_with_input_group('category',$categories,array('id','name'),'expense_category',$selected,'<a href="#" onclick="new_category();return false;"><i class="fa fa-plus"></i></a>');
                     } else {
                        echo render_select('category',$categories,array('id','name'),'expense_category',$selected);
                     }
                     ?>
                  <?php $value = (isset($expense) ? _d($expense->date) : _d(date('Y-m-d')));
                     $date_attrs = array();
                     if(isset($expense) && $expense->recurring > 0 && $expense->last_recurring_date != null) {
                       $date_attrs['disabled'] = true;
                     }
                     ?>
                  <?php echo render_date_input('date','expense_add_edit_date',$value,$date_attrs);
                     $value = (isset($expense) ? $expense->amount : ''); ?>
                  <?php echo render_input('amount','expense_add_edit_amount',$value,'number');
                     $hide_billable_options = 'hide';

                     if((isset($expense) && ($expense->billable == 1 || $expense->clientid != 0)) || isset($customer_id)){
                          $hide_billable_options = '';
                     }
                     ?>
                  <div class="checkbox checkbox-primary billable <?php echo $hide_billable_options; ?>">
                     <input type="checkbox" id="billable" <?php if(isset($expense) && $expense->invoiceid !== NULL){echo 'disabled'; } ?> name="billable" <?php if(isset($expense)){if($expense->billable == 1){echo 'checked';}}; ?>>
                     <label for="billable" <?php if(isset($expense) && $expense->invoiceid !== NULL){echo 'data-toggle="tooltip" title="'._l('expense_already_invoiced').'"'; } ?>><?php echo _l('expense_add_edit_billable'); ?></label>
                  </div>
                  <div class="form-group select-placeholder">
                     <label for="clientid" class="control-label"><?php echo _l('expense_add_edit_customer'); ?></label>
                     <select id="clientid" name="clientid" data-live-search="true" data-width="100%" class="ajax-search" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                     <?php $selected = (isset($expense) ? $expense->clientid : '');
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
                  <?php $hide_project_selector = ' hide';
                     // Show selector only if expense is already added and there is no client linked to the expense or isset customer id
                     if((isset($expense) && $expense->clientid != 0) || isset($customer_id)){
                     $hide_project_selector = '';
                     }
                     ?>
                  <div class="form-group projects-wrapper<?php echo $hide_project_selector; ?>">
                     <label for="project_id"><?php echo _l('project'); ?></label>
                     <div id="project_ajax_search_wrapper">
                        <select name="project_id" id="project_id" class="projects ajax-search" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                        <?php if(isset($expense) && $expense->project_id != 0){
                           echo '<option value="'.$expense->project_id.'" selected>'.get_project_name_by_id($expense->project_id).'</option>';
                           }
                           ?>
                        </select>
                     </div>
                  </div>
                  <?php $rel_id = (isset($expense) ? $expense->expenseid : false); ?>
                  <?php echo render_custom_fields('expenses',$rel_id); ?>
                  <div class="btn-bottom-toolbar text-right">
                     <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-md-6">
            <div class="panel_s">
               <div class="panel-body">
                  <h4 class="no-margin"><?php echo _l('advanced_options'); ?></h4>
                  <hr class="hr-panel-heading" />
                  <?php
                     $currency_attr = array('disabled'=>true,'data-show-subtext'=>true);

                     $currency_attr = apply_filters_deprecated('expense_currency_disabled', [$currency_attr], '2.3.0', 'expense_currency_attributes');

                     foreach($currencies as $currency){
                      if($currency['isdefault'] == 1){
                        $currency_attr['data-base'] = $currency['id'];
                      }
                      if(isset($expense)){
                        if($currency['id'] == $expense->currency){
                          $selected = $currency['id'];
                        }
                        if($expense->billable == 0){
                          if($expense->clientid != 0){
                            $c = $this->clients_model->get_customer_default_currency($expense->clientid);
                            if($c != 0){
                              $customer_currency = $c;
                            }
                          }
                        }
                      } else {
                        if(isset($customer_id)){
                          $c = $this->clients_model->get_customer_default_currency($customer_id);
                          if($c != 0){
                            $customer_currency = $c;
                          }
                        }
                        if($currency['isdefault'] == 1){
                          $selected = $currency['id'];
                        }
                      }
                     }
                     $currency_attr = hooks()->apply_filters('expense_currency_attributes', $currency_attr);
                     ?>
                  <div id="expense_currency">
                     <?php echo render_select('currency', $currencies, array('id','name','symbol'), 'expense_currency', $selected, $currency_attr); ?>
                  </div>
                  <div class="row">
                     <div class="col-md-6">
                        <div class="form-group select-placeholder">
                           <label class="control-label" for="tax"><?php echo _l('tax_1'); ?></label>
                           <select class="selectpicker display-block" data-width="100%" name="tax" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                              <option value=""><?php echo _l('no_tax'); ?></option>
                              <?php foreach($taxes as $tax){
                                 $selected = '';
                                 if(isset($expense)){
                                   if($tax['id'] == $expense->tax){
                                     $selected = 'selected';
                                   }
                                 } ?>
                              <option
                                 value="<?php echo $tax['id']; ?>"
                                 <?php echo $selected; ?>
                                 data-percent="<?php echo $tax['taxrate']; ?>"
                                 data-subtext="<?php echo $tax['name']; ?>">
                                 <?php echo $tax['taxrate']; ?>%
                              </option>
                              <?php } ?>
                           </select>

                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="form-group select-placeholder">
                           <label class="control-label" for="tax2"><?php echo _l('tax_2'); ?></label>
                           <select class="selectpicker display-block" data-width="100%" name="tax2" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" <?php if(!isset($expense) || isset($expense) && $expense->tax == 0){echo 'disabled';} ?>>
                              <option value=""><?php echo _l('no_tax'); ?></option>
                              <?php foreach($taxes as $tax){
                                 $selected = '';
                                 if(isset($expense)){
                                   if($tax['id'] == $expense->tax2){
                                     $selected = 'selected';
                                   }
                                 } ?>
                              <option
                                 value="<?php echo $tax['id']; ?>"
                                 <?php echo $selected; ?>
                                 data-percent="<?php echo $tax['taxrate']; ?>"
                                 data-subtext="<?php echo $tax['name']; ?>">
                                 <?php echo $tax['taxrate']; ?>%
                              </option>
                              <?php } ?>
                           </select>
                        </div>
                     </div>
                     <?php if(!isset($expense)) { ?>
                      <div class="col-md-12 hide" id="tax_subtract">
                           <div class="info-block">
                           <div class="checkbox checkbox-primary no-margin">
                            <input type="checkbox" id="tax1_included">
                            <label for="tax1_included">
                              <?php echo _l('subtract_tax_total_from_amount','<span id="tax_subtract_total" class="bold"></span>'); ?>
                            </label>
                          </div>
                          <small class="text-muted">
                            <?php echo _l('expense_subtract_info_text'); ?>
                          </small>
                        </div>
                      </div>
                    <?php } ?>
                  </div>
                  <div class="clearfix mtop15"></div>
                  <div class="row">
                     <div class="col-md-6">
                        <?php $selected = (isset($expense) ? $expense->paymentmode : ''); ?>
                        <?php echo render_select('paymentmode',$payment_modes,array('id','name'),'payment_mode',$selected); ?>
                     </div>
                     <div class="col-md-6">
                        <?php $value = (isset($expense) ? $expense->reference_no : ''); ?>
                        <?php echo render_input('reference_no','expense_add_edit_reference_no',$value); ?>
                     </div>
                  </div>
                  <div class="form-group select-placeholder"<?php if(isset($expense) && !empty($expense->recurring_from)){ ?> data-toggle="tooltip" data-title="<?php echo _l('create_recurring_from_child_error_message', [_l('expense_lowercase'),_l('expense_lowercase'), _l('expense_lowercase')]); ?>"<?php } ?>>
                     <label for="repeat_every" class="control-label"><?php echo _l('expense_repeat_every'); ?></label>
                     <select
                     name="repeat_every"
                     id="repeat_every"
                     class="selectpicker"
                     data-width="100%"
                     data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>"
                     <?php if(isset($expense) && !empty($expense->recurring_from)){ ?> disabled <?php } ?>>
                        <option value=""></option>
                        <option value="1-week" <?php if(isset($expense) && $expense->repeat_every == 1 && $expense->recurring_type == 'week'){echo 'selected';} ?>><?php echo _l('week'); ?></option>
                        <option value="2-week" <?php if(isset($expense) && $expense->repeat_every == 2 && $expense->recurring_type == 'week'){echo 'selected';} ?>>2 <?php echo _l('weeks'); ?></option>
                        <option value="1-month" <?php if(isset($expense) && $expense->repeat_every == 1 && $expense->recurring_type == 'month'){echo 'selected';} ?>>1 <?php echo _l('month'); ?></option>
                        <option value="2-month" <?php if(isset($expense) && $expense->repeat_every == 2 && $expense->recurring_type == 'month'){echo 'selected';} ?>>2 <?php echo _l('months'); ?></option>
                        <option value="3-month" <?php if(isset($expense) && $expense->repeat_every == 3 && $expense->recurring_type == 'month'){echo 'selected';} ?>>3 <?php echo _l('months'); ?></option>
                        <option value="6-month" <?php if(isset($expense) && $expense->repeat_every == 6 && $expense->recurring_type == 'month'){echo 'selected';} ?>>6 <?php echo _l('months'); ?></option>
                        <option value="1-year" <?php if(isset($expense) && $expense->repeat_every == 1 && $expense->recurring_type == 'year'){echo 'selected';} ?>>1 <?php echo _l('year'); ?></option>
                        <option value="custom" <?php if(isset($expense) && $expense->custom_recurring == 1){echo 'selected';} ?>><?php echo _l('recurring_custom'); ?></option>
                     </select>
                  </div>
                  <div class="recurring_custom <?php if((isset($expense) && $expense->custom_recurring != 1) || (!isset($expense))){echo 'hide';} ?>">
                     <div class="row">
                        <div class="col-md-6">
                           <?php $value = (isset($expense) && $expense->custom_recurring == 1 ? $expense->repeat_every : 1); ?>
                           <?php echo render_input('repeat_every_custom','',$value,'number',array('min'=>1)); ?>
                        </div>
                        <div class="col-md-6">
                           <select name="repeat_type_custom" id="repeat_type_custom" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                              <option value="day" <?php if(isset($expense) && $expense->custom_recurring == 1 && $expense->recurring_type == 'day'){echo 'selected';} ?>><?php echo _l('expense_recurring_days'); ?></option>
                              <option value="week" <?php if(isset($expense) && $expense->custom_recurring == 1 && $expense->recurring_type == 'week'){echo 'selected';} ?>><?php echo _l('expense_recurring_weeks'); ?></option>
                              <option value="month" <?php if(isset($expense) && $expense->custom_recurring == 1 && $expense->recurring_type == 'month'){echo 'selected';} ?>><?php echo _l('expense_recurring_months'); ?></option>
                              <option value="year" <?php if(isset($expense) && $expense->custom_recurring == 1 && $expense->recurring_type == 'year'){echo 'selected';} ?>><?php echo _l('expense_recurring_years'); ?></option>
                           </select>
                        </div>
                     </div>
                  </div>
                  <div id="cycles_wrapper" class="<?php if(!isset($expense) || (isset($expense) && $expense->recurring == 0)){echo ' hide';}?>">
                     <?php $value = (isset($expense) ? $expense->cycles : 0); ?>
                     <div class="form-group recurring-cycles">
                        <label for="cycles"><?php echo _l('recurring_total_cycles'); ?>
                        <?php if(isset($expense) && $expense->total_cycles > 0){
                           echo '<small>' . _l('cycles_passed', $expense->total_cycles) . '</small>';
                           }
                           ?>
                        </label>
                        <div class="input-group">
                           <input type="number" class="form-control"<?php if($value == 0){echo ' disabled'; } ?> name="cycles" id="cycles" value="<?php echo $value; ?>" <?php if(isset($expense) && $expense->total_cycles > 0){echo 'min="'.($expense->total_cycles).'"';} ?>>
                           <div class="input-group-addon">
                              <div class="checkbox">
                                 <input type="checkbox"<?php if($value == 0){echo ' checked';} ?> id="unlimited_cycles">
                                 <label for="unlimited_cycles"><?php echo _l('cycles_infinity'); ?></label>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div>
                     <?php
                        $hide_invoice_recurring_options = 'hide';
                        if(isset($expense) && $expense->billable == 1) {
                          $hide_invoice_recurring_options = '';
                        }
                        ?>
                     <div class="checkbox checkbox-primary billable_recurring_options <?php echo $hide_invoice_recurring_options; ?>">
                        <input type="checkbox" id="create_invoice_billable" name="create_invoice_billable" <?php if(isset($expense)){if($expense->create_invoice_billable == 1){echo 'checked';}}; ?>>
                        <label for="create_invoice_billable"><i class="fa fa-question-circle" data-toggle="tooltip" title="<?php echo _l('expense_recurring_autocreate_invoice_tooltip'); ?>"></i> <?php echo _l('expense_recurring_auto_create_invoice'); ?></label>
                     </div>
                  </div>
                  <div class="checkbox checkbox-primary billable_recurring_options <?php echo $hide_invoice_recurring_options; ?>">
                     <input type="checkbox" name="send_invoice_to_customer" id="send_invoice_to_customer" <?php if(isset($expense)){if($expense->send_invoice_to_customer == 1){echo 'checked';}}; ?>>
                     <label for="send_invoice_to_customer"><?php echo _l('expense_recurring_send_custom_on_renew'); ?></label>
                  </div>
               </div>
            </div>
         </div>
         <?php echo form_close(); ?>
      </div>
      <div class="btn-bottom-pusher"></div>
   </div>
</div>
<?php $this->load->view('admin/expenses/expense_category'); ?>
<?php init_tail(); ?>
<script>
   var customer_currency = '';
   Dropzone.options.expenseForm = false;
   var expenseDropzone;
   init_ajax_project_search_by_customer_id();
   var selectCurrency = $('select[name="currency"]');
   <?php if(isset($customer_currency)){ ?>
     var customer_currency = '<?php echo $customer_currency; ?>';
   <?php } ?>
     $(function(){
        $('body').on('change','#project_id', function(){
          var project_id = $(this).val();
          if(project_id != '') {
           if (customer_currency != 0) {
             selectCurrency.val(customer_currency);
             selectCurrency.selectpicker('refresh');
           } else {
             set_base_currency();
           }
         } else {
          do_billable_checkbox();
        }
      });

     if($('#dropzoneDragArea').length > 0){
        expenseDropzone = new Dropzone("#expense-form", appCreateDropzoneOptions({
          autoProcessQueue: false,
          clickable: '#dropzoneDragArea',
          previewsContainer: '.dropzone-previews',
          addRemoveLinks: true,
          maxFiles: 1,
          success:function(file,response){
           response = JSON.parse(response);
           if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
             window.location.assign(response.url);
           }
         },
       }));
     }

     appValidateForm($('#expense-form'),{
      category:'required',
      date:'required',
      amount:'required',
      currency:'required',
      repeat_every_custom: { min: 1},
    },expenseSubmitHandler);

     $('input[name="billable"]').on('change',function(){
       do_billable_checkbox();
     });

      $('#repeat_every').on('change',function(){
         if($(this).selectpicker('val') != '' && $('input[name="billable"]').prop('checked') == true){
            $('.billable_recurring_options').removeClass('hide');
          } else {
            $('.billable_recurring_options').addClass('hide');
          }
     });

     // hide invoice recurring options on page load
     $('#repeat_every').trigger('change');

      $('select[name="clientid"]').on('change',function(){
       customer_init();
       do_billable_checkbox();
       $('input[name="billable"]').trigger('change');
     });

     <?php if(!isset($expense)) { ?>
        $('select[name="tax"], select[name="tax2"]').on('change', function () {

            delay(function(){
                var $amount = $('#amount'),
                taxDropdown1 = $('select[name="tax"]'),
                taxDropdown2 = $('select[name="tax2"]'),
                taxPercent1 = parseFloat(taxDropdown1.find('option[value="'+taxDropdown1.val()+'"]').attr('data-percent')),
                taxPercent2 = parseFloat(taxDropdown2.find('option[value="'+taxDropdown2.val()+'"]').attr('data-percent')),
                total = $amount.val();

                if(total == 0 || total == '') {
                    return;
                }

                if($amount.attr('data-original-amount')) {
                  total = $amount.attr('data-original-amount');
                }

                total = parseFloat(total);

                if(taxDropdown1.val() || taxDropdown2.val()) {

                    $('#tax_subtract').removeClass('hide');

                    var totalTaxPercentExclude = taxPercent1;
                    if(taxDropdown2.val()){
                      totalTaxPercentExclude += taxPercent2;
                    }

                    var totalExclude = accounting.toFixed(total - exclude_tax_from_amount(totalTaxPercentExclude, total), app.options.decimal_places);
                    $('#tax_subtract_total').html(accounting.toFixed(totalExclude, app.options.decimal_places));
                } else {
                   $('#tax_subtract').addClass('hide');
                }
                if($('#tax1_included').prop('checked') == true) {
                    subtract_tax_amount_from_expense_total();
                }
              }, 200);
        });

        $('#amount').on('blur', function(){
          $(this).removeAttr('data-original-amount');
          if($(this).val() == '' || $(this).val() == '') {
              $('#tax1_included').prop('checked', false);
              $('#tax_subtract').addClass('hide');
          } else {
            var tax1 = $('select[name="tax"]').val();
            var tax2 = $('select[name="tax2"]').val();
            if(tax1 || tax2) {
                setTimeout(function(){
                    $('select[name="tax2"]').trigger('change');
                }, 100);
            }
          }
        })

        $('#tax1_included').on('change', function() {

          var $amount = $('#amount'),
          total = parseFloat($amount.val());

          // da pokazuva total za 2 taxes  Subtract TAX total (136.36) from expense amount
          if(total == 0) {
              return;
          }

          if($(this).prop('checked') == false) {
              $amount.val($amount.attr('data-original-amount'));
              return;
          }

          subtract_tax_amount_from_expense_total();
        });
      <?php } ?>
    });

    function subtract_tax_amount_from_expense_total(){
         var $amount = $('#amount'),
         total = parseFloat($amount.val()),
         taxDropdown1 = $('select[name="tax"]'),
         taxDropdown2 = $('select[name="tax2"]'),
         taxRate1 = parseFloat(taxDropdown1.find('option[value="'+taxDropdown1.val()+'"]').attr('data-percent')),
         taxRate2 = parseFloat(taxDropdown2.find('option[value="'+taxDropdown2.val()+'"]').attr('data-percent'));

         var totalTaxPercentExclude = taxRate1;
         if(taxRate2) {
          totalTaxPercentExclude+= taxRate2;
        }

        if($amount.attr('data-original-amount')) {
          total = parseFloat($amount.attr('data-original-amount'));
        }

        $amount.val(exclude_tax_from_amount(totalTaxPercentExclude, total));

        if($amount.attr('data-original-amount') == undefined) {
          $amount.attr('data-original-amount', total);
        }
    }

    function customer_init(){
        var customer_id = $('select[name="clientid"]').val();
        var projectAjax = $('select[name="project_id"]');
        var clonedProjectsAjaxSearchSelect = projectAjax.html('').clone();
        var projectsWrapper = $('.projects-wrapper');
        projectAjax.selectpicker('destroy').remove();
        projectAjax = clonedProjectsAjaxSearchSelect;
        $('#project_ajax_search_wrapper').append(clonedProjectsAjaxSearchSelect);
        init_ajax_project_search_by_customer_id();
        if(!customer_id){
           set_base_currency();
           projectsWrapper.addClass('hide');
         }
       $.get(admin_url + 'expenses/get_customer_change_data/'+customer_id,function(response){
         if(customer_id && response.customer_has_projects){
           projectsWrapper.removeClass('hide');
         } else {
           projectsWrapper.addClass('hide');
         }
         var client_currency = parseInt(response.client_currency);
         if (client_currency != 0) {
           customer_currency = client_currency;
           do_billable_checkbox();
         } else {
           customer_currency = '';
           set_base_currency();
         }
       },'json');
     }
     function expenseSubmitHandler(form){

      selectCurrency.prop('disabled',false);

      $('select[name="tax2"]').prop('disabled',false);
      $('input[name="billable"]').prop('disabled',false);
      $('input[name="date"]').prop('disabled',false);

      $.post(form.action, $(form).serialize()).done(function(response) {
        response = JSON.parse(response);
        if (response.expenseid) {
         if(typeof(expenseDropzone) !== 'undefined'){
          if (expenseDropzone.getQueuedFiles().length > 0) {
            expenseDropzone.options.url = admin_url + 'expenses/add_expense_attachment/' + response.expenseid;
            expenseDropzone.processQueue();
          } else {
            window.location.assign(response.url);
          }
        } else {
          window.location.assign(response.url);
        }
      } else {
        window.location.assign(response.url);
      }
    });
      return false;
    }
    function do_billable_checkbox(){
      var val = $('select[name="clientid"]').val();
      if(val != ''){
        $('.billable').removeClass('hide');
        if ($('input[name="billable"]').prop('checked') == true) {
          if($('#repeat_every').selectpicker('val') != ''){
            $('.billable_recurring_options').removeClass('hide');
          } else {
            $('.billable_recurring_options').addClass('hide');
          }
          if(customer_currency != ''){
            selectCurrency.val(customer_currency);
            selectCurrency.selectpicker('refresh');
          } else {
            set_base_currency();
         }
       } else {
        $('.billable_recurring_options').addClass('hide');
        // When project is selected, the project currency will be used, either customer currency or base currency
        if($('#project_id').selectpicker('val') == ''){
            set_base_currency();
        }
      }
    } else {
      set_base_currency();
      $('.billable').addClass('hide');
      $('.billable_recurring_options').addClass('hide');
    }
   }
   function set_base_currency(){
    selectCurrency.val(selectCurrency.data('base'));
    selectCurrency.selectpicker('refresh');
   }
</script>
</body>
</html>
