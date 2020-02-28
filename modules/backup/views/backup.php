<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body">
						<h4 class="no-margin">
							<?php echo $title; ?>
						</h4>
						<hr class="hr-panel-heading" />
						<div class="_buttons">
							<a href="<?php echo admin_url('backup/make_backup_db'); ?>" class="btn btn-default pull-right">
								<?php echo _l('utility_create_new_backup_db'); ?>
							</a>
							<a href="#" data-toggle="modal" data-target="#auto_backup_config" class="btn btn-default mright5 pull-right">
								<?php echo _l('auto_backup'); ?>
							</a>
						</div>
						<div class="clearfix"></div>
						<p class="mtop20 text-info bold">
							<?php echo _l('utility_db_backup_note'); ?>
						</p>

						<table class="table dt-table scroll-responsive" data-order-col="2" data-order-type="desc">
							<thead>
								<th><?php echo _l('utility_backup_table_backupname'); ?></th>
								<th><?php echo _l('utility_backup_table_backupsize'); ?></th>
								<th><?php echo _l('utility_backup_table_backupdate'); ?></th>
								<th><?php echo _l('options'); ?></th>
							</thead>
							<tbody>
								<?php $backups = list_files(BACKUPS_FOLDER); ?>
								<?php foreach($backups as $backup) {
									$fullPath = BACKUPS_FOLDER . $backup;
									$backupNameNoExtension = preg_replace('/\\.[^.\\s]{3,4}$/', '', $backup);
									?>
									<tr>
										<td>
											<a href="<?php echo site_url('backup/download/' . $backupNameNoExtension); ?>">
												<?php echo $backup; ?>
											</a>
										</td>
										<td>
											<?php echo bytesToSize($fullPath); ?>
										</td>
										<td data-order="<?php echo strftime('%Y-%m-%d %H:%M:%S', filectime($fullPath)); ?>">
											<?php echo date('M dS, Y, g:i a',filectime($fullPath)); ?>
										</td>
										<td>
											<a href="<?php echo admin_url('backup/delete/'.$backupNameNoExtension); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="auto_backup_config" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<?php echo form_open(admin_url('backup/update_auto_backup_options')); ?>
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo _l('auto_backup'); ?></h4>
			</div>
			<div class="modal-body">
				<?php echo render_yes_no_option('auto_backup_enabled','auto_backup_enabled'); ?>
				<?php echo render_input('auto_backup_every','auto_backup_every',get_option('auto_backup_every'),'number'); ?>
				<?php echo render_input('delete_backups_older_then','delete_backups_older_then',get_option('delete_backups_older_then'),'number'); ?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
			</div>
		</div><!-- /.modal-content -->
		<?php echo form_close(); ?>
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php init_tail(); ?>
</body>
</html>
