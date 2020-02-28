<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'description',
    'date',
    'staff',
    'isnotified',
    ];

$sIndexColumn = 'id';
$sTable       = db_prefix().'reminders';
$where        = [
    'AND rel_id=' . $id . ' AND rel_type="' . $rel_type . '"',
    ];
$join = [
    'JOIN '.db_prefix().'staff ON '.db_prefix().'staff.staffid = '.db_prefix().'reminders.staff',
    ];
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'firstname',
    'lastname',
    'id',
    'creator',
    'rel_type',
    ]);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'staff') {
            $_data = '<a href="' . admin_url('staff/profile/' . $aRow['staff']) . '">' . staff_profile_image($aRow['staff'], [
                'staff-profile-image-small',
                ]) . ' ' . $aRow['firstname'] . ' ' . $aRow['lastname'] . '</a>';
        } elseif ($aColumns[$i] == 'description') {
            if ($aRow['creator'] == get_staff_user_id() || is_admin()) {
                $_data .= '<div class="row-options">';
                if ($aRow['isnotified'] == 0) {
                    $_data .= '<a href="#" onclick="edit_reminder(' . $aRow['id'] . ',this); return false;" class="edit-reminder">' . _l('edit') . '</a> | ';
                }
                $_data .= '<a href="' . admin_url('misc/delete_reminder/' . $id . '/' . $aRow['id'] . '/' . $aRow['rel_type']) . '" class="text-danger delete-reminder">' . _l('delete') . '</a>';
                $_data .= '</div>';
            }
        } elseif ($aColumns[$i] == 'isnotified') {
            if ($_data == 1) {
                $_data = _l('reminder_is_notified_boolean_yes');
            } else {
                $_data = _l('reminder_is_notified_boolean_no');
            }
        } elseif ($aColumns[$i] == 'date') {
            $_data = _dt($_data);
        }
        $row[] = $_data;
    }
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
