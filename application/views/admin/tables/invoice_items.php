<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [];

if (has_permission('items', '', 'delete')) {
    $aColumns[] = '1';
}

$aColumns = array_merge($aColumns, [
    'description',
    'long_description',
    db_prefix() . 'items.rate',
    't1.taxrate as taxrate_1',
    't2.taxrate as taxrate_2',
    'unit',
    db_prefix() . 'items_groups.name',
    ]);

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'items';

$join = [
    'LEFT JOIN ' . db_prefix() . 'taxes t1 ON t1.id = ' . db_prefix() . 'items.tax',
    'LEFT JOIN ' . db_prefix() . 'taxes t2 ON t2.id = ' . db_prefix() . 'items.tax2',
    'LEFT JOIN ' . db_prefix() . 'items_groups ON ' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id',
    ];
$additionalSelect = [
    db_prefix() . 'items.id',
    't1.name as taxname_1',
    't2.name as taxname_2',
    't1.id as tax_id_1',
    't2.id as tax_id_2',
    'group_id',
    ];

$custom_fields = get_custom_fields('items');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);

    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'items.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="items_pr" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], $additionalSelect);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
            $_data = $aRow[strafter($aColumns[$i], 'as ')];
        } else {
            $_data = $aRow[$aColumns[$i]];
        }

        if ($aColumns[$i] == '1') {
            $_data = '<div class="checkbox"><input type="checkbox" value="' . $aRow['id'] . '"><label></label></div>';
        } elseif ($aColumns[$i] == 't1.taxrate as taxrate_1') {
            if (!$aRow['taxrate_1']) {
                $aRow['taxrate_1'] = 0;
            }
            $_data = '<span data-toggle="tooltip" title="' . $aRow['taxname_1'] . '" data-taxid="' . $aRow['tax_id_1'] . '">' . $aRow['taxrate_1'] . '%' . '</span>';
        } elseif ($aColumns[$i] == 't2.taxrate as taxrate_2') {
            if (!$aRow['taxrate_2']) {
                $aRow['taxrate_2'] = 0;
            }
            $_data = '<span data-toggle="tooltip" title="' . $aRow['taxname_2'] . '" data-taxid="' . $aRow['tax_id_2'] . '">' . $aRow['taxrate_2'] . '%' . '</span>';
        } elseif ($aColumns[$i] == 'description') {
            $_data = '<a href="#" data-toggle="modal" data-target="#sales_item_modal" data-id="' . $aRow['id'] . '">' . $_data . '</a>';
            $_data .= '<div class="row-options">';

            if (has_permission('items', '', 'edit')) {
                $_data .= '<a href="#" data-toggle="modal" data-target="#sales_item_modal" data-id="' . $aRow['id'] . '">' . _l('edit') . '</a>';
            }

            if (has_permission('items', '', 'delete')) {
                $_data .= ' | <a href="' . admin_url('invoice_items/delete/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            $_data .= '</div>';
        } else {
            if (startsWith($aColumns[$i], 'ctable_') && is_date($_data)) {
                $_data = _d($_data);
            }
        }

        $row[]              = $_data;
        $row['DT_RowClass'] = 'has-row-options';
    }


    $output['aaData'][] = $row;
}
