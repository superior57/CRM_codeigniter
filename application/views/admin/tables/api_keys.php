<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'CONCAT(firstname, " ", lastname) as full_name',
    'date_created',
    '`key`',
    ];

$sIndexColumn = 'id';
$sTable       = db_prefix() . config_item('rest_keys_table');
$where        = ['AND level > 0'];
$join         = [
    'JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . config_item('rest_keys_table') . '.user_id',
];
$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    [
    'id',
    ]
);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $full_name = $aRow['full_name'];

    $full_name .= '<div class="row-options">';

    $full_name .= '<a href="' . admin_url('api/regenerate_key/' . $aRow['key']) . '" class="_delete">Regenerate</a>';
    $full_name .= ' | <a href="' . admin_url('api/delete_key/' . $aRow['key']) . '" class="text-danger _delete">' . _l('delete') . '</a>';

    $full_name .= '</div>';

    $row[] = $full_name;

    $row[] = _dt(date('Y-m-d H:i:s', $aRow['date_created']));

    $row[] = $aRow['key'];

    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
