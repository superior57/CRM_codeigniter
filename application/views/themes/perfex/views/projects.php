<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s section-heading section-projects">
   <div class="panel-body">
      <h4 class="no-margin section-text"><?php echo _l('clients_my_projects'); ?></h4>
   </div>
</div>
<div class="panel_s">
   <div class="panel-body">
      <div class="row mbot15">
         <div class="col-md-12">
            <h3 class="text-success projects-summary-heading no-mtop mbot15"><?php echo _l('projects_summary'); ?></h3>
         </div>
         <?php get_template_part('projects/project_summary'); ?>
      </div>
      <hr />
         <table class="table dt-table table-projects" data-order-col="2" data-order-type="desc">
            <thead>
               <tr>
                  <th class="th-project-name"><?php echo _l('project_name'); ?></th>
                  <th class="th-project-start-date"><?php echo _l('project_start_date'); ?></th>
                  <th class="th-project-deadline"><?php echo _l('project_deadline'); ?></th>
                  <th class="th-project-billing-type"><?php echo _l('project_billing_type'); ?></th>
                  <?php
                     $custom_fields = get_custom_fields('projects',array('show_on_client_portal'=>1));
                     foreach($custom_fields as $field){ ?>
                  <th><?php echo $field['name']; ?></th>
                  <?php } ?>
                  <th><?php echo _l('project_status'); ?></th>
               </tr>
            </thead>
            <tbody>
               <?php foreach($projects as $project){ ?>
               <tr>
                  <td><a href="<?php echo site_url('clients/project/'.$project['id']); ?>"><?php echo $project['name']; ?></a></td>
                  <td data-order="<?php echo $project['start_date']; ?>"><?php echo _d($project['start_date']); ?></td>
                  <td data-order="<?php echo $project['deadline']; ?>"><?php echo _d($project['deadline']); ?></td>
                  <td>
                     <?php
                        if($project['billing_type'] == 1){
                          $type_name = 'project_billing_type_fixed_cost';
                        } else if($project['billing_type'] == 2){
                          $type_name = 'project_billing_type_project_hours';
                        } else {
                          $type_name = 'project_billing_type_project_task_hours';
                        }
                        echo _l($type_name);
                        ?>
                  </td>
                  <?php foreach($custom_fields as $field){ ?>
                  <td><?php echo get_custom_field_value($project['id'],$field['id'],'projects'); ?></td>
                  <?php } ?>
                  <td>
                     <?php
                        $status = get_project_status_by_id($project['status']);
                        echo '<span class="label inline-block" style="color:'.$status['color'].';border:1px solid '.$status['color'].'">'.$status['name'].'</span>';
                        ?>
                  </td>
               </tr>
               <?php } ?>
            </tbody>
         </table>
   </div>
</div>
