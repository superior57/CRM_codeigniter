<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
   <div class="col-md-12">
      <?php echo render_input('settings[purchase_key]', 'purchase_key', get_option('purchase_key'), 'text', array('data-ays-ignore'=>true)); ?>
   </div>
   <div class="col-md-6 text-center">
      <div class="alert alert-<?php if($latest_version > $current_version){echo 'danger';}else {echo 'info';} ?>">
         <h4 class="bold"><?php echo _l('your_version'); ?></h4>
         <p class="font-medium bold"><?php echo wordwrap($current_version,1,'.',true); ?></p>
      </div>
   </div>
   <div class="col-md-6 text-center">
      <div class="alert alert-<?php if($latest_version > $current_version){echo 'success';} else if($latest_version == $current_version){echo 'info';} ?>">
         <h4 class="bold"><?php echo _l('latest_version'); ?></h4>
         <p class="font-medium bold"><?php echo wordwrap($latest_version,1,'.',true); ?></p>
         <?php echo form_hidden('latest_version',$latest_version); ?>
      </div>
   </div>
   <div class="clearfix"></div>
   <hr />
   <div class="col-md-12 text-center">
      <?php if($current_version != $latest_version && $latest_version > $current_version){ ?>
         <div class="alert alert-warning">
            Before performing an update, it is <b>strongly recommended to create a full backup</b> of your current installation <b>(files and database)</b> and review the changelog.
         </div>
         <h3 class="bold text-center mbot20"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> <?php echo _l('update_available'); ?></h3>
         <div class="update_app_wrapper" data-wait-text="<?php echo _l('wait_text'); ?>" data-original-text="<?php echo _l('update_now'); ?>">
          <?php if(count($update_errors) == 0){ ?>
            <p class="bold mbot5">Upgrade Function</p>
            <div class="radio radio-primary radio-inline">
              <input type="radio" value="new" data-ays-ignore="true" checked id="new_upgrade_function" name="upgrade_function">
              <label for="new_upgrade_function">New (from v2.3.2)</label>
           </div>
           <div class="radio radio-primary radio-inline">
             <input type="radio" value="old" data-ays-ignore="true" id="old_upgrade_function" name="upgrade_function">
             <label for="old_upgrade_function">Old (prior to v2.3.2)</label>
          </div>
          <br />
          <br />
          <a href="#" id="update_app" class="btn btn-success">Download files</a>
       <?php } ?>
    </div>
    <div id="update_messages" class="mtop25 text-left"></div>
 <?php } else { ?>
   <h3 class="bold mbot20 text-success"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> <?php echo _l('using_latest_version'); ?></h3>
<?php } ?>
<?php if(count($update_errors) > 0){ ?>
   <p class="text-danger">Please fix the errors listed below.</p>
   <?php foreach($update_errors as $error){ ?>
      <div class="alert alert-danger">
         <?php echo $error; ?>
      </div>
   <?php } ?>
<?php } ?>
<?php if(isset($update_info->additional_data)){
   echo $update_info->additional_data;
} ?>
</div>
</div>
