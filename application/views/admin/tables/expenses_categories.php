<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'name',
    'description',
    ];
$sIndexColumn = 'id';
$sTable       = db_prefix().'expenses_categories';
$result       = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], [
    'id',
    ]);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'name') {
            $_data = '<a href="#" onclick="edit_category(this,' . $aRow['id'] . '); return false;" data-name="' . $aRow['name'] . '" data-description="' . clear_textarea_breaks($aRow['description']) . '">' . $_data . '</a>';
        }
        $row[] = $_data;
    }
    $options = icon_btn('#', 'pencil-square-o', 'btn-default', [
        'onclick'          => 'edit_category(this,' . $aRow['id'] . '); return false;',
        'data-name'        => $aRow['name'],
        'data-description' => clear_textarea_breaks($aRow['description']),
        ]);
    $row[]              = $options .= icon_btn('expenses/delete_category/' . $aRow['id'], 'remove', 'btn-danger _delete');
    $output['aaData'][] = $row;
}
