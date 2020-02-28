 <div class="form-group select-placeholder">
 	<select
 	class="selectpicker"
 	name="range"
 	id="range"
 	data-width="100%"<?php echo (isset($onChange) ? 'onchange="'.$onChange.'"' : ''); ?>>

 	<option value='<?php echo json_encode(
 	array(
 	_d(date('Y-m-d')),
 	_d(date('Y-m-d'))
 	)); ?>'>
 	<?php echo _l('today'); ?>
 </option>
 <option value='<?php echo json_encode(
 array(
 _d(date('Y-m-d', strtotime('monday this week'))),
 _d(date('Y-m-d', strtotime('sunday this week')))
 )); ?>'>
 <?php echo _l('this_week'); ?>
</option>
<option value='<?php echo json_encode(
array(
_d(date('Y-m-01')),
_d(date('Y-m-t'))
)); ?>' selected>
<?php echo _l('this_month'); ?>
</option>
<option value='<?php echo json_encode(
array(
_d(date('Y-m-01', strtotime("-1 MONTH"))),
_d(date('Y-m-t', strtotime('-1 MONTH')))
)); ?>'>
<?php echo _l('last_month'); ?>
</option>
<option value='<?php echo json_encode(
array(
_d(date('Y-m-d',strtotime(date('Y-01-01')))),
_d(date('Y-m-d',strtotime(date('Y-12-31'))))
)); ?>'>
<?php echo _l('this_year'); ?>
</option>
<option value='<?php echo json_encode(
array(
_d(date('Y-m-d',strtotime(date(date('Y',strtotime('last year')).'-01-01')))),
_d(date('Y-m-d',strtotime(date(date('Y',strtotime('last year')). '-12-31'))))
)); ?>'>
<?php echo _l('last_year'); ?>
</option>
<option value="period"><?php echo _l('period_datepicker'); ?></option>
</select>
</div>
<div class="row mtop15">
	<div class="col-md-12 period hide">
		<?php echo render_date_input('period-from','','',array('onchange'=>isset($onChange) ? $onChange : '')); ?>
	</div>
	<div class="col-md-12 period hide">
		<?php echo render_date_input('period-to','','',array('onchange'=>isset($onChange) ? $onChange : '')); ?>
	</div>
</div>
