<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row section-heading section-profile">
	<div class="col-md-8">
		<?php echo form_open_multipart('clients/profile',array('autocomplete'=>'off')); ?>
		<?php echo form_hidden('profile',true); ?>
		<div class="panel_s">
			<div class="panel-body">
				<h4 class="no-margin section-text"><?php echo _l('clients_profile_heading'); ?></h4>
			</div>
		</div>
		<div class="panel_s">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<?php if($contact->profile_image == NULL){ ?>
								<div class="form-group profile-image-upload-group">
									<label for="profile_image" class="profile-image"><?php echo _l('client_profile_image'); ?></label>
									<input type="file" name="profile_image" class="form-control" id="profile_image">
								</div>
							<?php } ?>
							<?php if($contact->profile_image != NULL){ ?>
								<div class="form-group profile-image-group">
									<div class="row">
										<div class="col-md-9">
											<img src="<?php echo contact_profile_image_url($contact->id,'thumb'); ?>" class="client-profile-image-thumb">
										</div>
										<div class="col-md-3 text-right">
											<a href="<?php echo site_url('clients/remove_profile_image'); ?>"><i class="fa fa-remove text-danger"></i></a>
										</div>
									</div>
								</div>
							<?php } ?>

						</div>
						<div class="form-group profile-firstname-group">
							<label for="firstname"><?php echo _l('clients_firstname'); ?></label>
							<input type="text" class="form-control" name="firstname" id="firstname" value="<?php echo set_value('firstname',$contact->firstname); ?>">
							<?php echo form_error('firstname'); ?>
						</div>
						<div class="form-group profile-lastname-group">
							<label for="lastname"><?php echo _l('clients_lastname'); ?></label>
							<input type="text" class="form-control" name="lastname" id="lastname" value="<?php echo set_value('lastname',$contact->lastname); ?>">
							<?php echo form_error('lastname'); ?>
						</div>
						<div class="form-group profile-positon-group">
							<label for="title"><?php echo _l('contact_position'); ?></label>
							<input type="text" class="form-control" name="title" id="title" value="<?php echo $contact->title; ?>">
						</div>
						<div class="form-group profile-email-group">
							<label for="email"><?php echo _l('clients_email'); ?></label>
							<input type="email" name="email" class="form-control" id="email" value="<?php echo $contact->email; ?>">
							<?php echo form_error('email'); ?>
						</div>
						<div class="form-group profile-phone-group">
							<label for="phonenumber"><?php echo _l('clients_phone'); ?></label>
							<input type="text" class="form-control" name="phonenumber" id="phonenumber" value="<?php echo $contact->phonenumber; ?>">
						</div>
						<div class="form-group contact-direction-option profile-direction-group">
							<label for="direction"><?php echo _l('document_direction'); ?></label>
							<select data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" class="form-control" name="direction" id="direction">
								<option value="" <?php if(empty($contact->direction)){echo 'selected';} ?>><?php echo _l('system_default_string'); ?></option>
								<option value="ltr" <?php if($contact->direction == 'ltr'){echo 'selected';} ?>>LTR</option>
								<option value="rtl" <?php if($contact->direction == 'rtl'){echo 'selected';} ?>>RTL</option>
							</select>
						</div>
						<?php echo render_custom_fields( 'contacts',get_contact_user_id(),array('show_on_client_portal'=>1)); ?>
						<?php if(can_contact_view_email_notifications_options()){ ?>
							<hr />
							<p class="bold email-notifications-label"><?php echo _l('email_notifications'); ?></p>
							<?php if(has_contact_permission('invoices')){ ?>
								<div class="checkbox checkbox-info email-notifications-invoices">
									<input type="checkbox" value="1" id="invoice_emails" name="invoice_emails"<?php if($contact->invoice_emails == 1){echo ' checked';} ?>>
									<label for="invoice_emails"><?php echo _l('invoice'); ?></label>
								</div>
								<div class="checkbox checkbox-info email-notifications-credit-notes">
									<input type="checkbox" value="1" id="credit_note_emails" name="credit_note_emails"<?php if($contact->credit_note_emails == 1){echo ' checked';} ?>>
									<label for="credit_note_emails"><?php echo _l('credit_note'); ?></label>
								</div>
							<?php } ?>
							<?php if(has_contact_permission('estimates')){ ?>
								<div class="checkbox checkbox-info email-notifications-estimates">
									<input type="checkbox" value="1" id="estimate_emails" name="estimate_emails"<?php if($contact->estimate_emails == 1){echo ' checked';} ?>>
									<label for="estimate_emails"><?php echo _l('estimate'); ?></label>
								</div>
							<?php } ?>
							<?php if(has_contact_permission('support')){ ?>
								<div class="checkbox checkbox-info email-notifications-tickets">
									<input type="checkbox" value="1" id="ticket_emails" name="ticket_emails"<?php if($contact->ticket_emails == 1){echo ' checked';} ?>>
									<label for="ticket_emails"><?php echo _l('tickets'); ?></label>
								</div>
							<?php } ?>
							<?php if(has_contact_permission('contracts')){ ?>
								<div class="checkbox checkbox-info email-notifications-contracts">
									<input type="checkbox" value="1" id="contract_emails" name="contract_emails"<?php if($contact->contract_emails == 1){echo ' checked';} ?>>
									<label for="contract_emails"><?php echo _l('contract'); ?></label>
								</div>
							<?php } ?>
							<?php if(has_contact_permission('projects')){ ?>
								<div class="checkbox checkbox-info email-notifications-projects">
									<input type="checkbox" value="1" id="project_emails" name="project_emails"<?php if($contact->project_emails == 1){echo ' checked';} ?>>
									<label for="project_emails"><?php echo _l('project'); ?></label>
								</div>
								<div class="checkbox checkbox-info email-notifications-tasks">
									<input type="checkbox" value="1" id="task_emails" name="task_emails"<?php if($contact->task_emails == 1){echo ' checked';} ?>>
									<label for="task_emails"><?php echo _l('task'); ?></label>
								</div>
							<?php } ?>
						<?php } ?>
					</div>
					<div class="row p15 contact-profile-save-section">
						<div class="col-md-12 text-right mtop20">
							<div class="form-group">
								<button type="submit" class="btn btn-info contact-profile-save"><?php echo _l('clients_edit_profile_update_btn'); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php echo form_close(); ?>
	</div>
	<div class="col-md-4 contact-profile-change-password-section">
		<div class="panel_s section-heading section-change-password">
			<div class="panel-body">
				<h4 class="no-margin section-text"><?php echo _l('clients_edit_profile_change_password_heading'); ?></h4>
			</div>
		</div>
		<div class="panel_s">
			<div class="panel-body">
				<?php echo form_open('clients/profile'); ?>
				<?php echo form_hidden('change_password',true); ?>
				<div class="form-group">
					<label for="oldpassword"><?php echo _l('clients_edit_profile_old_password'); ?></label>
					<input type="password" class="form-control" name="oldpassword" id="oldpassword">
					<?php echo form_error('oldpassword'); ?>
				</div>
				<div class="form-group">
					<label for="newpassword"><?php echo _l('clients_edit_profile_new_password'); ?></label>
					<input type="password" class="form-control" name="newpassword" id="newpassword">
					<?php echo form_error('newpassword'); ?>
				</div>
				<div class="form-group">
					<label for="newpasswordr"><?php echo _l('clients_edit_profile_new_password_repeat'); ?></label>
					<input type="password" class="form-control" name="newpasswordr" id="newpasswordr">
					<?php echo form_error('newpasswordr'); ?>
				</div>
				<div class="form-group">
					<button type="submit" class="btn btn-info btn-block"><?php echo _l('clients_edit_profile_change_password_btn'); ?></button>
				</div>
				<?php echo form_close(); ?>
			</div>
			<?php if($contact->last_password_change !== NULL){ ?>
				<div class="panel-footer last-password-change">
					<?php echo _l('clients_profile_last_changed_password',time_ago($contact->last_password_change)); ?>
				</div>
			<?php } ?>
		</div>
	</div>

</div>
