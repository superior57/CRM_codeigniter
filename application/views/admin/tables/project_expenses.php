<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'expenses.id',
    'category',
    'amount',
    'expense_name',
    'file_name',
    'date',
    'invoiceid',
    'reference_no',
    'paymentmode',
];
$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid',
    'LEFT JOIN ' . db_prefix() . 'expenses_categories ON ' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category',
    'LEFT JOIN ' . db_prefix() . 'files ON ' . db_prefix() . 'files.rel_id = ' . db_prefix() . 'expenses.id AND rel_type="expense"',
];
$custom_fields = get_custom_fields('expenses', [
    'show_on_table' => 1,
]);
$i = 0;
foreach ($custom_fields as $field) {
    array_push($aColumns, 'ctable_' . $i . '.value as cvalue_' . $i);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $i . ' ON ' . db_prefix() . 'expenses.id = ctable_' . $i . '.relid AND ctable_' . $i . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $i . '.fieldid=' . $field['id']);
    $i++;
}
$where  = [];
$filter = [];
include_once(APPPATH . 'views/admin/tables/includes/expenses_filter.php');

array_push($where, 'AND project_id=' . $project_id);

if (!has_permission('expenses', '', 'view')) {
    array_push($where, 'AND ' . db_prefix() . 'expenses.addedfrom=' . get_staff_user_id());
}
$sIndexColumn = 'id';
$sTable       = db_prefix() . 'expenses';
$result       = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'name',
    'billable',
    'invoiceid',
    'currency',
    'tax',
    'tax2',
]);
$output  = $result['output'];
$rResult = $result['rResult'];
$this->ci->load->model('payment_modes_model');
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
            $_data = $aRow[strafter($aColumns[$i], 'as ')];
        } else {
            $_data = $aRow[$aColumns[$i]];
        }
        if ($aColumns[$i] == db_prefix() . 'expenses.id') {
            $_data = '<span class="label label-default inline-block">' . $_data . '</span>';
        } elseif ($aColumns[$i] == 'category') {
            $_data = '<a href="' . admin_url('expenses/list_expenses/' . $aRow[db_prefix() . 'expenses.id']) . '" target="_blank">' . $aRow['name'] . '</a>';
            if ($aRow['billable'] == 1) {
                if ($aRow['invoiceid'] == null) {
                    $_data .= '<p class="text-danger">' . _l('expense_list_unbilled') . '</p>';
                } else {
                    if (total_rows(db_prefix() . 'invoices', [
                        'id' => $aRow['invoiceid'],
                        'status' => 2,
                    ]) > 0) {
                        $_data .= '<br /><p class="text-success">' . _l('expense_list_billed') . '</p>';
                    } else {
                        $_data .= '<p class="text-success">' . _l('expense_list_invoice') . '</p>';
                    }
                }
            }
        } elseif ($aColumns[$i] == 'amount') {
            $total     = $_data;
            $tmp_total = $total;
            if ($aRow['tax'] != 0) {
                $_tax = get_tax_by_id($aRow['tax']);
                $total += ($total / 100 * $_tax->taxrate);
            }
            if ($aRow['tax2'] != 0) {
                $_tax = get_tax_by_id($aRow['tax2']);
                $total += ($tmp_total / 100 * $_tax->taxrate);
            }
            $_data = app_format_money($total, get_currency($aRow['currency']));
        } elseif ($aColumns[$i] == 'paymentmode') {
            $_data = '';
            if ($aRow['paymentmode'] != '0' && !empty($aRow['paymentmode'])) {
                $_data = $this->ci->payment_modes_model->get($aRow['paymentmode'])->name;
            }
        } elseif ($aColumns[$i] == 'file_name') {
            if (!empty($_data)) {
                $_data = '<a href="' . site_url('download/file/expense/' . $aRow[db_prefix() . 'expenses.id']) . '">' . $_data . '</a>';
            }
        } elseif ($aColumns[$i] == 'date') {
            $_data = _d($_data);
        } elseif ($aColumns[$i] == 'invoiceid') {
            if ($_data) {
                $_data = '<a href="' . admin_url('invoices/list_invoices/' . $_data) . '">' . format_invoice_number($_data) . '</a>';
            } else {
                $_data = '';
            }
        } else {
            if (startsWith($aColumns[$i], 'ctable_') && is_date($_data)) {
                $_data = _d($_data);
            }
        }
        $row[] = $_data;
    }
    $output['aaData'][] = $row;
}
