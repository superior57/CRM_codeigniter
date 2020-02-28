<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = [
  _l('invoice_dt_table_heading_number'),
  _l('invoice_dt_table_heading_amount'),
  [
    'name'     => _l('invoice_estimate_year'),
    'th_attrs' => ['class' => 'not_visible'],
  ],
  [
    'name' => _l('invoice_dt_table_heading_client'),
  ],
  _l('frequency'),
  _l('cycles_remaining'),
  _l('last_child_invoice_date'),
  [
    'name'=>_l('next_invoice_date_list'),
    'th_attrs'=>['class'=>'next-recurring-date']
  ],
];
render_datatable($table_data, 'invoices');
