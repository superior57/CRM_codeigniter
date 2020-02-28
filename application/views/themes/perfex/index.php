<?php defined('BASEPATH') or exit('No direct script access allowed');
echo theme_head_view();
get_template_part($navigationEnabled ? 'navigation' : '');
?>
<div id="wrapper">
   <div id="content">
      <div class="container">
         <div class="row">
            <?php get_template_part('alerts'); ?>
         </div>
      </div>
      <?php if(isset($knowledge_base_search)){ ?>
         <?php get_template_part('knowledge_base/search'); ?>
      <?php } ?>
      <div class="container">
         <?php hooks()->do_action('customers_content_container_start'); ?>
         <div class="row">
            <?php
            /**
             * Don't show calendar for invoices, estimates, proposals etc.. views where no navigation is included or in kb area
             */
            if(is_client_logged_in() && $subMenuEnabled && !isset($knowledge_base_search)){ ?>
               <ul class="submenu customer-top-submenu">
                  <li class="customers-top-submenu-files"><a href="<?php echo site_url('clients/files'); ?>"><i class="fa fa-file" aria-hidden="true"></i> <?php echo _l('customer_profile_files'); ?></a></li>
                  <li class="customers-top-submenu-calendar"><a href="<?php echo site_url('clients/calendar'); ?>"><i class="fa fa-calendar-minus-o" aria-hidden="true"></i> <?php echo _l('calendar'); ?></a></li>
               </ul>
               <div class="clearfix"></div>
            <?php } ?>
            <?php echo theme_template_view(); ?>
         </div>
      </div>
   </div>
   <?php
   echo theme_footer_view();
   ?>
</div>
<?php
/* Always have app_customers_footer() just before the closing </body>  */
app_customers_footer();
   /**
   * Check for any alerts stored in session
   */
   app_js_alerts();
   ?>
</body>
</html>
