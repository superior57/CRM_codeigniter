<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'name',
    'due_date',
    ];
$sIndexColumn = 'id';
$sTable       = db_prefix().'milestones';
$where        = [
    'AND project_id=' . $project_id,
    ];
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $where, [
    'id',
    'milestone_order',
    'description',
    'description_visible_to_customer',
    ]);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'name') {
            if (has_permission('projects', '', 'edit')) {
                $_data = '<a href="#" onclick="edit_milestone(this,' . $aRow['id'] . '); return false" data-name="' . $aRow['name'] . '" data-due_date="' . _d($aRow['due_date']) . '" data-order="' . $aRow['milestone_order'] . '" data-description="' . htmlspecialchars(clear_textarea_breaks($aRow['description'])) . '" data-description-visible-to-customer="' . $aRow['description_visible_to_customer'] . '">' . $_data . '</a>';
            }

            if (has_permission('projects', '', 'delete')) {
                $_data .= '<div class="row-options">';
                $_data .= '<a href="' . admin_url('projects/delete_milestone/' . $project_id . '/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
                $_data .= '</div>';
            }
        } elseif ($aColumns[$i] == 'due_date') {
            $_data = _d($_data);
            if (date('Y-m-d') > $aRow['due_date'] && total_rows(db_prefix().'tasks', [
                'milestone' => $aRow['id'],
                'status !=' => 5,
                'rel_id' => $project_id,
                'rel_type' => 'project',
                ]) > 0) {
                $_data .= ' <span class="label label-danger mleft5 inline-block">' . _l('project_milestone_duedate_passed') . '</span>';
            }
        }
        $row[] = $_data;
    }


    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
