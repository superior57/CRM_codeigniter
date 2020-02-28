<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h4 class="no-mtop">
    <a href="<?php echo lead_consent_url($lead->id); ?>" target="_blank">
     <small>
        <?php echo _l('view_consent'); ?>
    </small>
</a>
</h4>
<div class="row">
    <?php $this->load->view('admin/gdpr/consent_user_info', array('form_url'=>'gdpr/lead_consent_opt_action','lead_id'=>$lead->id)); ?>
</div>
