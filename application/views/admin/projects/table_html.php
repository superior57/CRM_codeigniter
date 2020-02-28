<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = [
   _l('the_number_sign'),
   _l('project_name'),
    [
         'name'     => _l('project_customer'),
         'th_attrs' => ['class' => isset($client) ? 'not_visible' : ''],
    ],
   _l('tags'),
   _l('project_start_date'),
   _l('project_deadline'),
   _l('project_members'),
   _l('project_status'),
];

$custom_fields = get_custom_fields('projects', ['show_on_table' => 1]);
foreach ($custom_fields as $field) {
    array_push($table_data, $field['name']);
}

$table_data = hooks()->apply_filters('projects_table_columns', $table_data);

render_datatable($table_data, isset($class) ?  $class : 'projects', [], [
  'data-last-order-identifier' => 'projects',
  'data-default-order'  => get_table_last_order('projects'),
]);
