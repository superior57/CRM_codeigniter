<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div id="expenses_total"></div>
<hr />
<?php if(has_permission('expenses','','create')){ ?>
<a href="#" data-toggle="modal" data-target="#new_project_expense" class="btn btn-info mbot25"><?php echo _l('new_expense'); ?></a>
<?php } ?>
<?php
   $data_expenses_filter['total_unbilled'] = $this->db->query('SELECT count(*) as num_rows FROM '.db_prefix().'expenses WHERE (SELECT 1 from '.db_prefix().'invoices WHERE '.db_prefix().'invoices.id = '.db_prefix().'expenses.invoiceid AND status != 2)')->row()->num_rows;
   $data_expenses_filter['categories'] = $expense_categories;
   $data_expenses_filter['filter_table_name'] = '.table-project-expenses';
   $data_expenses_filter['years'] = $this->expenses_model->get_expenses_years();
   $this->load->view('admin/expenses/filter_by_template',$data_expenses_filter); ?>
<div class="clearfix"></div>
<?php
   echo form_hidden('custom_view');
   $this->load->view('admin/expenses/table_html', array('class'=>'project-expenses'));
   ?>
<div class="modal fade" id="new_project_expense" tabindex="-1" role="dialog">
   <div class="modal-dialog">
      <div class="modal-content">
         <?php echo form_open(admin_url('projects/add_expense'),array('id'=>'project-expense-form','class'=>'dropzone dropzone-manual')); ?>
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><?php echo _l('add_new', _l('expense_lowercase')); ?></h4>
         </div>
         <div class="modal-body">
            <div id="dropzoneDragArea" class="dz-default dz-message">
               <span><?php echo _l('expense_add_edit_attach_receipt'); ?></span>
            </div>
            <div class="dropzone-previews"></div>
            <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('expense_name_help'); ?>"></i>
            <?php echo render_input('expense_name','expense_name'); ?>
            <?php echo render_textarea('note','expense_add_edit_note','',array('rows'=>4),array()); ?>
            <?php echo render_select('category',$expense_categories,array('id','name'),'expense_category'); ?>
            <?php echo render_date_input('date','expense_add_edit_date',_d(date('Y-m-d'))); ?>
            <?php echo render_input('amount','expense_add_edit_amount','','number'); ?>
            <div class="row mbot15">
               <div class="col-md-6">
                  <div class="form-group">
                     <label class="control-label" for="tax"><?php echo _l('tax_1'); ?></label>
                     <select class="selectpicker display-block" data-width="100%" name="tax" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                        <option value=""><?php echo _l('no_tax'); ?></option>
                        <?php foreach($taxes as $tax){ ?>
                        <option value="<?php echo $tax['id']; ?>" data-subtext="<?php echo $tax['name']; ?>"><?php echo $tax['taxrate']; ?>%</option>
                        <?php } ?>
                     </select>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="form-group">
                     <label class="control-label" for="tax2"><?php echo _l('tax_2'); ?></label>
                     <select class="selectpicker display-block" data-width="100%" name="tax2" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" disabled>
                        <option value=""><?php echo _l('no_tax'); ?></option>
                        <?php foreach($taxes as $tax){ ?>
                        <option value="<?php echo $tax['id']; ?>" data-subtext="<?php echo $tax['name']; ?>"><?php echo $tax['taxrate']; ?>%</option>
                        <?php } ?>
                     </select>
                  </div>
               </div>
            </div>
            <div class="hide">
               <?php echo render_select('currency',$currencies,array('id','name','symbol'),'expense_currency',$currency->id); ?>
            </div>
            <?php
               $customer_expense_data = array();
               $_customer_expense_data = array();
               $_customer_expense_data['userid'] = $project->client_data->userid;
               $_customer_expense_data['company'] = $project->client_data->company;
               $customer_expense_data[] = $_customer_expense_data;
               echo render_select('clientid',$customer_expense_data,array('userid','company'),'expense_add_edit_customer',$project->clientid); ?>
            <div class="checkbox checkbox-primary">
               <input type="checkbox" id="billable" name="billable" checked>
               <label for="billable"><?php echo _l('expense_add_edit_billable'); ?></label>
            </div>
            <?php echo render_input('reference_no','expense_add_edit_reference_no'); ?>
            <?php $selected = (isset($expense) ? $expense->paymentmode : ''); ?>
            <?php
               // Fix becuase payment modes are used for invoice filtering and there needs to be shown all
               // in case there is payment made with payment mode that was active and now is inactive
               $expenses_modes = array();
               foreach($payment_modes as $m){
               if(isset($m['invoices_only']) && $m['invoices_only'] == 1) {continue;}
               if($m['active'] == 1){
               $expenses_modes[] = $m;
               }
               }
               ?>
            <?php echo render_select('paymentmode',$expenses_modes,array('id','name'),'payment_mode',$selected); ?>
            <div class="clearfix mbot15"></div>
            <?php echo render_custom_fields('expenses'); ?>
            <?php echo form_hidden('project_id',$project->id); ?>
            <?php echo form_hidden('clientid',$project->clientid); ?>
            <div class="clearfix"></div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
         </div>
         <?php echo form_close(); ?>
      </div>
      <!-- /.modal-content -->
   </div>
   <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
