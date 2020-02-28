<?php

defined('BASEPATH') or exit('No direct script access allowed');

$hasPermissionEdit   = has_permission('tasks', '', 'edit');
$hasPermissionDelete = has_permission('tasks', '', 'delete');
$tasksPriorities     = get_tasks_priorities();

$aColumns = [
    '1', // bulk actions
    db_prefix() . 'tasks.id as id',
    db_prefix() . 'tasks.name as task_name',
    'status',
    'startdate',
    'duedate',
     get_sql_select_task_asignees_full_names() . ' as assignees',
    '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'tasks.id and rel_type="task" ORDER by tag_order ASC) as tags',
    'priority',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'tasks';

$where = [];
include_once(APPPATH . 'views/admin/tables/includes/tasks_filter.php');

if (!$this->ci->input->post('tasks_related_to')) {
    array_push($where, 'AND rel_id="' . $rel_id . '" AND rel_type="' . $rel_type . '"');
} else {
    // Used in the customer profile filters
    $tasks_related_to = explode(',', $this->ci->input->post('tasks_related_to'));
    $rel_to_query     = 'AND (';

    $lastElement = end($tasks_related_to);
    foreach ($tasks_related_to as $rel_to) {
        if ($rel_to == 'invoice') {
            $rel_to_query .= '(rel_id IN (SELECT id FROM ' . db_prefix() . 'invoices WHERE clientid=' . $rel_id . ')';
        } elseif ($rel_to == 'estimate') {
            $rel_to_query .= '(rel_id IN (SELECT id FROM ' . db_prefix() . 'estimates WHERE clientid=' . $rel_id . ')';
        } elseif ($rel_to == 'contract') {
            $rel_to_query .= '(rel_id IN (SELECT id FROM ' . db_prefix() . 'contracts WHERE client=' . $rel_id . ')';
        } elseif ($rel_to == 'ticket') {
            $rel_to_query .= '(rel_id IN (SELECT ticketid FROM ' . db_prefix() . 'tickets WHERE userid=' . $rel_id . ')';
        } elseif ($rel_to == 'expense') {
            $rel_to_query .= '(rel_id IN (SELECT id FROM ' . db_prefix() . 'expenses WHERE clientid=' . $rel_id . ')';
        } elseif ($rel_to == 'proposal') {
            $rel_to_query .= '(rel_id IN (SELECT id FROM ' . db_prefix() . 'proposals WHERE rel_type=' . $rel_id . ' AND rel_type="customer")';
        } elseif ($rel_to == 'customer') {
            $rel_to_query .= '(rel_id IN (SELECT userid FROM ' . db_prefix() . 'clients WHERE userid=' . $rel_id . ')';
        } elseif ($rel_to == 'project') {
            $rel_to_query .= '(rel_id IN (SELECT id FROM ' . db_prefix() . 'projects WHERE clientid=' . $rel_id . ')';
        }

        $rel_to_query .= ' AND rel_type="' . $rel_to . '")';
        if ($rel_to != $lastElement) {
            $rel_to_query .= ' OR ';
        }
    }

    $rel_to_query .= ')';
    array_push($where, $rel_to_query);
}

$join = [];

$custom_fields = get_table_custom_fields('tasks');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, '(SELECT value FROM ' . db_prefix() . 'customfieldsvalues WHERE ' . db_prefix() . 'customfieldsvalues.relid=' . db_prefix() . 'tasks.id AND ' . db_prefix() . 'customfieldsvalues.fieldid=' . $field['id'] . ' AND ' . db_prefix() . 'customfieldsvalues.fieldto="' . $field['fieldto'] . '" LIMIT 1) as ' . $selectAs);
}

$aColumns = hooks()->apply_filters('tasks_related_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'billed',
    'recurring',
    '(SELECT staffid FROM ' . db_prefix() . 'task_assigned WHERE taskid=' . db_prefix() . 'tasks.id AND staffid=' . get_staff_user_id() . ') as is_assigned',
     get_sql_select_task_assignees_ids() . ' as assignees_ids',
     '(SELECT MAX(id) FROM ' . db_prefix() . 'taskstimers WHERE task_id=' . db_prefix() . 'tasks.id and staff_id=' . get_staff_user_id() . ' and end_time IS NULL) as not_finished_timer_by_current_staff',
       '(SELECT staffid FROM ' . db_prefix() . 'task_assigned WHERE taskid=' . db_prefix() . 'tasks.id AND staffid=' . get_staff_user_id() . ') as current_user_is_assigned',
        '(SELECT CASE WHEN addedfrom=' . get_staff_user_id() . ' AND is_added_from_contact=0 THEN 1 ELSE 0 END) as current_user_is_creator',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['id'] . '"><label></label></div>';

    $row[] = '<a href="' . admin_url('tasks/view/' . $aRow['id']) . '" onclick="init_task_modal(' . $aRow['id'] . '); return false;">' . $aRow['id'] . '</a>';

    $outputName = '';

    if ($aRow['not_finished_timer_by_current_staff']) {
        $outputName .= '<span class="pull-left text-danger"><i class="fa fa-clock-o fa-fw"></i></span>';
    }

    $outputName .= '<a href="' . admin_url('tasks/view/' . $aRow['id']) . '" class="display-block main-tasks-table-href-name" onclick="init_task_modal(' . $aRow['id'] . '); return false;">' . $aRow['task_name'] . '</a>';

    if ($aRow['recurring'] == 1) {
        $outputName .= '<span class="label label-primary inline-block mtop4"> ' . _l('recurring_task') . '</span>';
    }

    $outputName .= '<div class="row-options">';

    $class = 'text-success bold';
    $style = '';

    $tooltip = '';
    if ($aRow['billed'] == 1 || !$aRow['is_assigned'] || $aRow['status'] == Tasks_model::STATUS_COMPLETE) {
        $class = 'text-dark disabled';
        $style = 'style="opacity:0.6;cursor: not-allowed;"';
        if ($aRow['status'] == Tasks_model::STATUS_COMPLETE) {
            $tooltip = ' data-toggle="tooltip" data-title="' . format_task_status($aRow['status'], false, true) . '"';
        } elseif ($aRow['billed'] == 1) {
            $tooltip = ' data-toggle="tooltip" data-title="' . _l('task_billed_cant_start_timer') . '"';
        } elseif (!$aRow['is_assigned']) {
            $tooltip = ' data-toggle="tooltip" data-title="' . _l('task_start_timer_only_assignee') . '"';
        }
    }

    if ($aRow['not_finished_timer_by_current_staff']) {
        $outputName .= '<a href="#" class="text-danger tasks-table-stop-timer" onclick="timer_action(this,' . $aRow['id'] . ',' . $aRow['not_finished_timer_by_current_staff'] . '); return false;">' . _l('task_stop_timer') . '</a>';
    } else {
        $outputName .= '<span' . $tooltip . ' ' . $style . '>
        <a href="#" class="' . $class . ' tasks-table-start-timer" onclick="timer_action(this,' . $aRow['id'] . '); return false;">' . _l('task_start_timer') . '</a>
        </span>';
    }

    if ($hasPermissionEdit) {
        $outputName .= '<span class="text-dark"> | </span><a href="#" onclick="edit_task(' . $aRow['id'] . '); return false">' . _l('edit') . '</a>';
    }

    if ($hasPermissionDelete) {
        $outputName .= '<span class="text-dark"> | </span><a href="' . admin_url('tasks/delete_task/' . $aRow['id']) . '" class="text-danger _delete task-delete">' . _l('delete') . '</a>';
    }
    $outputName .= '</div>';

    $row[]           = $outputName;
    $canChangeStatus = ($aRow['current_user_is_creator'] != '0' || $aRow['current_user_is_assigned'] || has_permission('tasks', '', 'edit'));
    $status          = get_task_status_by_id($aRow['status']);
    $outputStatus    = '';

    $outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $aRow['status'] . '">';

    $outputStatus .= $status['name'];

    /*  if ($aRow['status'] == Tasks_model::STATUS_COMPLETE && $canChangeStatus) {
       $outputStatus .= '<a href="#" onclick="unmark_complete(' . $aRow['id'] . '); return false;"><i class="fa fa-check task-icon task-finished-icon" data-toggle="tooltip" title="' . _l('task_unmark_as_complete') . '"></i></a>';
    } else {
       if ($canChangeStatus) {
           $outputStatus .= '<a href="#" onclick="mark_complete(' . $aRow['id'] . '); return false;"><i class="fa fa-check task-icon task-unfinished-icon" data-toggle="tooltip" title="' . _l('task_single_mark_as_complete') . '"></i></a>';
       }
    }
*/

    if ($canChangeStatus) {
        $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        $outputStatus .= '<a href="#" style="font-size:14px;vertical-align:middle;" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        $outputStatus .= '</a>';

        $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $aRow['id'] . '">';
        foreach ($task_statuses as $taskChangeStatus) {
            if ($aRow['status'] != $taskChangeStatus['id']) {
                $outputStatus .= '<li>
                  <a href="#" onclick="task_mark_as(' . $taskChangeStatus['id'] . ',' . $aRow['id'] . '); return false;">
                     ' . _l('task_mark_as', $taskChangeStatus['name']) . '
                  </a>
               </li>';
            }
        }
        $outputStatus .= '</ul>';
        $outputStatus .= '</div>';
    }

    $outputStatus .= '</span>';

    $row[] = $outputStatus;
    $row[] = _d($aRow['startdate']);

    $row[] = _d($aRow['duedate']);

    $row[] = format_members_by_ids_and_names($aRow['assignees_ids'], $aRow['assignees']);

    $row[] = render_tags($aRow['tags']);

    $outputPriority = '<span style="color:' . task_priority_color($aRow['priority']) . ';" class="inline-block">' . task_priority($aRow['priority']);

    if (has_permission('tasks', '', 'edit') && $aRow['status'] != Tasks_model::STATUS_COMPLETE) {
        $outputPriority .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        $outputPriority .= '<a href="#" style="font-size:14px;vertical-align:middle;" class="dropdown-toggle text-dark" id="tableTaskPriority-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $outputPriority .= '<span data-toggle="tooltip" title="' . _l('task_single_priority') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        $outputPriority .= '</a>';

        $outputPriority .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskPriority-' . $aRow['id'] . '">';
        foreach ($tasksPriorities as $priority) {
            if ($aRow['priority'] != $priority['id']) {
                $outputPriority .= '<li>
                  <a href="#" onclick="task_change_priority(' . $priority['id'] . ',' . $aRow['id'] . '); return false;">
                     ' . $priority['name'] . '
                  </a>
               </li>';
            }
        }
        $outputPriority .= '</ul>';
        $outputPriority .= '</div>';
    }

    $outputPriority .= '</span>';
    $row[] = $outputPriority;

    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }


    $row['DT_RowClass'] = 'has-row-options';

    if ((!empty($aRow['duedate']) && $aRow['duedate'] < date('Y-m-d')) && $aRow['status'] != Tasks_model::STATUS_COMPLETE) {
        $row['DT_RowClass'] .= ' text-danger';
    }

    $row = hooks()->apply_filters('tasks_related_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}
