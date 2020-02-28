<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="consentModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo _l('gdpr_consent'); ?></h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="padding-10">
                <div class="col-md-12">
                    <h4 class="no-mtop">
                        <a href="<?php echo contact_consent_url($contact_id); ?>" target="_blank">
                         <small>
                            <?php echo _l('view_consent'); ?>
                        </small>
                    </a>
                </h4>
            </div>
            <?php $this->load->view('admin/gdpr/consent_user_info', array('form_url'=>'gdpr/contact_consent_opt_action')); ?>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
