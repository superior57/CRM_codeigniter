<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div id="setup-menu-wrapper" class="animated <?php if($this->session->has_userdata('setup-menu-open')
    && $this->session->userdata('setup-menu-open') == true){echo 'display-block';} ?>">
    <ul class="nav metis-menu" id="setup-menu">
        <li>
            <a class="close-customizer"><i class="fa fa-close"></i></a>
            <span class="text-left bold customizer-heading"><?php echo _l('setting_bar_heading'); ?></span>
        </li>
        <?php
        $totalSetupMenuItems = 0;
        foreach($setup_menu as $key => $item){
         if(isset($item['collapse']) && count($item['children']) === 0) {
           continue;
       }
       $totalSetupMenuItems++;
       ?>
       <li class="menu-item-<?php echo $item['slug']; ?>">
         <a href="<?php echo count($item['children']) > 0 ? '#' : $item['href']; ?>" aria-expanded="false">
             <i class="<?php echo $item['icon']; ?> menu-icon"></i>
             <span class="menu-text">
                 <?php echo _l($item['name'],'', false); ?>
             </span>
             <?php if(count($item['children']) > 0){ ?>
                 <span class="fa arrow"></span>
             <?php } ?>
         </a>
         <?php if(count($item['children']) > 0){ ?>
             <ul class="nav nav-second-level collapse" aria-expanded="false">
                <?php foreach($item['children'] as $submenu){
                   ?>
                   <li class="sub-menu-item-<?php echo $submenu['slug']; ?>"><a href="<?php echo $submenu['href']; ?>">
                       <?php if(!empty($submenu['icon'])){ ?>
                           <i class="<?php echo $submenu['icon']; ?> menu-icon"></i>
                       <?php } ?>
                       <span class="sub-menu-text">
                          <?php echo _l($submenu['name'],'',false); ?>
                      </span>
                  </a>
              </li>
          <?php } ?>
      </ul>
  <?php } ?>
</li>
<?php hooks()->do_action('after_render_single_setup_menu', $item); ?>
<?php } ?>
<?php if(get_option('show_help_on_setup_menu') == 1 && is_admin()){ $totalSetupMenuItems++; ?>
    <li>
        <a href="<?php echo hooks()->apply_filters('help_menu_item_link','https://help.perfexcrm.com'); ?>" target="_blank">
            <?php echo hooks()->apply_filters('help_menu_item_text',_l('setup_help')); ?>
        </a>
    </li>
<?php } ?>
</ul>
</div>
<?php $this->app->set_setup_menu_visibility($totalSetupMenuItems); ?>
