<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = [
  _l('the_number_sign'),
  _l('expense_dt_table_heading_category'),
  _l('expense_dt_table_heading_amount'),
  _l('expense_name'),
  _l('expense_receipt'),
  _l('expense_dt_table_heading_date'),
];

if (!isset($project)) {
  array_push($table_data, _l('project'));
  array_push($table_data, [
    'name'     => _l('expense_dt_table_heading_customer'),
    'th_attrs' => ['class' => (isset($client) ? 'not_visible' : '')],
  ]);
}

$table_data = array_merge($table_data, [
  _l('invoice'),
  _l('expense_dt_table_heading_reference_no'),
  _l('expense_dt_table_heading_payment_mode'),
]);

$custom_fields = get_custom_fields('expenses', ['show_on_table' => 1]);

foreach ($custom_fields as $field) {
  array_push($table_data, $field['name']);
}

$table_data = hooks()->apply_filters('expenses_table_columns', $table_data);
render_datatable($table_data, (isset($class) ? $class : 'expenses'), [], [
  'data-last-order-identifier' => 'expenses',
  'data-default-order'         => get_table_last_order('expenses'),
]);
