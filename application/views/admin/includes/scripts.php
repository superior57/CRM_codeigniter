<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include_once(APPPATH.'views/admin/includes/helpers_bottom.php'); ?>

<?php hooks()->do_action('before_js_scripts_render'); ?>

<?php echo app_compile_scripts();

/**
 * Global function for custom field of type hyperlink
 */
echo get_custom_fields_hyperlink_js_function(); ?>
<?php
/**
 * Check for any alerts stored in session
 */
app_js_alerts();
?>
<?php
/**
 * Check pusher real time notifications
 */
if(get_option('pusher_realtime_notifications') == 1){ ?>
   <script type="text/javascript">
   $(function(){
         // Enable pusher logging - don't include this in production
         // Pusher.logToConsole = true;
         <?php $pusher_options = hooks()->apply_filters('pusher_options', array());
            if(!isset($pusher_options['cluster']) && get_option('pusher_cluster') != ''){
                  $pusher_options['cluster'] = get_option('pusher_cluster');
            }
         ?>
         var pusher_options = <?php echo json_encode($pusher_options); ?>;
         var pusher = new Pusher("<?php echo get_option('pusher_app_key'); ?>", pusher_options);
         var channel = pusher.subscribe('notifications-channel-<?php echo get_staff_user_id(); ?>');
         channel.bind('notification', function(data) {
            fetch_notifications();
         });
   });
   </script>
<?php } ?>
<?php app_admin_footer(); ?>
