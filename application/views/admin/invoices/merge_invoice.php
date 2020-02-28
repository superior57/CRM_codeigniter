<?php defined('BASEPATH') or exit('No direct script access allowed');
if(count($invoices_to_merge) > 0){ ?>
    <h4 class="bold mbot15 font-medium"><?php echo _l('invoices_available_for_merging'); ?></h4>
    <?php foreach($invoices_to_merge as $_inv){ ?>
        <div class="checkbox">
            <input type="checkbox" name="invoices_to_merge[]" value="<?php echo $_inv->id; ?>">
            <label for="">
                <a href="<?php echo admin_url('invoices/list_invoices/'.$_inv->id); ?>" data-toggle="tooltip" data-title="<?php echo format_invoice_status($_inv->status,'',false); ?>" target="_blank">
                    <?php echo format_invoice_number($_inv->id); ?>
                    </a> - <?php echo app_format_money($_inv->total, $_inv->currency_name); ?>
                </label>
            </div>
            <?php
                if($_inv->discount_total > 0){
                    echo '<b>'._l('invoices_merge_discount', app_format_money($_inv->discount_total, $_inv->currency_name)) . '</b><br />';
                }
            ?>
        <?php } ?>
        <p>
            <div class="checkbox checkbox-info">
                <input type="checkbox" checked name="cancel_merged_invoices" id="cancel_merged_invoices">
                <label for="cancel_merged_invoices"><i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('invoice_merge_number_warning'); ?>" data-placement="bottom"></i> <?php echo _l('invoices_merge_cancel_merged_invoices'); ?></label>
            </div>
        </p>
    <?php } ?>
