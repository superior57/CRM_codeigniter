<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Modal Zip Invoices -->
<div class="modal fade" id="client_zip_invoices" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open('admin/clients/zip_invoices/'.$client->userid); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?php echo _l('client_zip_invoices'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="invoice_zip_status"><?php echo _l('client_zip_status'); ?></label>
                            <div class="radio radio-primary">
                                <input type="radio" value="all" checked name="invoice_zip_status" id="all">
                                <label for="all"><?php echo _l('client_zip_status_all'); ?></label>
                            </div>
                            <?php foreach($invoice_statuses as $status){ ?>
                                 <div class="radio radio-primary">
                                <input type="radio" value="<?php echo $status; ?>" id="s_<?php echo $status; ?>" name="invoice_zip_status">
                                <label for="s_<?php echo $status; ?>"><?php echo format_invoice_status($status,'',false); ?></label>
                            </div>
                            <?php } ?>
                        </div>
                        <?php $this->load->view('admin/clients/modals/modal_zip_date_picker'); ?>
                        <?php echo form_hidden('file_name', $zip_in_folder); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
