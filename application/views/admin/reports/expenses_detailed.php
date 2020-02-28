<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <a href="<?php echo admin_url('reports/expenses'); ?>" class="btn btn-default pull-left"><?php echo _l('go_back'); ?></a>
                        <?php $this->load->view('admin/expenses/filter_by_template'); ?>
                    </div>
                </div>
                <div class="panel_s">
                    <div class="panel-body">
                       <?php
                       $_currency = $base_currency;
                       if(is_using_multiple_currencies(db_prefix().'expenses')){ ?>
                       <div data-toggle="tooltip" class="mbot15 pull-left" title="<?php echo _l('report_expenses_base_currency_select_explanation'); ?>">
                        <select class="selectpicker" name="currencies" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" >
                            <?php foreach($currencies as $c) {
                                $selected = '';
                                if(!$this->input->get('currency')){
                                    if($c['id'] == $base_currency->id){
                                        $selected = 'selected';
                                        $_currency = $base_currency;
                                    }
                                } else {
                                    if($this->input->get('currency') == $c['id']){
                                        $selected = 'selected';
                                        $_currency = $this->currencies_model->get($c['id']);
                                    }
                                }
                                ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo $c['name']; ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php } ?>
                        <div class="clearfix"></div>
                        <div id="date-range" class="mbot15">
                            <div class="row">
                             <div class="col-md-6">
                              <label for="report-from" class="control-label"><?php echo _l('report_sales_from_date'); ?></label>
                              <div class="input-group date">
                               <input type="text" class="form-control datepicker" id="report-from" name="report-from">
                               <div class="input-group-addon">
                                <i class="fa fa-calendar calendar-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                      <label for="report-to" class="control-label"><?php echo _l('report_sales_to_date'); ?></label>
                      <div class="input-group date">
                       <input type="text" class="form-control datepicker" disabled="disabled" id="report-to" name="report-to">
                       <div class="input-group-addon">
                        <i class="fa fa-calendar calendar-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <table class="table dt-table-loading table-expenses">
       <thead>
           <tr>
               <th><?php echo _l('expense_dt_table_heading_category'); ?></th>
               <th><?php echo _l('expense_dt_table_heading_amount'); ?></th>
               <th><?php echo _l('expense_name'); ?></th>
               <th><?php echo _l('tax_1'); ?></th>
               <th><?php echo _l('tax_2'); ?></th>
               <th><?php echo _l('expenses_report_total_tax'); ?></th>
               <th><?php echo _l('report_invoice_amount_with_tax'); ?></th>
               <th><?php echo _l('expenses_list_billable'); ?></th>
               <th><?php echo _l('expense_dt_table_heading_date'); ?></th>
               <th><?php echo _l('expense_dt_table_heading_customer'); ?></th>
               <th><?php echo _l('invoice'); ?></th>
               <th><?php echo _l('expense_dt_table_heading_reference_no'); ?></th>
               <th><?php echo _l('expense_dt_table_heading_payment_mode'); ?></th>
           </tr>
       </thead>
       <tbody></tbody>
       <tfoot>
           <tr>
               <td></td>
               <td class="subtotal"></td>
               <td></td>
               <td class="tax_1"></td>
               <td class="tax_2"></td>
               <td class="total_tax"></td>
               <td class="total"></td>
               <td></td>
               <td></td>
               <td></td>
               <td></td>
               <td></td>
               <td></td>
           </tr>
       </tfoot>
   </table>
</div>
</div>
</div>
</div>
</div>
</div>
<?php init_tail(); ?>

<script>
    $(function(){

       var report_from = $('input[name="report-from"]');
       var report_to = $('input[name="report-to"]');
       var filter_selector_helper = '.expenses-filter-year,.expenses-filter-month-wrapper,.expenses-filter-month,.months-divider,.years-divider';

        var Expenses_ServerParams = {};
           $.each($('._hidden_inputs._filters input'),function(){
            Expenses_ServerParams[$(this).attr('name')] = '[name="'+$(this).attr('name')+'"]';
        });

       Expenses_ServerParams['currency'] = '[name="currencies"]';
       Expenses_ServerParams['report_from'] = '[name="report-from"]';
       Expenses_ServerParams['report_to'] = '[name="report-to"]';

       initDataTable('.table-expenses', window.location.href, 'undefined', 'undefined', Expenses_ServerParams, [8, 'desc']);

       report_from.on('change', function() {
         var val = $(this).val();
         var report_to_val = report_to.val();
         if (val != '') {
           report_to.attr('disabled', false);
           $(filter_selector_helper).removeClass('active').addClass('hide');
           $('input[name^="year_"]').val('');
           $('input[name^="expenses_by_month_"]').val('');
         } else {
            report_to.attr('disabled', true);
         }

         if ((report_to_val != '' && val != '') || (val == '' && report_to_val == '') || (val == '' && report_to_val != '')) {
             $('.table-expenses').DataTable().ajax.reload();
         }

         if(val == '' && report_to_val == '' || report_to.is(':disabled') && val == ''){
                $(filter_selector_helper).removeClass('hide');
         }
        });

        report_to.on('change', function() {
             var val = $(this).val();
             if (val != '') {
               $('.table-expenses').DataTable().ajax.reload();
           }
       });

       $('.table-expenses').on('draw.dt',function(){
        var expenseReportsTable = $(this).DataTable();
        var sums = expenseReportsTable.ajax.json().sums;
        $(this).find('tfoot').addClass('bold');
        $(this).find('tfoot td').eq(0).html("<?php echo _l('expenses_report_total'); ?>");
        $(this).find('tfoot td.tax_1').html(sums.tax_1);
        $(this).find('tfoot td.tax_2').html(sums.tax_2);
        $(this).find('tfoot td.subtotal').html(sums.amount);
        $(this).find('tfoot td.total_tax').html(sums.total_tax);
        $(this).find('tfoot td.total').html(sums.amount_with_tax);
    });

       $('select[name="currencies"]').on('change',function(){
        $('.table-expenses').DataTable().ajax.reload();
    });
   })

</script>
</body>
</html>
