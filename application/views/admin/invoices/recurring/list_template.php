<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12">
   <div class="panel_s mbot10">
      <div class="panel-body _buttons">
         <?php if (has_permission('invoices', '', 'create')) { ?>
         <a href="<?php echo admin_url('invoices/invoice'); ?>" class="btn btn-info pull-left new new-invoice-list">
         <?php echo _l('create_new_invoice'); ?>
         </a>
         <?php } ?>
         <a href="<?php echo admin_url('invoices'); ?>" class="btn btn-default pull-left mleft5">
            <?php echo _l('go_back'); ?>
         </a>
         <div class="display-block text-right">
            <div class="btn-group pull-right mleft4 invoice-view-buttons btn-with-tooltip-group _filter_data" data-toggle="tooltip" data-title="<?php echo _l('filter_by'); ?>">
               <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
               <i class="fa fa-filter" aria-hidden="true"></i>
               </button>
               <ul class="dropdown-menu width300">
               <li>
                  <a href="#" data-cview="all" onclick="dt_custom_view('','.table-invoices',''); return false;">
                  <?php echo _l('invoices_list_all'); ?>
                  </a>
               </li>
               <?php if (count($invoices_years) > 0) { ?>
               <li class="divider"></li>
               <?php foreach ($invoices_years as $year) { ?>
               <li class="active">
                  <a href="#" data-cview="year_<?php echo $year['year']; ?>" onclick="dt_custom_view(<?php echo $year['year']; ?>,'.table-invoices','year_<?php echo $year['year']; ?>'); return false;"><?php echo $year['year']; ?>
                  </a>
               </li>
               <?php } ?>
               <?php } ?>
               <?php if (count($invoices_sale_agents) > 0) { ?>
               <div class="clearfix"></div>
               <li class="divider"></li>
               <li class="dropdown-submenu pull-left">
                  <a href="#" tabindex="-1"><?php echo _l('sale_agent_string'); ?></a>
                  <ul class="dropdown-menu dropdown-menu-left">
                     <?php foreach ($invoices_sale_agents as $agent) { ?>
                     <li>
                        <a href="#" data-cview="sale_agent_<?php echo $agent['sale_agent']; ?>" onclick="dt_custom_view(<?php echo $agent['sale_agent']; ?>,'.table-invoices','sale_agent_<?php echo $agent['sale_agent']; ?>'); return false;"><?php echo $agent['full_name']; ?>
                        </a>
                     </li>
                     <?php } ?>
                  </ul>
               </li>
               <?php } ?>
            </div>
            <a href="#"
              class="btn btn-default btn-with-tooltip toggle-small-view hidden-xs"
              onclick="toggle_small_view('.table-invoices','#invoice'); return false;"
              data-toggle="tooltip" title="<?php echo _l('invoices_toggle_table_tooltip'); ?>">
              <i class="fa fa-angle-double-left"></i>
            </a>
         </div>
      </div>
   </div>
   <div class="row">
      <div class="col-md-12" id="small-table">
         <div class="panel_s">
            <div class="panel-body">
               <!-- if invoiceid found in url -->
               <?php echo form_hidden('invoiceid', $invoiceid); ?>
               <?php $this->load->view('admin/invoices/recurring/table_html'); ?>
            </div>
         </div>
      </div>
      <div class="col-md-7 small-table-right-col">
         <div id="invoice" class="hide">
         </div>
      </div>
   </div>
</div>
