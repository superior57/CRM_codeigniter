<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <?php if(!is_gdpr()) { ?>
         <div class="panel_s">
            <div class="panel-body">
               <div class="col-md-12 text-center">
                  <h4><?php echo _l('gdpr_not_enabled'); ?></h4>
                  <a href="<?php echo admin_url('gdpr/enable'); ?>" class="btn btn-info"><?php echo _l('enable_gdpr'); ?></a>
               </div>
            </div>
         </div>
         <?php } else { ?>
         <?php if($save == true){ ?>
         <?php echo form_open(admin_url('gdpr/save?page='.$page)); ?>
         <?php } ?>
         <div class="col-md-3">
            <ul class="nav navbar-pills navbar-pills-flat nav-tabs nav-stacked">
               <li<?php if($page == 'general'){echo ' class="active"'; } ?>>
                  <a href="<?php echo admin_url('gdpr/index?page=general'); ?>"><?php echo _l('settings_group_general'); ?></a>
               </li>
               <li<?php if($page == 'portability'){echo ' class="active"'; } ?>>
                  <a href="<?php echo admin_url('gdpr/index?page=portability'); ?>"><?php echo _l('gdpr_right_to_data_portability'); ?></a>
               </li>
               <li<?php if($page == 'forgotten'){echo ' class="active"'; } ?>>
                  <a href="<?php echo admin_url('gdpr/index?page=forgotten'); ?>"><?php echo _l('gdpr_right_to_erasure'); ?></a>
               </li>
               <li<?php if($page == 'informed'){echo ' class="active"'; } ?>>
                  <a href="<?php echo admin_url('gdpr/index?page=informed'); ?>"><?php echo _l('gdpr_right_to_be_informed'); ?></a>
               </li>
               <li<?php if($page == 'rectification'){echo ' class="active"'; } ?>>
                  <a href="<?php echo admin_url('gdpr/index?page=rectification'); ?>"><?php echo _l('gdpr_right_of_access'); ?>/<?php echo _l('gdpr_right_to_rectification'); ?></a>
               </li>
               <li<?php if($page == 'consent'){echo ' class="active"'; } ?>>
                  <a href="<?php echo admin_url('gdpr/index?page=consent'); ?>"><?php echo _l('gdpr_consent'); ?></a>
               </li>
            </ul>
         </div>
         <div class="col-md-9">
            <div class="panel_s">
               <div class="panel-body">
                  <?php hooks()->do_action('before_admin_gdpr_settings'); ?>
                  <?php $this->load->view('admin/gdpr/pages/'.$page); ?>
               </div>
            </div>
         </div>
         <?php if($save == true){ ?>
         <div class="btn-bottom-toolbar btn-toolbar-container-out text-right">
            <button type="submit" class="btn btn-info"><?php echo _l('save'); ?></button>
         </div>
         <?php echo form_close(); ?>
         <?php } ?>
         <?php } ?>
      </div>
   </div>
</div>
<div id="page-tail"></div>
<?php init_tail(); ?>
<script>
   $(function(){
     $('.removalStatus').on('change', function(e){
       var id = $(this).attr('data-id');
       var val = $(this).selectpicker('val');

       // Event is invoked twice? Second is jQuery object
       if(typeof(val) != 'string') {
          return;
       }
       requestGet('gdpr/change_removal_request_status/'+id+'/'+val);
     });
   });
</script>
</body>
</html>
