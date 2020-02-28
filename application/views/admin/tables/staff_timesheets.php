<?php

defined('BASEPATH') or exit('No direct script access allowed');

$v = $this->ci->db->query('SELECT VERSION() as version')->row();
// 5.6 mysql version don't have the ANY_VALUE function implemented.

  $additionalSelect = [
    db_prefix() . 'taskstimers.id',
    'task_id',
    'rel_type',
    'rel_id',
    'billed',
    'status', ];

$staffIdSelect = '';
if ($v && strpos($v->version, '5.7') !== false) {
    $staffIdSelect = 'ANY_VALUE(staff_id) as staff_id';
    foreach ($additionalSelect as $key => $column) {
        if ($key !== 0) {
            $additionalSelect[$key] = 'ANY_VALUE(' . $column . ') as ' . $column;
        } else {
            // causing errors for ambigious column
            $additionalSelect[$key] = 'ANY_VALUE(' . $column . ') as id';
        }
    }
    $aColumns = [
        'ANY_VALUE(name) as name',
        'ANY_VALUE((SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'taskstimers.id and rel_type="timesheet" ORDER by tag_order ASC)) as tags',
        'ANY_VALUE(start_time) as start_time',
        'ANY_VALUE(end_time) as end_time',
        'ANY_VALUE(note) as note',
        'ANY_VALUE(' . tasks_rel_name_select_query() . ') as rel_name',
        'ANY_VALUE(end_time - start_time) as time_h',
        'ANY_VALUE(end_time - start_time) as time_d',
        ];
} else {
    $staffIdSelect = 'staff_id';

    $aColumns = [
        'name as name',
        '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'taskstimers.id and rel_type="timesheet" ORDER by tag_order ASC) as tags',
        'start_time as start_time',
        'end_time as end_time',
        'note as note',
        tasks_rel_name_select_query() . ' as rel_name',
        'end_time - start_time as time_h',
        'end_time - start_time as time_d',
    ];
}

$time_h_column = 6;
$time_d_column = 7;

if ($view_all == true) {
    array_unshift($aColumns, $staffIdSelect);

    $time_h_column = 7;
    $time_d_column = 8;
}

if ($this->ci->input->post('group_by_task')) {
    if ($v && strpos($v->version, '5.7') !== false) {
        $aColumns[$time_h_column] = 'ANY_VALUE((SUM(end_time - start_time))) as time_h';
        $aColumns[$time_d_column] = 'ANY_VALUE((SUM(end_time - start_time))) as time_d';
    } else {
        $aColumns[$time_h_column] = 'SUM(end_time - start_time) as time_h';
        $aColumns[$time_d_column] = 'SUM(end_time - start_time) as time_d';
    }
}

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'taskstimers';

$join = [
    'LEFT JOIN ' . db_prefix() . 'tasks ON ' . db_prefix() . 'tasks.id = ' . db_prefix() . 'taskstimers.task_id',
];

$where = [];

$staff_id = false;

if ($this->ci->input->post('staff_id')) {
    $staff_id = $this->ci->input->post('staff_id');
} elseif ($view_all == false) {
    $staff_id = get_staff_user_id();
}

if ($staff_id != false) {
    $where = [
        'AND staff_id=' . $staff_id,
        ];
}

$project_ids = $this->ci->input->post('project_id');

if ($project_ids && is_array($project_ids)) {
    $project_ids = array_filter($project_ids, function ($value) {
        return $value !== '';
    });

    if (count($project_ids) > 0) {
        array_push($where, 'AND task_id IN (SELECT id FROM ' . db_prefix() . 'tasks WHERE rel_type = "project" AND rel_id  IN (' . implode(',', $project_ids) . '))');
    }
}

if ($this->ci->input->post('clientid') && !$this->ci->input->post('project_id')) {
    $customer_id = $this->ci->input->post('clientid');

    array_push($where, 'AND (
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'invoices WHERE clientid=' . $customer_id . ') AND rel_type="invoice")
                OR
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'estimates WHERE clientid=' . $customer_id . ') AND rel_type="estimate")
                OR
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'contracts WHERE client=' . $customer_id . ') AND rel_type="contract")
                OR
                ( rel_id IN (SELECT ticketid FROM ' . db_prefix() . 'tickets WHERE userid=' . $customer_id . ') AND rel_type="ticket")
                OR
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'expenses WHERE clientid=' . $customer_id . ') AND rel_type="expense")
                OR
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'proposals WHERE rel_id=' . $customer_id . ' AND rel_type="customer") AND rel_type="proposal")
                OR
                (rel_id IN (SELECT userid FROM ' . db_prefix() . 'clients WHERE userid=' . $customer_id . ') AND rel_type="customer")
                OR
                (rel_id IN (SELECT id FROM ' . db_prefix() . 'projects WHERE clientid=' . $customer_id . ') AND rel_type="project")
                )');
}

array_push($where, 'AND task_id != 0');

$filter = $this->ci->input->post('range');
if ($filter != 'period') {
    if ($filter == 'today') {
        $beginOfDay = strtotime('midnight');
        $endOfDay   = strtotime('tomorrow', $beginOfDay) - 1;
        array_push($where, ' AND start_time BETWEEN ' . $beginOfDay . ' AND ' . $endOfDay);
    } elseif ($filter == 'this_month') {
        $beginThisMonth = date('Y-m-01');
        $endThisMonth   = date('Y-m-t 23:59:59');
        array_push($where, ' AND start_time BETWEEN ' . strtotime($beginThisMonth) . ' AND ' . strtotime($endThisMonth));
    } elseif ($filter == 'last_month') {
        $beginLastMonth = date('Y-m-01', strtotime('-1 MONTH'));
        $endLastMonth   = date('Y-m-t 23:59:59', strtotime('-1 MONTH'));
        array_push($where, ' AND start_time BETWEEN ' . strtotime($beginLastMonth) . ' AND ' . strtotime($endLastMonth));
    } elseif ($filter == 'this_week') {
        $beginThisWeek = date('Y-m-d', strtotime('monday this week'));
        $endThisWeek   = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        array_push($where, ' AND start_time BETWEEN ' . strtotime($beginThisWeek) . ' AND ' . strtotime($endThisWeek));
    } elseif ($filter == 'last_week') {
        $beginLastWeek = date('Y-m-d', strtotime('monday last week'));
        $endLastWeek   = date('Y-m-d 23:59:59', strtotime('sunday last week'));
        array_push($where, ' AND start_time BETWEEN ' . strtotime($beginLastWeek) . ' AND ' . strtotime($endLastWeek));
    }
} else {
    $start_date = to_sql_date($this->ci->input->post('period-from'));
    $end_date   = to_sql_date($this->ci->input->post('period-to'));
    array_push($where, ' AND start_time BETWEEN ' . strtotime($start_date . ' 00:00:00') . ' AND ' . strtotime($end_date . ' 23:59:00'));
}

$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    $additionalSelect,
    ($this->ci->input->post('group_by_task') ? 'GROUP BY task_id' : '')
);

$output  = $result['output'];
$rResult = $result['rResult'];

$footer_data['total_logged_time_h'] = 0;
$footer_data['total_logged_time_d'] = 0;

$footer_data['chart']           = [];
$footer_data['chart']['labels'] = [];
$footer_data['chart']['data']   = [];

$temp_weekdays_data           = [];
$temp_months_data             = [];
$chart_type                   = 'today';
$chart_type_month_from_filter = false;
$weekDay                      = date('w', strtotime(date('Y-m-d H:i:s')));
if ($filter == 'today') {
    $footer_data['chart']['labels'] = [_l('today')];
} elseif ($filter == 'this_week' || $filter == 'last_week') {
    foreach (get_weekdays() as $day) {
        array_push($footer_data['chart']['labels'], $day);
    }
    $i = 0;
    foreach (get_weekdays_original() as $day) {
        if ($weekDay != '0') {
            $footer_data['chart']['labels'][$i] = date('d', strtotime($day . ' ' . str_replace('_', ' ', $filter))) . ' - ' . $footer_data['chart']['labels'][$i];
        } else {
            if ($filter == 'this_week') {
                $strtotime = 'last ' . $day;
                if ($day == 'Sunday') {
                    $strtotime = 'sunday this week';
                }
                $footer_data['chart']['labels'][$i] = date('d', strtotime($strtotime)) . ' - ' . $footer_data['chart']['labels'][$i];
            } else {
                $strtotime                          = $day . ' last week';
                $footer_data['chart']['labels'][$i] = date('d', strtotime($strtotime)) . ' - ' . $footer_data['chart']['labels'][$i];
            }
        }
        $i++;
    }

    $chart_type = 'week';
} elseif ($filter == 'this_month' || $filter == 'last_month') {
    $month      = ($filter == 'this_month') ? date('m') : date('m', strtotime('first day last month'));
    $month_year = ($filter == 'this_month') ? date('Y') : date('Y', strtotime('first day last month'));

    for ($d = 1; $d <= 31; $d++) {
        $time = mktime(12, 0, 0, $month, $d, $month_year);
        if (date('m', $time) == $month) {
            array_push($footer_data['chart']['labels'], date('Y-m-d', $time));
        }
    }
    $chart_type = 'month';
} else {
    $_start_time = new DateTime(date('Y-m-d', strtotime($start_date)));
    $_end_time   = new DateTime(date('Y-m-d', strtotime($end_date)));

    $chart_type  = 'weeks_split';
    $weeks       = get_weekdays_between_dates($_start_time, $_end_time);
    $total_weeks = count($weeks);
    for ($i = 1; $i <= $total_weeks; $i++) {
        array_push($footer_data['chart']['labels'], split_weeks_chart_label($weeks, $i));
    }
}

$chartWhere = implode(' ', $where);
$chartWhere = ltrim($chartWhere, 'AND ');

$chartData = $this->ci->db->query('SELECT end_time - start_time logged_time_h,
    end_time - start_time logged_time_d,start_time,end_time FROM ' . db_prefix() . 'taskstimers LEFT JOIN ' . db_prefix() . 'tasks ON ' . db_prefix() . 'tasks.id = ' . db_prefix() . 'taskstimers.task_id WHERE ' . trim($chartWhere))->result_array();

foreach ($chartData as $timer) {
    if ($timer['logged_time_h'] == null) {
        $footer_data['total_logged_time_h'] += (time() - $timer['start_time']);
    } else {
        $footer_data['total_logged_time_h'] += $timer['logged_time_h'];
    }

    if ($timer['logged_time_d'] == null) {
        $total_logged_time_d = time() - $timer['start_time'];
    } else {
        $total_logged_time_d = $timer['logged_time_d'];
    }
    if ($chart_type == 'today') {
        array_push($footer_data['chart']['data'], $total_logged_time_d);
    } elseif ($chart_type == 'week') {
        $weekday = date('N', $timer['start_time']);
        if (!isset($temp_weekdays_data[$weekday])) {
            $temp_weekdays_data[$weekday] = 0;
        }
        $temp_weekdays_data[$weekday] += $total_logged_time_d;
    } elseif ($chart_type == 'month') {
        $month = intval(strftime('%d', $timer['start_time']));

        if (!isset($temp_months_data[$month])) {
            $temp_months_data[$month] = 0;
        }

        $temp_months_data[$month] += $total_logged_time_d;
    } elseif ($chart_type == 'weeks_split') {
        $w = 1;
        foreach ($weeks as $week) {
            $start_time_date = strftime('%Y-%m-%d', $timer['start_time']);
            if (!isset($weeks[$w]['total'])) {
                $weeks[$w]['total'] = 0;
            }
            if (in_array($start_time_date, $week)) {
                $weeks[$w]['total'] += $total_logged_time_d;
            }
            $w++;
        }
    }
    $footer_data['total_logged_time_d'] += $total_logged_time_d;
}

foreach ($rResult as $aRow) {
    $row = [];

    if ($view_all === true) {
        $row[] = '<a href="' . admin_url('staff/member/' . $aRow['staff_id']) . '" target="_blank">' . get_staff_full_name($aRow['staff_id']) . '</a>';
    }

    $taskName = '<a href="' . admin_url('tasks/view/' . $aRow['task_id']) . '" onclick="init_task_modal(' . $aRow['task_id'] . '); return false;">' . $aRow['name'] . '</a>';

    $status = get_task_status_by_id($aRow['status']);

    $taskName .= '<span class="hidden"> - </span><span class="inline-block pull-right mright5 label" style="border:1px solid ' . $status['color'] . ';color:' . $status['color'] . '" task-status-table="' . $aRow['status'] . '">' . $status['name'] . '</span>';

    $row[] = $taskName;

    $row[] = render_tags($aRow['tags']);

    $row[] = _dt($aRow['start_time'], true);

    $endTimeOutput = ($aRow['end_time'] ? _dt($aRow['end_time'], true) : '');

    if (!$aRow['end_time'] && is_admin() && $aRow['billed'] == 0) {
        $endTimeOutput .= ' <a href="#"
        data-toggle="popover"
        data-placement="bottom"
        data-html="true"
        data-trigger="manual"
        data-title="' . _l('note') . "\"
        data-content='" . render_textarea('timesheet_note') . '
        <button type="button"
        onclick="timer_action(this, ' . $aRow['task_id'] . ', ' . $aRow['id'] . ', 1);"
        class="btn btn-info btn-xs">' . _l('save')
        . "</button>'
        class=\"text-danger\"
        onclick=\"return false;\">
                <i class=\"fa fa-clock-o\"></i> " . _l('task_stop_timer') . '
        </a>';
    }
    $row[] = $endTimeOutput;
    $row[] = $aRow['note'];

    if ($aRow['rel_name']) {
        $relName = task_rel_name($aRow['rel_name'], $aRow['rel_id'], $aRow['rel_type']);
        $link    = task_rel_link($aRow['rel_id'], $aRow['rel_type']);
        $row[]   = '<a href="' . $link . '">' . $relName . '</a>';
    } else {
        $row[] = '';
    }

    $total_logged_time = 0;
    if ($aRow['time_h'] == null) {
        $total_logged_time = time() - $aRow['start_time'];
    } else {
        $total_logged_time = $aRow['time_h'];
    }
    $row[] = seconds_to_time_format($total_logged_time);

    $total_logged_time = 0;
    if ($aRow['time_d'] == null) {
        $total_logged_time = time() - $aRow['start_time'];
    } else {
        $total_logged_time = $aRow['time_d'];
    }
    $row[] = sec2qty($total_logged_time);

    $output['aaData'][] = $row;
}

if ($chart_type == 'today') {
    $footer_data['chart']['data'] = [sec2qty(array_sum($footer_data['chart']['data']))];
} elseif ($chart_type == 'week') {
    ksort($temp_weekdays_data);
    for ($i = 1; $i <= 7; $i++) {
        $total_logged_time = 0;
        if (isset($temp_weekdays_data[$i])) {
            $total_logged_time = $temp_weekdays_data[$i];
        }
        array_push($footer_data['chart']['data'], sec2qty($total_logged_time));
    }
} elseif ($chart_type == 'month') {
    ksort($temp_months_data);

    for ($i = 1; $i <= 31; $i++) {
        $total_logged_time = 0;
        if (isset($temp_months_data[$i])) {
            $total_logged_time = $temp_months_data[$i];
        }
        array_push($footer_data['chart']['data'], sec2qty($total_logged_time));
    }
} elseif ($chart_type == 'weeks_split') {
    foreach ($weeks as $week) {
        $total = 0;
        if (isset($week['total'])) {
            $total = $week['total'];
        }
        $total_logged_time = $total;
        array_push($footer_data['chart']['data'], sec2qty($total_logged_time));
    }
}

$output['chart']      = $footer_data['chart'];
$output['chart_type'] = $chart_type;
unset($footer_data['chart']);

$footer_data['total_logged_time_h'] = seconds_to_time_format($footer_data['total_logged_time_h']);
$footer_data['total_logged_time_d'] = sec2qty($footer_data['total_logged_time_d']);

$output['logged_time'] = $footer_data;
