<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns         = ['name', 'start_date', 'deadline', 'status'];
$sIndexColumn     = 'id';
$sTable           = db_prefix() . 'projects';
$additionalSelect = ['id'];
$join             = [
    'JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'projects.clientid',
    ];

$where    = [];
$staff_id = get_staff_user_id();
if ($this->ci->input->post('staff_id')) {
    $staff_id = $this->ci->input->post('staff_id');
} else {
    // Request from dashboard, finished and canceled not need to be shown
    array_push($where, ' AND status != 4 AND status != 5');
}
array_push($where, ' AND ' . db_prefix() . 'projects.id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . $staff_id . ')');

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0 ; $i < count($aColumns) ; $i++) {
        $_data = $aRow[ $aColumns[$i] ];

        if ($aColumns[$i] == 'start_date' || $aColumns[$i] == 'deadline') {
            $_data = _d($_data);
        } elseif ($aColumns[$i] == 'name') {
            $_data = '<a href="' . admin_url('projects/view/' . $aRow['id']) . '">' . $_data . '</a>';
        } elseif ($aColumns[$i] == 'status') {
            $status = get_project_status_by_id($_data);
            $status = '<span class="label label inline-block project-status-' . $_data . '" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '">' . $status['name'] . '</span>';
            $_data  = $status;
        }

        $row[] = $_data;
    }
    $output['aaData'][] = $row;
}
