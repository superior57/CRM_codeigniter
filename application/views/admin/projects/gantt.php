<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="panel_s">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-12">
							<h4><?php echo _l('filter_by'); ?></h4>
						</div>
						<?php echo form_open(admin_url('projects/gantt'), array('method'=>'get','id'=>'ganttFiltersForm')); ?>
						<div class="col-md-4">
							<select class="selectpicker" data-none-selected-text="<?php echo _l('all'); ?>" name="status[]" data-width="100%" multiple="true">
								<?php foreach($statuses as $status){
									$statusSelected = in_array($status['id'], $selected_statuses);
									?>
									<option value="<?php echo $status['id']; ?>"<?php if($statusSelected){echo ' selected';} ?>>
										<?php echo $status['name']; ?>
									</option>
								<?php } ?>
							</select>
						</div>
						<?php
			            /**
			             * Only show this filter if user has permission for projects view otherwise
			             * wont need this becuase by default this filter will be applied
			             */
			            if(has_permission('projects','','view')){ ?>
			            	<div class="col-md-3">
			            		<select class="selectpicker" data-live-search="true" data-title="<?php echo _l('project_member'); ?>" name="member" data-width="100%">
			            			<option value=""></option>
			            			<?php foreach($project_members as $member) { ?>
			            				<option value="<?php echo $member['staff_id']; ?>"<?php if($selectedMember == $member['staff_id']){echo ' selected'; } ?>>
			            					<?php echo $member['firstname'] . ' ' . $member['lastname']; ?>
			            				</option>
			            			<?php } ?>
			            		</option>
			            	</select>
			            </div>
			        <?php } ?>
			        <div class="col-md-1">
			        	<button type="submit" class="btn btn-default"><?php echo _l('apply'); ?></button>
			        </div>
			        <?php echo form_close(); ?>
			        <div class="clearfix"></div>
			        <hr />
			    </div>
			    <div id="gantt"></div>
			</div>
		</div>
	</div>
	<?php init_tail(); ?>
	<script>
		var gantt_data = <?php echo json_encode($gantt_data); ?>;
		$(function(){

			$("#gantt").gantt({
				source: gantt_data,
				itemsPerPage: 25,
				months: app.months_json,
				navigate: 'scroll',
				onRender: function() {
					$('#gantt .leftPanel .name .fn-label:empty').parents('.name').css('background', 'initial');
				},
				onItemClick: function(data) {
					if(typeof(data.project_id) != 'undefined') {
						var projectViewUrl = '<?php echo admin_url('projects/view'); ?>';
						window.location.href = projectViewUrl+'/'+data.project_id;
					} else if(typeof(data.task_id) != 'undefined') {
						init_task_modal(data.task_id);
					}
				},
			});
		});
	</script>
</body>
</html>
