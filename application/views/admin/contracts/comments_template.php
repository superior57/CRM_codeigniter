<?php defined('BASEPATH') or exit('No direct script access allowed');
   ob_start();
   $len = count($comments);
   $i = 0;
   foreach ($comments as $comment) { ?>
<div class="col-md-12" data-commentid="<?php echo $comment['id']; ?>">

   <?php if($comment['staffid'] != 0){ ?>
   <a href="<?php echo admin_url('profile/' . $comment['staffid']); ?>">
   <?php
      echo staff_profile_image($comment['staffid'], array(
        'staff-profile-image-small',
        'media-object img-circle pull-left mright10'
        ));
        ?>
   </a>
   <?php } ?>
   <?php if ($comment['staffid'] == get_staff_user_id() || is_admin()) { ?>
   <a href="#" class="pull-right" onclick="remove_contract_comment(<?php echo $comment['id']; ?>); return false;">
   <i class="fa fa-times text-danger"></i>
   <a href="#" class="pull-right mright5" onclick="toggle_contract_comment_edit(<?php echo $comment['id']; ?>);return false;"><i class="fa fa-pencil-square-o"></i></a>
   </a>
   <?php } ?>
   <div class="media-body">
      <div class="mtop5">
      <?php if($comment['staffid'] != 0){ ?>
         <a href="<?php echo admin_url('profile/' . $comment['staffid']); ?>"><?php echo get_staff_full_name($comment['staffid']); ?></a>
      <?php } else { ?>
         <?php echo '<b>' . _l('is_customer_indicator') . '</b>'; ?>
      <?php } ?>
      <small class="text-muted text-has-action" data-toggle="tooltip" data-title="<?php echo _dt($comment['dateadded']); ?>"> - <?php echo time_ago($comment['dateadded']); ?></small>
      </div>
      <br />
      <div data-contract-comment="<?php echo $comment['id']; ?>">
         <?php echo check_for_links($comment['content']); ?>
      </div>
      <div data-contract-comment-edit-textarea="<?php echo $comment['id']; ?>" class="hide mtop15">
         <?php echo render_textarea('comment-content','',$comment['content']); ?>
         <?php if($comment['staffid'] == get_staff_user_id() || is_admin()){ ?>
         <div class="text-right">
            <button type="button" class="btn btn-default" onclick="toggle_contract_comment_edit(<?php echo $comment['id']; ?>);return false;"><?php echo _l('cancel'); ?></button>
            <button type="button" class="btn btn-info" onclick="edit_contract_comment(<?php echo $comment['id']; ?>);"><?php echo _l('update_comment'); ?></button>
         </div>
         <?php } ?>
      </div>
   </div>
   <?php if ($i >= 0 && $i != $len - 1) {
      echo '<hr />';
      }
      ?>
</div>
<?php $i++; } ?>
