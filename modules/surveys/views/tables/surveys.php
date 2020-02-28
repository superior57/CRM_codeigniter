<?php

defined('BASEPATH') or exit('No direct script access allowed');
$aColumns = [
    'surveyid',
    'subject',
    '(SELECT count(questionid) FROM ' . db_prefix() . 'form_questions WHERE ' . db_prefix() . 'form_questions.rel_id = ' . db_prefix() . 'surveys.surveyid AND rel_type="survey")',
    '(SELECT count(resultsetid) FROM ' . db_prefix() . 'surveyresultsets WHERE ' . db_prefix() . 'surveyresultsets.surveyid = ' . db_prefix() . 'surveys.surveyid)',
    'datecreated',
    'active',
];
$sIndexColumn = 'surveyid';
$sTable       = db_prefix() . 'surveys';
$result       = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], [
    'hash',
]);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'subject') {
            $_data = '<a href="' . admin_url('surveys/survey/' . $aRow['surveyid']) . '">' . $_data . '</a>';

            $_data .= '<div class="row-options">';

            $_data .= '<a href="' . site_url('survey/' . $aRow['surveyid'] . '/' . $aRow['hash']) . '" target="_blank">' . _l('survey_list_view_tooltip') . '</a>';

            if (total_rows(db_prefix() . 'surveyresultsets', 'surveyid=' . $aRow['surveyid']) > 0) {
                $_data .= ' | <a href="' . admin_url('surveys/results/' . $aRow['surveyid']) . '">' . _l('survey_list_view_results_tooltip') . '</a>';
            }

            $_data .= ' | <a href="' . admin_url('surveys/survey/' . $aRow['surveyid']) . '">' . _l('edit') . '</a>';

            if (has_permission('surveys', '', 'delete')) {
                $_data .= ' | <a href="' . admin_url('surveys/delete/' . $aRow['surveyid']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }

            $_data .= '</div>';
        } elseif ($aColumns[$i] == 'datecreated') {
            $_data = _dt($_data);
        } elseif ($aColumns[$i] == 'active') {
            $checked = '';
            if ($aRow['active'] == 1) {
                $checked = 'checked';
            }

            $_data = '<div class="onoffswitch">
                <input type="checkbox" data-switch-url="' . admin_url() . 'surveys/change_survey_status" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['surveyid'] . '" data-id="' . $aRow['surveyid'] . '" ' . $checked . '>
                <label class="onoffswitch-label" for="c_' . $aRow['surveyid'] . '"></label>
            </div>';

            // For exporting
            $_data .= '<span class="hide">' . ($checked == 'checked' ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';
        }
        $row[] = $_data;
    }
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
