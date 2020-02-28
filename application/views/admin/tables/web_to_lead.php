<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = ['id', 'name', '(SELECT COUNT(id) FROM '.db_prefix().'leads WHERE '.db_prefix().'leads.from_form_id = '.db_prefix().'web_to_lead.id)', 'dateadded'];

$sIndexColumn = 'id';
$sTable       = db_prefix().'web_to_lead';

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], ['form_key', 'id']);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0 ; $i < count($aColumns) ; $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'name') {
            $_data = '<a href="' . admin_url('leads/form/' . $aRow['id']) . '">' . $_data . '</a>';
            $_data .= '<div class="row-options">';
            $_data .= '<a href="' . site_url('forms/wtl/' . $aRow['form_key']) . '" target="_blank">' . _l('view') . '</a>';
            $_data .= ' | <a href="' . admin_url('leads/form/' . $aRow['id']) . '">' . _l('edit') . '</a>';
            $_data .= ' | <a href="' . admin_url('leads/delete_form/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            $_data .= '</div>';
        } elseif ($aColumns[$i] == 'dateadded') {
            $_data = '<span class="text-has-action is-date" data-toggle="tooltip" data-title="' . _dt($_data) . '">' . time_ago($_data) . '</span>';
        }

        $row[] = $_data;
    }
    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
