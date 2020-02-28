<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12 no-padding animated fadeIn">
    <div class="panel_s">
        <?php echo form_open(admin_url('credit_notes/' . (isset($refund) ? 'edit_refund' : 'create_refund') .'/'. (isset($refund) ? $refund->id.'/'.$refund->credit_note_id : $credit_note->id)), ['id'=>'credit_note_refund_form']); ?>
        <div class="panel-body">
            <h4 class="no-margin"><?php echo _l('refund'); ?> (<?php echo format_credit_note_number($credit_note->id); ?>)</h4>
            <hr class="hr-panel-heading" />
            <div class="row">
                <div class="col-md-6">
                    <?php
                    echo render_input('amount', 'refund_amount', isset($refund) ? $refund->amount : $credit_note->remaining_credits, 'number', array('max'=>(!isset($refund) ? $credit_note->remaining_credits : $credit_note->remaining_credits + $refund->amount),'min'=>1)); ?>
                    <?php echo render_date_input('refunded_on', 'credit_date', isset($refund) ? _d($refund->refunded_on ) : _d(date('Y-m-d'))); ?>
                    <div class="form-group">
                        <label for="payment_mode" class="control-label"><?php echo _l('payment_mode'); ?></label>
                        <select class="selectpicker" name="payment_mode" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                            <option value=""></option>
                            <?php foreach($payment_modes as $mode){ ?>
                                <option value="<?php echo $mode['id']; ?>"<?php if(isset($refund) && $refund->payment_mode == $mode['id']){echo ' selected'; } ?>><?php echo $mode['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                   <div class="form-gruoup">
                    <label for="note" class="control-label"><?php echo _l('note'); ?></label>
                    <textarea name="note" class="form-control" rows="8" id="note"><?php if(isset($refund)) {echo clear_textarea_breaks($refund->note);} ?></textarea>
                </div>
            </div>
        </div>
        <div class="pull-right mtop15">
            <a href="#" class="btn btn-danger" onclick="init_credit_note(<?php echo $credit_note->id; ?>); return false;">
                <?php echo _l('cancel'); ?>
            </a>
            <button type="submit" autocomplete="off" data-loading-text="<?php echo _l('wait_text'); ?>" data-form="#credit_note_refund_form" class="btn btn-success">
                <?php echo _l('submit'); ?>
            </button>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>
</div>
<script>
   $(function(){
     init_selectpicker();
     init_datepicker();
     appValidateForm($('#credit_note_refund_form'),{amount:'required',refunded_on:'required'});
 });
</script>
