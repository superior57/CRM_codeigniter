<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'id',
    'name',
    'taxrate',
    ];
$sIndexColumn = 'id';
$sTable       = db_prefix().'taxes';

$result = data_tables_init($aColumns, $sIndexColumn, $sTable);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data                       = $aRow[$aColumns[$i]];
        $is_referenced_expenses      = (total_rows(db_prefix().'expenses', ['tax' => $aRow['id']]) > 0 || total_rows(db_prefix().'expenses', ['tax2' => $aRow['id']]) > 0 ? 1 : 0);
        $is_referenced_subscriptions = total_rows(db_prefix().'subscriptions', ['tax_id' => $aRow['id']]) > 0;
        if ($aColumns[$i] == 'name') {
            $_data = '<a href="#" data-toggle="modal" data-is-referenced-expenses="' . $is_referenced_expenses . '" data-is-referenced-subscriptions="' . $is_referenced_subscriptions . '" data-target="#tax_modal" data-id="' . $aRow['id'] . '">' . $_data . '</a>';
        }
        $row[] = $_data;
    }

    $options = icon_btn('#' . $aRow['id'], 'pencil-square-o', 'btn-default', [
        'data-toggle'                      => 'modal',
        'data-target'                      => '#tax_modal',
        'data-id'                          => $aRow['id'],
        'data-is-referenced-expenses'      => $is_referenced_expenses,
        'data-is-referenced-subscriptions' => $is_referenced_subscriptions,
        ]);

    $row[] = $options .= icon_btn('taxes/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');

    $output['aaData'][] = $row;
}
