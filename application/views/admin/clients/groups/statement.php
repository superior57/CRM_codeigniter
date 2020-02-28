<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h4 class="customer-profile-group-heading"><?php echo _l('customer_statement'); ?></h4>
<div class="row">
 <div class="col-md-4">
   <?php $this->load->view('admin/clients/groups/_statement_period_select', ['onChange'=>'render_customer_statement()']); ?>
</div>
<div class="col-md-8 col-xs-12">
   <div class="text-right _buttons pull-right">

      <a href="#" id="statement_print" target="_blank" class="btn btn-default btn-with-tooltip mright5" data-toggle="tooltip" title="<?php echo _l('print'); ?>" data-placement="bottom">
          <i class="fa fa-print"></i>
      </a>

      <a href="" id="statement_pdf"  class="btn btn-default btn-with-tooltip mright5" data-toggle="tooltip" title="<?php echo _l('view_pdf'); ?>" data-placement="bottom">
          <i class="fa fa-file-pdf-o"></i>
      </a>

      <a href="#" class="btn-with-tooltip btn btn-default" data-toggle="modal" data-target="#statement_send_to_client"><span data-toggle="tooltip" data-title="<?php echo _l('send_to_email'); ?>" data-placement="bottom"><i class="fa fa-envelope"></i></span></a>
</div>
</div>
<div class="clearfix"></div>
<div class="col-md-12">
    <h4><?php echo _l('customer_statement_for',get_company_name($client->userid)); ?></h4>
</div>
<div class="clearfix"></div>

<div class="col-md-12 mtop15">
    <div class="panel_s">
        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                   <address class="text-right">
                       <?php echo format_organization_info(); ?>
                   </address>
               </div>
               <div class="col-md-12">
                   <hr />
               </div>
               <div class="col-md-7">
                   <address>
                    <p><?php echo _l('statement_bill_to'); ?>:</p>
                    <?php echo format_customer_info($client, 'statement', 'billing'); ?>
                 </address>
             </div>
             <div id="statement-html"></div>
         </div>
     </div>
 </div>
</div>
</div>
<div class="modal fade email-template" data-editor-id=".<?php echo 'tinymce-'.$client->userid; ?>" id="statement_send_to_client" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <?php echo form_open('',array('id'=>'send_statement_form')); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <?php echo _l('account_summary'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <?php
                            if($template_disabled){
                                echo '<div class="alert alert-danger">';
                                echo 'The email template <b><a href="'.admin_url('emails/email_template/'.$template_id).'" target="_blank">'.$template_system_name.'</a></b> is disabled. Click <a href="'.admin_url('emails/email_template/'.$template_id).'" target="_blank">here</a> to enable the email template in order to be sent successfully.';
                                echo '</div>';
                            }
                            $selected = array();
                            foreach($contacts as $contact){
                                if(has_contact_permission('invoices',$contact['id'])){
                                    array_push($selected,$contact['id']);
                                }
                            }
                            if(count($selected) == 0){
                                echo '<p class="text-danger">' . _l('sending_email_contact_permissions_warning',_l('customer_permission_invoice')) . '</p><hr />';
                            }
                            echo render_select('send_to[]',$contacts,array('id','email','firstname,lastname'),'invoice_estimate_sent_to_email',$selected,array('multiple'=>true),array(),'','',false);
                            ?>
                        </div>
                        <?php echo render_input('cc','CC'); ?>
                        <hr />
                        <h5 class="bold"><?php echo _l('invoice_send_to_client_preview_template'); ?></h5>
                        <hr />
                        <?php echo render_textarea('email_template_custom','',$template->message,array(),array(),'','tinymce-'.$client->userid); ?>
                        <?php echo form_hidden('template_name',$template_name); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" autocomplete="off" data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-info"><?php echo _l('send'); ?></button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>
<?php hooks()->add_action('app_admin_footer','parse_customer_statement_html');
function parse_customer_statement_html(){ ?>
<script>
    $(function(){
       render_customer_statement();
   });
    function render_customer_statement(){
     var $statementPeriod = $('#range');
     var value = $statementPeriod.selectpicker('val');
     var period = new Array();
     if(value != 'period'){
        period = JSON.parse(value);
    } else {
        period[0] = $('input[name="period-from"]').val();
        period[1] = $('input[name="period-to"]').val();

        if(period[0] == '' || period[1] == ''){
            return false;
        }
    }

    var statementUrl = admin_url+'clients/statement';
    var statementUrlParams = new Array();

    statementUrlParams['customer_id'] = customer_id;
    statementUrlParams['from'] = period[0];
    statementUrlParams['to'] = period[1];
    statementUrl = buildUrl(statementUrl,statementUrlParams);

    $.get(statementUrl,function(response){
        $('#statement-html').html(response.html);

        $('#statement_pdf').attr('href',buildUrl(admin_url+'clients/statement_pdf',statementUrlParams));
        $('#send_statement_form').attr('action',buildUrl(admin_url+'clients/send_statement',statementUrlParams));

        statementUrlParams['print'] = true;
        $('#statement_print').attr('href',buildUrl(admin_url+'clients/statement_pdf',statementUrlParams));
    },'json').fail(function(response){
        alert_float('danger',response.responseText);
    });
}
</script>
<?php } ?>
