<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'expenses.id as id',
    db_prefix() . 'expenses_categories.name as category_name',
    'amount',
    'expense_name',
    'file_name',
    'date',
    db_prefix() . 'projects.name as project_name',
    get_sql_select_client_company(),
    'invoiceid',
    'reference_no',
    'paymentmode',
];
$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid',
    'JOIN ' . db_prefix() . 'expenses_categories ON ' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category',
    'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'expenses.project_id',
    'LEFT JOIN ' . db_prefix() . 'files ON ' . db_prefix() . 'files.rel_id = ' . db_prefix() . 'expenses.id AND rel_type="expense"',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'expenses.currency',
];

$custom_fields = get_table_custom_fields('expenses');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'expenses.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$where  = [];
$filter = [];
include_once(APPPATH . 'views/admin/tables/includes/expenses_filter.php');

if ($clientid != '') {
    array_push($where, 'AND ' . db_prefix() . 'expenses.clientid=' . $clientid);
}

if (!has_permission('expenses', '', 'view')) {
    array_push($where, 'AND ' . db_prefix() . 'expenses.addedfrom=' . get_staff_user_id());
}

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'expenses';

$aColumns = hooks()->apply_filters('expenses_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'category',
    'billable',
    db_prefix().'currencies.name as currency_name',
    db_prefix() . 'expenses.clientid',
    'tax',
    'tax2',
    'project_id',
    'recurring',
]);
$output  = $result['output'];
$rResult = $result['rResult'];

$this->ci->load->model('payment_modes_model');

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = $aRow['id'];

    $categoryOutput = '';

    if (is_numeric($clientid)) {
        $categoryOutput = '<a href="' . admin_url('expenses/list_expenses/' . $aRow['id']) . '">' . $aRow['category_name'] . '</a>';
    } else {
        $categoryOutput = '<a href="' . admin_url('expenses/list_expenses/' . $aRow['id']) . '" onclick="init_expense(' . $aRow['id'] . ');return false;">' . $aRow['category_name'] . '</a>';
    }

    if ($aRow['recurring'] == 1) {
        $categoryOutput .= '<br /><span class="label label-primary inline-block mtop4"> ' . _l('expense_recurring_indicator') . '</span>';
    }

    if ($aRow['billable'] == 1) {
        if ($aRow['invoiceid'] == null) {
            $categoryOutput .= ' <p class="text-danger">' . _l('expense_list_unbilled') . '</p>';
        } else {
            if (total_rows(db_prefix() . 'invoices', [
                'id' => $aRow['invoiceid'],
                'status' => 2,
                ]) > 0) {
                $categoryOutput .= ' <p class="text-success">' . _l('expense_list_billed') . '</p>';
            } else {
                $categoryOutput .= ' <p class="text-success">' . _l('expense_list_invoice') . '</p>';
            }
        }
    }

    $categoryOutput .= '<div class="row-options">';


    $categoryOutput .= '<a href="' . admin_url('expenses/list_expenses/' . $aRow['id']) . '" onclick="init_expense(' . $aRow['id'] . ');return false;">' . _l('view') . '</a>';

    if (has_permission('expenses', '', 'edit')) {
        $categoryOutput .= ' | <a href="' . admin_url('expenses/expense/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }

    if (has_permission('expenses', '', 'delete')) {
        $categoryOutput .= ' | <a href="' . admin_url('expenses/delete/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    }

    $categoryOutput .= '</div>';
    $row[] = $categoryOutput;

    $total    = $aRow['amount'];
    $tmpTotal = $total;

    if ($aRow['tax'] != 0) {
        $tax = get_tax_by_id($aRow['tax']);
        $total += ($total / 100 * $tax->taxrate);
    }
    if ($aRow['tax2'] != 0) {
        $tax = get_tax_by_id($aRow['tax2']);
        $total += ($tmpTotal / 100 * $tax->taxrate);
    }

    $row[] = app_format_money($total, $aRow['currency_name']);

    $row[] = '<a href="' . admin_url('expenses/list_expenses/' . $aRow['id']) . '" onclick="init_expense(' . $aRow['id'] . ');return false;">' . $aRow['expense_name'] . '</a>';

    $outputReceipt = '';

    if (!empty($aRow['file_name'])) {
        $outputReceipt = '<a href="' . site_url('download/file/expense/' . $aRow['id']) . '">' . $aRow['file_name'] . '</a>';
    }

    $row[] = $outputReceipt;

    $row[] = _d($aRow['date']);

    $row[] = '<a href="' . admin_url('projects/view/' . $aRow['project_id']) . '">' . $aRow['project_name'] . '</a>';

    $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '">' . $aRow['company'] . '</a>';

    if ($aRow['invoiceid']) {
        $row[] = '<a href="' . admin_url('invoices/list_invoices/' . $aRow['invoiceid']) . '">' . format_invoice_number($aRow['invoiceid']) . '</a>';
    } else {
        $row[] = '';
    }

    $row[] = $aRow['reference_no'];

    $paymentModeOutput = '';
    if ($aRow['paymentmode'] != '0' && !empty($aRow['paymentmode'])) {
        $payment_mode = $this->ci->payment_modes_model->get($aRow['paymentmode'], [], false, true);
        if ($payment_mode) {
            $paymentModeOutput = $payment_mode->name;
        }
    }
    $row[] = $paymentModeOutput;

    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row['DT_RowClass'] = 'has-row-options';

    $row = hooks()->apply_filters('expenses_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}
