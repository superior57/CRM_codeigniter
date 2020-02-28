<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body">
						<div class="_buttons">
							<a href="#" class="btn btn-info pull-left" data-toggle="modal" data-target="#currency_modal"><?php echo _l('new_currency'); ?></a>
						</div>
						<div class="clearfix"></div>
						<hr class="hr-panel-heading" />
						<div class="clearfix"></div>
						<?php render_datatable(array(
							_l('currency_list_name'),
							_l('currency_list_symbol'),
							_l('options'),
						),'currencies'); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="currency_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">
					<span class="edit-title"><?php echo _l('currency_edit_heading'); ?></span>
					<span class="add-title"><?php echo _l('currency_add_heading'); ?></span>
				</h4>
			</div>
			<?php echo form_open('admin/currencies/manage',array('id'=>'currency_form')); ?>
			<?php echo form_hidden('currencyid'); ?>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div class="alert alert-warning"><?php echo _l('currency_valid_code_help'); ?></div>
						<?php echo render_input('name','currency_add_edit_description','','text',array('placeholder'=>_l('iso_code'))); ?>
						<?php echo render_input('symbol','currency_add_edit_rate'); ?>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label for="decimal_separator"><?php echo _l('settings_sales_decimal_separator'); ?></label>
									<select id="decimal_separator" class="selectpicker" name="decimal_separator" data-width="100%">
										<option value=",">,</option>
										<option value=".">.</option>
									</select>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label for="thousand_separator"><?php echo _l('settings_sales_thousand_separator'); ?></label>
									<select id="thousand_separator" class="selectpicker" name="thousand_separator" data-width="100%" data-show-subtext="true">
										<option value=",">,</option>
										<option value=".">.</option>
										<option value="'" data-subtext="apostrophe">'</option>
										<option value="" data-subtext="none">&nbsp;</option>
										<option value=" " data-subtext="space">&nbsp;</option>
									</select>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="placement" class="control-label clearfix"><?php echo _l('settings_sales_currency_placement'); ?></label>
							<div class="radio radio-primary radio-inline">
								<input type="radio" name="placement" value="before" id="c_placement_before">
								<label for="c_placement_before"><?php echo _l('settings_sales_currency_placement_before'); ?></label>
							</div>
							<div class="radio radio-primary radio-inline">
								<input type="radio" name="placement" id="c_placement_after" value="after">
								<label for="c_placement_after"><?php echo _l('settings_sales_currency_placement_after'); ?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</div>
<?php init_tail(); ?>
<script>
	$(function(){

		initDataTable('.table-currencies', window.location.href, [2], [2]);

		appValidateForm($('form'), {
			name:{
				required:true,
				maxlength:3
			},
			symbol: 'required',
			decimal_separator:'required',
			thousand_separator: 'required',
			placement:'required',
		}, manage_currencies);

		$('#currency_modal').on('show.bs.modal', function(event) {

			var button = $(event.relatedTarget)
			var id = button.data('id');

			$('#currency_modal input[name="name"]').val('');
			$('#currency_modal input[name="symbol"]').val('');
			$('#currency_modal input[name="currencyid"]').val('');

			$('#currency_modal #c_placement_before').prop('checked', true);
			$('#currency_modal #decimal_separator').selectpicker('val', "<?php echo get_option('decimal_separator'); ?>");
			$('#currency_modal #thousand_separator').selectpicker('val', "<?php echo get_option('thousand_separator'); ?>");

			$('#currency_modal .add-title').removeClass('hide');
			$('#currency_modal .edit-title').addClass('hide');

			if (typeof(id) !== 'undefined') {
				$('input[name="currencyid"]').val(id);
				var name = $(button).parents('tr').find('td').eq(0).find('span.name').text();
				var symbol = $(button).parents('tr').find('td').eq(1).text();
				var xrate = $(button).parents('tr').find('td').eq(2).text();
				$('#currency_modal .add-title').addClass('hide');
				$('#currency_modal .edit-title').removeClass('hide');
				$('#currency_modal input[name="name"]').val(name);
				$('#currency_modal input[name="symbol"]').val(symbol);

				$('#currency_modal #c_placement_'+button.data('placement')).prop('checked', true);
				$('#currency_modal #decimal_separator').selectpicker('val', button.data('decimal-separator'));
				$('#currency_modal #thousand_separator').selectpicker('val', button.data('thousand-separator'));
			}
		});
	});
	/* CURRENCY MANAGE FUNCTIONS */
	function manage_currencies(form) {
		var data = $(form).serialize();
		var url = form.action;
		$.post(url, data).done(function(response) {
			response = JSON.parse(response);
			if (response.success == true) {
				$('.table-currencies').DataTable().ajax.reload();
				alert_float('success', response.message);
			}
			$('#currency_modal').modal('hide');
		});
		return false;
	}

</script>
</body>
</html>
