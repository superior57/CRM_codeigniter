   <?php
   $pinned_projects = get_user_pinned_projects();
   if(count($pinned_projects) > 0){ ?>
      <li class="pinned-separator"></li>
      <?php foreach($pinned_projects as $project_pin){ ?>
         <li class="pinned_project">
            <a href="<?php echo admin_url('projects/view/'.$project_pin['id']); ?>" data-toggle="tooltip" data-title="<?php echo _l('pinned_project'); ?>"><?php echo $project_pin['name']; ?><br><small><?php echo $project_pin["company"]; ?></small></a>
            <div class="col-md-12">
               <div class="progress progress-bar-mini">
                  <div class="progress-bar no-percent-text not-dynamic" role="progressbar" data-percent="<?php echo $project_pin['progress']; ?>" style="width: <?php echo $project_pin['progress']; ?>%;">
                  </div>
               </div>
            </div>
         </li>
      <?php } ?>
      <?php } ?>
