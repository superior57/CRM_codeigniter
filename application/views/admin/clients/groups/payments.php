<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if(isset($client)){ ?>
	<h4 class="customer-profile-group-heading"><?php echo _l('client_payments_tab'); ?></h4>
	<a href="#" class="btn btn-info mbot25" data-toggle="modal" data-target="#client_zip_payments"><?php echo _l('zip_payments'); ?></a>
	<?php
	$this->load->view('admin/payments/table_html', array('class'=>'payments-single-client'));
	$this->load->view('admin/clients/modals/zip_payments');
	?>
<?php } ?>
