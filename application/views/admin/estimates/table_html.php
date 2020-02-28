<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = array(
   _l('estimate_dt_table_heading_number'),
   _l('estimate_dt_table_heading_amount'),
   _l('estimates_total_tax'),
   array(
      'name'=>_l('invoice_estimate_year'),
      'th_attrs'=>array('class'=>'not_visible')
   ),
   array(
      'name'=>_l('estimate_dt_table_heading_client'),
      'th_attrs'=>array('class'=> (isset($client) ? 'not_visible' : ''))
   ),
   _l('project'),
   _l('tags'),
   _l('estimate_dt_table_heading_date'),
   _l('estimate_dt_table_heading_expirydate'),
   _l('reference_no'),
   _l('estimate_dt_table_heading_status'));

$custom_fields = get_custom_fields('estimate',array('show_on_table'=>1));

foreach($custom_fields as $field){
   array_push($table_data,$field['name']);
}

$table_data = hooks()->apply_filters('estimates_table_columns', $table_data);

render_datatable($table_data, isset($class) ? $class : 'estimates');
