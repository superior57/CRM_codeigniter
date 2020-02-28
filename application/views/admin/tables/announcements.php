<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'name',
    'dateadded',
    ];

$sIndexColumn = 'announcementid';
$sTable       = db_prefix().'announcements';
$where        = [];
$is_admin     = is_admin();

if (!is_admin()) {
    $where = ['AND showtostaff=1'];
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $where, [
    'announcementid',
    'showtostaff',
    '(SELECT COUNT(*) FROM '.db_prefix().'dismissed_announcements WHERE announcementid='.db_prefix().'announcements.announcementid AND staff=1 AND userid='.get_staff_user_id().') as is_dismissed'
    ]);

$output   = $result['output'];
$rResult  = $result['rResult'];
$is_admin = is_admin();
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'name') {
            if ($is_admin) {
                $_data = '<a href="' . admin_url('announcements/announcement/' . $aRow['announcementid']) . '">' . $_data . '</a>';
            } else {
                $_data = '<a href="' . admin_url('announcements/view/' . $aRow['announcementid']) . '">' . $_data . '</a>';
            }
            $_data .= '<div class="row-options">';
            $_data .= '<a href="' . admin_url('announcements/view/' . $aRow['announcementid']) . '">' . _l('view') . '</a>';

            if (!$aRow['is_dismissed'] && $aRow['showtostaff'] == '1') {
                $_data .= ' | <a href="' . admin_url('misc/dismiss_announcement/' . $aRow['announcementid']) . '"><b>' . _l('dismiss_announcement') . '</b></a>';
            }

            if (is_admin()) {
                $_data .= ' | <a href="' . admin_url('announcements/announcement/' . $aRow['announcementid']) . '">' . _l('edit') . '</a>';
                $_data .= ' | <a href="' . admin_url('announcements/delete/' . $aRow['announcementid']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }

            $_data .= '</div>';
        } elseif ($aColumns[$i] == 'dateadded') {
            $_data = _d($_data);
        }
        $row[] = $_data;
    }

    $row['DT_RowClass'] = 'has-row-options';

    if (!$aRow['is_dismissed'] && $aRow['showtostaff'] == '1') {
        $row['DT_RowClass'] .= ' alert-info';
    }

    $output['aaData'][] = $row;
}
