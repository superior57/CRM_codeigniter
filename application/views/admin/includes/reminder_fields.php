<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_hidden('rel_id',$id); ?>
<?php echo form_hidden('rel_type',$name); ?>
<?php echo render_datetime_input('date','set_reminder_date','',array('data-date-min-date'=>_d(date('Y-m-d')))); ?>
<?php echo render_select('staff',$members,array('staffid',array('firstname','lastname')),'reminder_set_to',get_staff_user_id(),array('data-current-staff'=>get_staff_user_id())); ?>
<?php echo render_textarea('description','reminder_description'); ?>
<?php if(total_rows(db_prefix().'emailtemplates',array('slug'=>'reminder-email-staff','active'=>0)) == 0) { ?>
  <div class="form-group">
    <div class="checkbox checkbox-primary">
      <input type="checkbox" name="notify_by_email" id="notify_by_email">
      <label for="notify_by_email"><?php echo _l('reminder_notify_me_by_email'); ?></label>
    </div>
  </div>
<?php } ?>
