<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body">
                  <div class="_buttons">
                     <a href="#" onclick="save_menu();return false;" class="btn btn-info"><?php echo _l('utilities_menu_save'); ?></a>
                     <a href="<?php echo admin_url('menu_setup/reset_setup_menu'); ?>" class="btn btn-default"><?php echo _l('reset'); ?></a>
                  </div>
                  <div class="clearfix"></div>
                  <hr class="hr-panel-heading no-mbot" />
                  <div class="clearfix"></div>
                  <h4 class="bold mtop15"><?php echo _l('active_menu_items'); ?></h4>
                  <hr />
                  <div class="dd active">
                     <?php
                        echo '<ol class="dd-list">';
                        foreach($menu_items as $item){
                          $disabled = isset($menu_options->{$item['slug']}) && $menu_options->{$item['slug']}->disabled == 'true';
                         ?>
                     <li class="dd-item dd3-item main<?php echo (!isset($item['collapse']) ? ' dd-nochildren' : ''); ?>" data-id="<?php echo $item['slug']; ?>"<?php if($disabled){echo '  style="opacity:0.5"'; }?>>
                        <div class="dd-handle dd3-handle"></div>
                        <div class="dd3-content"><?php echo _l($item['name'],'', false); ?>
                           <a href="#" class="text-muted toggle-menu-options main-item-options pull-right"><i class="fa fa-cog"></i></a>
                        </div>
                        <div class="menu-options main-item-options" style="display:none;" data-menu-options="<?php echo $item['slug']; ?>">
                           <?php if(!isset($item['collapse']) && !in_array($item['slug'], $no_disable)){ ?>
                           <div class="form-group">
                              <div class="checkbox">
                                 <input type="checkbox"
                                    class="is-disabled-main"
                                    value="1"
                                    id="menu_disabled_<?php echo $item['slug']; ?>"
                                    name="disabled"<?php if($disabled){echo ' checked';} ?>>
                                 <label for="menu_disabled_<?php echo $item['slug']; ?>">Disabled?</label>
                              </div>
                           </div>
                           <?php } ?>
                           <label class="control-label"><?php echo _l('utilities_menu_icon'); ?></label>
                           <div class="input-group">
                              <?php
                                  $icon = app_get_menu_setup_icon($menu_options, $item['slug'], 'setup');
                                 ?>
                              <input type="text" value="<?php if($icon){echo $icon;} ?>" class="form-control icon-picker" id="icon-<?php echo $item['slug']; ?>">
                              <span class="input-group-addon">
                              <i class="<?php if($icon){echo $icon;} ?>"></i>
                              </span>
                           </div>
                        </div>
                        <?php if(count($item['children']) > 0){ ?>
                       <ol class="dd-list dd-list-sub-items">
                           <?php foreach($item['children'] as $submenu){
                            $child_disabled = (isset($menu_options->{$item['slug']}->children->{$submenu['slug']}) && $menu_options->{$item['slug']}->children->{$submenu['slug']}->disabled == 'true');
                            ?>
                           <li class="dd-item dd3-item sub-items" data-id="<?php echo $submenu['slug']; ?>"<?php if($child_disabled){echo '  style="opacity:0.5"'; }?>>
                              <div class="dd-handle dd3-handle"></div>
                              <div class="dd3-content"><?php echo _l($submenu['name'],'', false); ?>
                                 <a href="#" class="text-muted toggle-menu-options sub-item-options pull-right"><i class="fa fa-cog"></i></a>
                              </div>
                              <div class="menu-options sub-item-options" style="display:none;" data-menu-options="<?php echo $submenu['slug']; ?>">
                                 <?php if(!in_array($submenu['slug'], $no_disable)){ ?>
                                 <div class="form-group">
                                    <div class="checkbox">
                                       <input type="checkbox"
                                          class="is-disabled-child"
                                          value="1"
                                          id="menu_disabled_<?php echo $submenu['slug']; ?>"
                                          name="disabled"<?php if($child_disabled){echo ' checked';} ?>>
                                       <label for="menu_disabled_<?php echo $submenu['slug']; ?>">Disabled?</label>
                                    </div>
                                 </div>
                               <?php } ?>
                                 <label class="control-label"><?php echo _l('utilities_menu_icon'); ?></label>
                                 <div class="input-group">
                                    <?php
                                       $icon = app_get_menu_setup_icon($menu_options, $submenu['slug'], 'setup');
                                    ?>
                                    <input type="text" value="<?php if($icon){echo $icon;} ?>" class="form-control icon-picker" id="icon-<?php echo $submenu['slug']; ?>">
                                    <span class="input-group-addon">
                                    <i class="<?php if($icon){echo $icon;} ?>"></i>
                                    </span>
                                 </div>
                              </div>
                           </li>
                           <?php } ?>
                        </ol>
                        <?php } ?>
                     </li>
                     <?php } ?>
                     </ol>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
<?php init_tail(); ?>
<script src="<?php echo module_dir_url('menu_setup','assets/jquery-nestable/jquery.nestable.js'); ?>"></script>
<link href="<?php echo module_dir_url('menu_setup', 'assets/font-awesome-icon-picker/css/fontawesome-iconpicker.min.css'); ?>" rel="stylesheet">
<script src="<?php echo module_dir_url('menu_setup', 'assets/font-awesome-icon-picker/js/fontawesome-iconpicker.js'); ?>"></script>
<script>
   var iconPickerInitialized = false;
   $(function(){

     _formatMenuIconInput();

     $('.dd').nestable({
       maxDepth: 2
     });

     $('.toggle-menu-options').on('click', function(e) {
       e.preventDefault();
       if(iconPickerInitialized == false){
         $('.icon-picker').iconpicker()
         .on({'iconpickerSetSourceValue': function(e){
           _formatMenuIconInput(e);
         }})
         iconPickerInitialized = true;
       }
       menu_id = $(this).parents('li').data('id');
       if ($(this).hasClass('main-item-options')) {
         $(this).parents('li').find('.main-item-options[data-menu-options="' + menu_id + '"]').slideToggle();
       } else {
         $(this).parents('li').find('.sub-item-options[data-menu-options="' + menu_id + '"]').slideToggle();
       }
     });
   });

   function save_menu() {
      var items = $('body').find('.dd.active li').not(".dd-list-sub-items li");
      var mainPosition = false;
     $.each(items, function(key, val) {
       var main_menu = $(this);
         if(mainPosition === false) {
          mainPosition = key + 5;
       } else {
          mainPosition = mainPosition + 5;
       }
       main_menu.data('icon', main_menu.find('#icon-'+main_menu.data('id')).val());
       main_menu.data('disabled', main_menu.find('.is-disabled-main').prop('checked') === true);
       main_menu.data('position', mainPosition);

       var sub_items = main_menu.find('.dd-list-sub-items li');
       var subPosition = false;
       $.each(sub_items, function(subKey, val) {
         var sub_item = $(this);
          if(subPosition === false) {
            subPosition = subKey + 5;
         } else {
            subPosition = subPosition + 5;
         }
         sub_item.data('disabled', sub_item.find('.is-disabled-child').prop('checked') === true);
         sub_item.data('icon', sub_item.find('#icon-'+sub_item.data('id')).val());
         sub_item.data('position', subPosition);
       });
     });

     var data = {};
     data.options = $('.dd').nestable('serialize');

     $.post(admin_url + 'menu_setup/update_setup_menu', data).done(function() {
        window.location.reload();
     });
   }

</script>
</body>
</html>
