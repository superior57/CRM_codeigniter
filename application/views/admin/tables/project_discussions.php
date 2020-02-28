<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'subject',
    'last_activity',
    '(SELECT COUNT(*) FROM '.db_prefix().'projectdiscussioncomments WHERE discussion_id = '.db_prefix().'projectdiscussions.id AND discussion_type="regular")',
    'show_to_customer',
    ];
$sIndexColumn = 'id';
$sTable       = db_prefix().'projectdiscussions';
$result       = data_tables_init($aColumns, $sIndexColumn, $sTable, [], ['AND project_id=' . $project_id], [
    'id',
    'description',
    ]);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'subject') {
            $_data = '<a href="' . admin_url('projects/view/' . $project_id . '?group=project_discussions&discussion_id=' . $aRow['id']) . '">' . $_data . '</a>';
            if (has_permission('projects', '', 'edit') || has_permission('projects', '', 'delete')) {
                $_data .= '<div class="row-options">';
                if (has_permission('projects', '', 'edit')) {
                    $_data .= '<a href="#" onclick="edit_discussion(this,' . $aRow['id'] . '); return false;" data-subject="'.$aRow['subject'].'" data-description="'.htmlentities(clear_textarea_breaks($aRow['description'])).'" data-show-to-customer="'.$aRow['show_to_customer'].'">'._l('edit').'</a>';
                }
                if (has_permission('projects', '', 'delete')) {
                     $_data .= (has_permission('projects', '', 'edit') ? ' | ' : '') . '<a href="#" onclick="delete_project_discussion(' . $aRow['id'] . '); return false;" class="text-danger">'._l('delete').'</a>';
                }
                $_data .= '</div>';
            }
        } elseif ($aColumns[$i] == 'show_to_customer') {
            if ($_data == 1) {
                $_data = _l('project_discussion_visible_to_customer_yes');
            } else {
                $_data = _l('project_discussion_visible_to_customer_no');
            }
        } elseif ($aColumns[$i] == 'last_activity') {
            if (!is_null($_data)) {
                $_data = '<span class="text-has-action is-date" data-toggle="tooltip" data-title="' . _dt($_data) . '">' . time_ago($_data) . '</span>';
            } else {
                $_data = _l('project_discussion_no_activity');
            }
        }
        $row[] = $_data;
    }

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
