<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="panel_s">
			<div class="panel-body">
				<?php $this->load->view('admin/payments/table_html'); ?>
			</div>
		</div>
	</div>
</div>
<?php init_tail(); ?>
<script>
	$(function(){
		initDataTable('.table-payments', admin_url+'payments/table', undefined, undefined,'undefined',<?php echo hooks()->apply_filters('payments_table_default_order', json_encode(array(0,'desc'))); ?>);
	});
</script>
</body>
</html>
