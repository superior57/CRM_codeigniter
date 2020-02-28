<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Modal Zip Payments -->
<div class="modal fade" id="client_zip_payments" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open('admin/clients/zip_payments/'.$client->userid); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?php echo _l('client_zip_payments'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?php
                        array_unshift($payment_modes,array('id'=>'' ,'name'=>_l('client_zip_status_all')));
                        echo render_select('paymentmode', $payment_modes, array('id','name'), 'client_zip_payment_modes');
                        ?>
                        <div class="clearfix mbot15"></div>
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
