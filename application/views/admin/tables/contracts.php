<?php

defined('BASEPATH') or exit('No direct script access allowed');

$base_currency = get_base_currency();

$aColumns = [
    db_prefix() . 'contracts.id as id',
    'subject',
    get_sql_select_client_company(),
    db_prefix() . 'contracts_types.name as type_name',
    'contract_value',
    'datestart',
    'dateend',
    'signature',
    ];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'contracts';

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'contracts.client',
    'LEFT JOIN ' . db_prefix() . 'contracts_types ON ' . db_prefix() . 'contracts_types.id = ' . db_prefix() . 'contracts.contract_type',
];

$custom_fields = get_table_custom_fields('contracts');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);

    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'contracts.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$where  = [];
$filter = [];

if ($this->ci->input->post('exclude_trashed_contracts')) {
    array_push($filter, 'AND trash = 0');
}

if ($this->ci->input->post('trash')) {
    array_push($filter, 'AND trash = 1');
}

if ($this->ci->input->post('expired')) {
    array_push($filter, 'AND dateend IS NOT NULL AND dateend <"' . date('Y-m-d') . '" and trash = 0');
}

if ($this->ci->input->post('without_dateend')) {
    array_push($filter, 'AND dateend IS NULL AND trash = 0');
}

$types    = $this->ci->contracts_model->get_contract_types();
$typesIds = [];
foreach ($types as $type) {
    if ($this->ci->input->post('contracts_by_type_' . $type['id'])) {
        array_push($typesIds, $type['id']);
    }
}

if (count($typesIds) > 0) {
    array_push($filter, 'AND contract_type IN (' . implode(', ', $typesIds) . ')');
}

$years      = $this->ci->contracts_model->get_contracts_years();
$yearsArray = [];
foreach ($years as $year) {
    if ($this->ci->input->post('year_' . $year['year'])) {
        array_push($yearsArray, $year['year']);
    }
}
if (count($yearsArray) > 0) {
    array_push($filter, 'AND YEAR(datestart) IN (' . implode(', ', $yearsArray) . ')');
}

$monthArray = [];
for ($m = 1; $m <= 12; $m++) {
    if ($this->ci->input->post('contracts_by_month_' . $m)) {
        array_push($monthArray, $m);
    }
}

if (count($monthArray) > 0) {
    array_push($filter, 'AND MONTH(datestart) IN (' . implode(', ', $monthArray) . ')');
}

if (count($filter) > 0) {
    array_push($where, 'AND (' . prepare_dt_filter($filter) . ')');
}

if ($clientid != '') {
    array_push($where, 'AND client=' . $clientid);
}

if (!has_permission('contracts', '', 'view')) {
    array_push($where, 'AND ' . db_prefix() . 'contracts.addedfrom=' . get_staff_user_id());
}

$aColumns = hooks()->apply_filters('contracts_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'contracts.id', 'trash', 'client', 'hash']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = $aRow['id'];

    $subjectOutput = '<a href="' . admin_url('contracts/contract/' . $aRow['id']) . '">' . $aRow['subject'] . '</a>';
    if ($aRow['trash'] == 1) {
        $subjectOutput .= '<span class="label label-danger pull-right">' . _l('contract_trash') . '</span>';
    }

    $subjectOutput .= '<div class="row-options">';

    $subjectOutput .= '<a href="' . site_url('contract/' . $aRow['id'] . '/' . $aRow['hash']) . '" target="_blank">' . _l('view') . '</a>';

    if (has_permission('contracts', '', 'edit')) {
        $subjectOutput .= ' | <a href="' . admin_url('contracts/contract/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }

    if (has_permission('contracts', '', 'delete')) {
        $subjectOutput .= ' | <a href="' . admin_url('contracts/delete/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    }

    $subjectOutput .= '</div>';
    $row[] = $subjectOutput;

    $row[] = '<a href="' . admin_url('clients/client/' . $aRow['client']) . '">' . $aRow['company'] . '</a>';

    $row[] = $aRow['type_name'];

    $row[] = app_format_money($aRow['contract_value'], $base_currency);

    $row[] = _d($aRow['datestart']);

    $row[] = _d($aRow['dateend']);

    if (!empty($aRow['signature'])) {
        $row[] = '<span class="text-success">' . _l('is_signed') . '</span>';
    } else {
        $row[] = '<span class="text-muted">' . _l('is_not_signed') . '</span>';
    }

    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }


    if (!empty($aRow['dateend'])) {
        $_date_end = date('Y-m-d', strtotime($aRow['dateend']));
        if ($_date_end < date('Y-m-d')) {
            $row['DT_RowClass'] = 'alert-danger';
        }
    }

    if (isset($row['DT_RowClass'])) {
        $row['DT_RowClass'] .= ' has-row-options';
    } else {
        $row['DT_RowClass'] = 'has-row-options';
    }

    $row = hooks()->apply_filters('contracts_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}
