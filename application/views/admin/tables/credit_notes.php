<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'number',
    'date',
    get_sql_select_client_company(),
    db_prefix() . 'creditnotes.status as status',
    db_prefix() . 'projects.name as project_name',
    'reference_no',
    'total',
    '(SELECT ' . db_prefix() . 'creditnotes.total - (
      (SELECT COALESCE(SUM(amount),0) FROM ' . db_prefix() . 'credits WHERE ' . db_prefix() . 'credits.credit_id=' . db_prefix() . 'creditnotes.id)
      +
      (SELECT COALESCE(SUM(amount),0) FROM ' . db_prefix() . 'creditnote_refunds WHERE ' . db_prefix() . 'creditnote_refunds.credit_note_id=' . db_prefix() . 'creditnotes.id)
      )
    ) as remaining_amount',
    ];

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'creditnotes.clientid',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'creditnotes.currency',
    'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'creditnotes.project_id',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'creditnotes';

$custom_fields = get_table_custom_fields('credit_note');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'creditnotes.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$where  = [];
$filter = [];

if ($clientid != '') {
    array_push($where, 'AND ' . db_prefix() . 'creditnotes.clientid=' . $clientid);
}

if (!has_permission('credit_notes', '', 'view')) {
    array_push($where, 'AND ' . db_prefix() . 'creditnotes.addedfrom=' . get_staff_user_id());
}

$project_id = $this->ci->input->get('project_id');
if ($project_id) {
    array_push($where, 'AND project_id=' . $project_id);
}

$statuses  = $this->ci->credit_notes_model->get_statuses();
$statusIds = [];

foreach ($statuses as $status) {
    if ($this->ci->input->post('credit_notes_status_' . $status['id'])) {
        array_push($statusIds, $status['id']);
    }
}

if (count($statusIds) > 0) {
    array_push($filter, 'AND ' . db_prefix() . 'creditnotes.status IN (' . implode(', ', $statusIds) . ')');
}

$years      = $this->ci->credit_notes_model->get_credits_years();
$yearsArray = [];

foreach ($years as $year) {
    if ($this->ci->input->post('year_' . $year['year'])) {
        array_push($yearsArray, $year['year']);
    }
}

if (count($yearsArray) > 0) {
    array_push($filter, 'AND YEAR(date) IN (' . implode(', ', $yearsArray) . ')');
}

if (count($filter) > 0) {
    array_push($where, 'AND (' . prepare_dt_filter($filter) . ')');
}

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'creditnotes.id',
    db_prefix() . 'creditnotes.clientid',
    db_prefix(). 'currencies.name as currency_name',
    'project_id',
    'deleted_customer_name',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $numberOutput = '';
    // If is from client area table
    if (is_numeric($clientid) || $project_id) {
        $numberOutput = '<a href="' . admin_url('credit_notes/list_credit_notes/' . $aRow['id']) . '" target="_blank">' . format_credit_note_number($aRow['id']) . '</a>';
    } else {
        $numberOutput = '<a href="' . admin_url('credit_notes/list_credit_notes/' . $aRow['id']) . '" onclick="init_credit_note(' . $aRow['id'] . '); return false;">' . format_credit_note_number($aRow['id']) . '</a>';
    }

    $numberOutput .= '<div class="row-options">';

    if (has_permission('credit_notes', '', 'edit')) {
        $numberOutput .= '<a href="' . admin_url('credit_notes/credit_note/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }
    $numberOutput .= '</div>';

    $row[] = $numberOutput;

    $row[] = _d($aRow['date']);

    if (empty($aRow['deleted_customer_name'])) {
        $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '">' . $aRow['company'] . '</a>';
    } else {
        $row[] = $aRow['deleted_customer_name'];
    }

    $row[] = format_credit_note_status($aRow['status']);

    $row[] = '<a href="' . admin_url('projects/view/' . $aRow['project_id']) . '">' . $aRow['project_name'] . '</a>';

    $row[] = $aRow['reference_no'];

    $row[] = app_format_money($aRow['total'], $aRow['currency_name']);

    $row[] = app_format_money($aRow['remaining_amount'], $aRow['currency_name']);

    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $output['aaData'][] = $row;
}

echo json_encode($output);
die();
