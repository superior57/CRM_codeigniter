<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'name',
    ];

$sIndexColumn = 'roleid';
$sTable       = db_prefix().'roles';

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], ['roleid']);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'name') {
            $_data            = '<a href="' . admin_url('roles/role/' . $aRow['roleid']) . '" class="mbot10 display-block">' . $_data . '</a>';
            $_data .= '<span class="mtop10 display-block">' . _l('roles_total_users') . ' ' . total_rows(db_prefix().'staff', [
                'role' => $aRow['roleid'],
                ]) . '</span>';
        }
        $row[] = $_data;
    }

    $options = icon_btn('roles/role/' . $aRow['roleid'], 'pencil-square-o');
    $row[]   = $options .= icon_btn('roles/delete/' . $aRow['roleid'], 'remove', 'btn-danger _delete');

    $output['aaData'][] = $row;
}
