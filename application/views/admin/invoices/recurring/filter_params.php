<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="_filters _hidden_inputs">
	<?php
	foreach($invoices_sale_agents as $agent){
		echo form_hidden('sale_agent_'.$agent['sale_agent']);
	}
	foreach($invoices_years as $year){
		echo form_hidden('year_'.$year['year'],$year['year']);
	}
	?>
</div>
