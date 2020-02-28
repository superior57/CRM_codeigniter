<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade email-template" data-editor-id=".<?php echo 'tinymce-'.$subscription->id; ?>" id="subscription_send_to_client_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <?php echo form_open('admin/subscriptions/send_to_email/'.$subscription->id); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <?php echo _l('send_subscription'); ?>
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
                            $contact = get_primary_contact_user_id($subscription->clientid);
                            if(!$contact) {
                               echo '<p class="text-danger">The system is unable to find primary contact for this customer. Make sure that this customer have active primary contact.</p><hr />';
                           } else { ?>
                           <div class="bg-stripe bold text-center mbot15">
                            <?php echo _l('subscription_will_send_to_primary_contact'); ?>
                        </div>
                        <?php } ?>
                    </div>
                    <?php echo render_input('cc','CC'); ?>
                    <hr />
                    <h5 class="bold"><?php echo _l('invoice_send_to_client_preview_template'); ?></h5>
                    <hr />
                    <?php echo render_textarea('email_template_custom','',$template->message,array(),array(),'','tinymce-'.$subscription->id); ?>
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
