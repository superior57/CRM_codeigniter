<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if($project->settings->view_tasks == 1){ ?>
<!-- Project Tasks -->
<?php if(!isset($view_task)) { ?>
<div class="pull-right tasks-table<?php if($project->settings->view_milestones == 1){echo ' hide';} ?>">
   <select class="selectpicker" name="tasksFilterByStatus[]" onchange="dt_custom_view('.table-tasks', 3, $(this).selectpicker('val'));" multiple="true" data-none-selected-text="<?php echo _l('filter_by'); ?>">
      <?php foreach($tasks_statuses as $status) { ?>
      <option value="<?php echo $status['name']; ?>"><?php echo $status['name']; ?></option>
      <?php } ?>
   </select>
</div>
<?php } ?>
<?php if($project->settings->view_milestones == 1 && !isset($view_task)){ ?>
<a href="#" class="btn btn-default pull-left" onclick="taskTable(); return false;"><i class="fa fa-th-list"></i></a>
<div class="clearfix"></div>
<hr />
<div class="tasks-phases">
   <?php
      $milestones = array();
      $milestones[] = array(
       'name'=>_l('milestones_uncategorized'),
       'id'=>0,
       'total_logged_time'=>$this->projects_model->calc_milestone_logged_time($project->id,0),
       'total_tasks'=>total_project_tasks_by_milestone(0, $project->id),
       'total_finished_tasks'=>total_project_finished_tasks_by_milestone(0, $project->id),
       'color'=>NULL,
      );
      $_milestones = $this->projects_model->get_milestones($project->id);
      foreach($_milestones as $m){
          $milestones[] = $m;
      }
      ?>
   <div class="row">
      <?php foreach($milestones as $milestone){
         $tasks = $this->projects_model->get_tasks($project->id,array('milestone'=>$milestone['id']));
         $percent              = 0;
         if ($milestone['total_finished_tasks'] >= floatval($milestone['total_tasks'])) {
          $percent = 100;
         } else {
           if ($milestone['total_tasks'] !== 0) {
              $percent = number_format(($milestone['total_finished_tasks'] * 100) / $milestone['total_tasks'], 2);
           }
         }
         $milestone_color = '';
         if(!empty($milestone["color"]) && !is_null($milestone['color'])){
            $milestone_color = ' style="background:'.$milestone["color"].';border:1px solid '.$milestone['color'].'"';
         }
         ?>
      <div class="col-md-4<?php if($milestone['id'] == 0 && count($tasks) == 0){echo ' hide'; } ?>">
         <div class="panel-heading <?php if($milestone_color != ''){echo 'color-not-auto-adjusted color-white ';} ?><?php if($milestone['id'] != 0){echo 'task-phase';}else{echo 'info-bg';} ?>"<?php echo $milestone_color; ?>>
            <?php if($milestone['id'] != 0 && $milestone['description_visible_to_customer'] == 1){ ?>
            <i class="fa fa-file-text pointer" aria-hidden="true" data-toggle="popover" data-title="<?php echo _l('milestone_description'); ?>" data-html="true" data-content="<?php echo htmlspecialchars($milestone['description']); ?>"></i>&nbsp;
            <?php } ?>
            <span class="bold"><?php echo $milestone['name']; ?></span>
            <?php if($project->settings->view_task_total_logged_time == 1){ ?>
            <?php echo '<br /><small>' . _l('milestone_total_logged_time') . ': ' . seconds_to_time_format($milestone['total_logged_time']). '</small>';
               } ?>
         </div>
         <div class="panel-body">
            <?php
               if(count($tasks) == 0){
                echo _l('milestone_no_tasks_found');
               }
               foreach($tasks as $task){ ?>
            <div class="media _task_wrapper<?php if((!empty($task['duedate']) && $task['duedate'] < date('Y-m-d')) && $task['status'] != Tasks_model::STATUS_COMPLETE){ echo ' overdue-task'; } ?>">
               <div class="media-body">
                  <a href="<?php echo site_url('clients/project/'.$project->id.'?group=project_tasks&taskid='.$task['id']); ?>" class="task_milestone pull-left<?php if($task['status'] == Tasks_model::STATUS_COMPLETE){echo ' line-throught text-muted';} ?>"><?php echo $task['name']; ?></a>
                  <?php if($project->settings->edit_tasks == 1 && $task['is_added_from_contact'] == 1 && $task['addedfrom'] == get_contact_user_id()){ ?>
                  <a href="<?php echo site_url('clients/project/'.$project->id.'?group=edit_task&taskid='.$task['id']); ?>" class="pull-right">
                  <small><i class="fa fa-pencil-square-o"></i></small>
                  </a>
                  <?php } ?>
                  <br />
                  <small><?php echo _l('task_status'); ?>: <?php echo format_task_status($task['status'],true); ?></small>
                  <br />
                  <small><?php echo _l('tasks_dt_datestart'); ?>: <b><?php echo _d($task['startdate']); ?></b></small>
                  <?php if(is_date($task['duedate'])){ ?>
                  -
                  <small><?php echo _l('task_duedate'); ?>: <b><?php echo _d($task['duedate']); ?></b></small>
                  <?php } ?>
               </div>
            </div>
            <?php } ?>
         </div>
         <div class="panel-footer">
            <div class="progress no-margin progress-bg-dark">
               <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 0%" data-percent="<?php echo $percent; ?>">
               </div>
            </div>
         </div>
      </div>
      <?php } ?>
   </div>
</div>
<?php } ?>
<?php if(!isset($view_task)){ ?>
<div class="clearfix"></div>
<hr class="tasks-table" />
<div class="tasks-table<?php if($project->settings->view_milestones == 1){echo ' hide';} ?>">
   <table class="table dt-table table-tasks" data-order-col="3" data-s-type='[{"column":3,"type":"task-status"}]' data-order-type="asc">
      <thead>
         <tr>
            <th><?php echo _l('tasks_dt_name'); ?></th>
            <th><?php echo _l('tasks_dt_datestart'); ?></th>
            <th><?php echo _l('task_duedate'); ?></th>
            <th><?php echo _l('task_status'); ?></th>
            <?php if($project->settings->view_milestones == 1){ ?>
            <th><?php echo _l('task_milestone'); ?></th>
            <?php } ?>
            <th><?php echo _l('task_billable'); ?></th>
            <th><?php echo _l('task_billed'); ?></th>
            <?php
               $custom_fields = get_custom_fields('tasks',array('show_on_client_portal'=>1));
               foreach($custom_fields as $field){ ?>
            <th><?php echo $field['name']; ?></th>
            <?php } ?>
         </tr>
      </thead>
      <tbody>
         <?php
            foreach($project_tasks as $task){ ?>
         <tr>
            <td>
               <?php if($project->settings->edit_tasks == 1 && $task['is_added_from_contact'] == 1 && $task['addedfrom'] == get_contact_user_id()){ ?>
               <a href="<?php echo site_url('clients/project/'.$project->id.'?group=edit_task&taskid='.$task['id']); ?>"><i class="fa fa-pencil-square-o"></i></a>
               <?php } ?>
               <a href="<?php echo site_url('clients/project/'.$project->id.'?group=project_tasks&taskid='.$task['id']); ?>">
               <?php echo $task['name']; ?></a>
            </td>
            <td data-order="<?php echo $task['startdate']; ?>"><?php echo _d($task['startdate']); ?></td>
            <td data-order="<?php echo $task['duedate']; ?>"><?php echo _d($task['duedate']); ?></td>
            <td data-order="<?php echo $task['status']; ?>">
               <?php echo format_task_status($task['status']); ?>
            </td>
            <?php if($project->settings->view_milestones == 1){ ?>
            <td data-order="<?php echo $task['milestone_name']; ?>"><?php if($task['milestone'] != 0){ echo $task['milestone_name']; } ?></td>
            <?php } ?>
            <td data-order="<?php echo $task['billable']; ?>">
               <?php
                  if($task['billable'] == 1){
                   $billable = _l("task_billable_yes");
                  } else {
                   $billable = _l("task_billable_no");
                  }
                  echo $billable;
                  ?>
            </td>
            <td data-order="<?php echo $task['billed']; ?>">
               <?php
                  if($task['billed'] == 1){
                   $billed = '<span class="label label-success pull-left">'._l('task_billed_yes').'</span>';
                  } else {
                   $billed = '<span class="label label-'.($task['billable'] == 1 ? 'danger' : 'default').' pull-left">'._l('task_billed_no').'</span>';
                  }
                  echo $billed;
                  ?>
            </td>
            <?php foreach($custom_fields as $field){ ?>
            <td><?php echo get_custom_field_value($task['id'],$field['id'],'tasks'); ?></td>
            <?php } ?>
         </tr>
         <?php } ?>
      </tbody>
   </table>
</div>
<?php } else {
   get_template_part('projects/project_task');
   }
}
?>
