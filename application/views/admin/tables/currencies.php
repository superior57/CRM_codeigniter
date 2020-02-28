<?php

defined('BASEPATH') or exit('No direct script access allowed');
$aColumns = [
    'name',
    'symbol',
    ];
$sIndexColumn = 'id';
$sTable       = db_prefix() . 'currencies';
$result       = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], [
    'id',
    'isdefault',
    'placement',
    'thousand_separator',
    'decimal_separator',
]);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];

        $attributes = [
        'data-toggle'             => 'modal',
        'data-target'             => '#currency_modal',
        'data-id'                 => $aRow['id'],
        'data-placement'          => $aRow['placement'],
        'data-thousand-separator' => $aRow['thousand_separator'],
        'data-decimal-separator'  => $aRow['decimal_separator'],
        ];

        if ($aColumns[$i] == 'name') {
            $_data = '<span class="name"><a href="#" ' . _attributes_to_string($attributes) . '>' . $_data . '</a></span>';
            if ($aRow['isdefault'] == 1) {
                $_data .= '<span class="display-block text-info">' . _l('base_currency_string') . '</span>';
            }
        }
        $row[] = $_data;
    }
    $options = icon_btn('#' . $aRow['id'], 'pencil-square-o', 'btn-default', $attributes);

    if ($aRow['isdefault'] == 0) {
        $options .= icon_btn('currencies/make_base_currency/' . $aRow['id'], 'star', 'btn-info', [
            'data-toggle' => 'tooltip',
            'title'       => _l('make_base_currency'),
            ]);
    }

    $row[]              = $options .= icon_btn('currencies/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');
    $output['aaData'][] = $row;
}
