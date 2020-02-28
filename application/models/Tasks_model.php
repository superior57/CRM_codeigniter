<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Tasks_model extends App_Model
{
    const STATUS_NOT_STARTED = 1;

    const STATUS_AWAITING_FEEDBACK = 2;

    const STATUS_TESTING = 3;

    const STATUS_IN_PROGRESS = 4;

    const STATUS_COMPLETE = 5;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
        $this->load->model('staff_model');
    }

    // Not used?
    public function get_user_tasks_assigned()
    {
        $this->db->where('id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . get_staff_user_id() . ')');
        $this->db->where('status !=', 5);
        $this->db->order_by('duedate', 'asc');

        return $this->db->get(db_prefix() . 'tasks')->result_array();
    }

    public function get_statuses()
    {
        $statuses = hooks()->apply_filters('before_get_task_statuses', [
            [
                'id'             => self::STATUS_NOT_STARTED,
                'color'          => '#989898',
                'name'           => _l('task_status_1'),
                'order'          => 1,
                'filter_default' => true,
                ],
             [
                'id'             => self::STATUS_IN_PROGRESS,
                'color'          => '#03A9F4',
                'name'           => _l('task_status_4'),
                'order'          => 2,
                'filter_default' => true,
                ],
             [
                'id'             => self::STATUS_TESTING,
                'color'          => '#2d2d2d',
                'name'           => _l('task_status_3'),
                'order'          => 3,
                'filter_default' => true,
                ],
              [
                'id'             => self::STATUS_AWAITING_FEEDBACK,
                'color'          => '#adca65',
                'name'           => _l('task_status_2'),
                'order'          => 4,
                'filter_default' => true,
                ],
            [
                'id'             => self::STATUS_COMPLETE,
                'color'          => '#84c529',
                'name'           => _l('task_status_5'),
                'order'          => 100,
                'filter_default' => false,
                ],
            ]);

        usort($statuses, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $statuses;
    }

    /**
     * Get task by id
     * @param  mixed $id task id
     * @return object
     */
    public function get($id, $where = [])
    {
        $is_admin = is_admin();
        $this->db->where('id', $id);
        $this->db->where($where);
        $task = $this->db->get(db_prefix() . 'tasks')->row();
        if ($task) {
            $task->comments      = $this->get_task_comments($id);
            $task->assignees     = $this->get_task_assignees($id);
            $task->assignees_ids = [];

            foreach ($task->assignees as $follower) {
                array_push($task->assignees_ids, $follower['assigneeid']);
            }

            $task->followers     = $this->get_task_followers($id);
            $task->followers_ids = [];
            foreach ($task->followers as $follower) {
                array_push($task->followers_ids, $follower['followerid']);
            }

            $task->attachments     = $this->get_task_attachments($id);
            $task->timesheets      = $this->get_timesheeets($id);
            $task->checklist_items = $this->get_checklist_items($id);

            if (is_staff_logged_in()) {
                $task->current_user_is_assigned = $this->is_task_assignee(get_staff_user_id(), $id);
                $task->current_user_is_creator  = $this->is_task_creator(get_staff_user_id(), $id);
            }

            $task->milestone_name = '';

            if ($task->rel_type == 'project') {
                $task->project_data = $this->projects_model->get($task->rel_id);
                if ($task->milestone != 0) {
                    $milestone = $this->get_milestone($task->milestone);
                    if ($milestone) {
                        $task->milestone_name = $milestone->name;
                    }
                }
            }
        }

        return hooks()->apply_filters('get_task', $task);
    }

    public function get_milestone($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'milestones')->row();
    }

    public function do_kanban_query($status, $search = '', $page = 1, $count = false, $where = [])
    {
        $tasks_where = '';
        if (!has_permission('tasks', '', 'view')) {
            $tasks_where = get_tasks_where_string(false);
        }

        $this->db->select('id,name,duedate,startdate,status,' . get_sql_select_task_total_checklist_items() . ',' . get_sql_select_task_total_finished_checklist_items() . ',(SELECT COUNT(id) FROM ' . db_prefix() . 'task_comments WHERE taskid=' . db_prefix() . 'tasks.id) as total_comments,(SELECT COUNT(id) FROM ' . db_prefix() . 'files WHERE rel_id=' . db_prefix() . 'tasks.id AND rel_type="task") as total_files,' . get_sql_select_task_asignees_full_names() . ' as assignees' . ',' . get_sql_select_task_assignees_ids() . ' as assignees_ids,(SELECT staffid FROM ' . db_prefix() . 'task_assigned WHERE taskid=' . db_prefix() . 'tasks.id AND staffid=' . get_staff_user_id() . ') as current_user_is_assigned, (SELECT CASE WHEN addedfrom=' . get_staff_user_id() . ' AND is_added_from_contact=0 THEN 1 ELSE 0 END) as current_user_is_creator');

        $this->db->from(db_prefix() . 'tasks');
        $this->db->where('status', $status);

        $this->db->where($where);

        if ($tasks_where != '') {
            $this->db->where($tasks_where);
        }

        if ($search != '') {
            if (!startsWith($search, '#')) {
                $this->db->where('(' . db_prefix() . 'tasks.name LIKE "%' . $search . '%" OR ' . db_prefix() . 'tasks.description LIKE "%' . $search . '%")');
            } else {
                $this->db->where(db_prefix() . 'tasks.id IN
                (SELECT rel_id FROM ' . db_prefix() . 'taggables WHERE tag_id IN
                (SELECT id FROM ' . db_prefix() . 'tags WHERE name="' . strafter($search, '#') . '")
                AND ' . db_prefix() . 'taggables.rel_type=\'task\' GROUP BY rel_id HAVING COUNT(tag_id) = 1)
                ');
            }
        }

        $this->db->order_by('kanban_order', 'asc');

        if ($count == false) {
            if ($page > 1) {
                $page--;
                $position = ($page * get_option('tasks_kanban_limit'));
                $this->db->limit(get_option('tasks_kanban_limit'), $position);
            } else {
                $this->db->limit(get_option('tasks_kanban_limit'));
            }
        }

        if ($count == false) {
            return $this->db->get()->result_array();
        }

        return $this->db->count_all_results();
    }

    public function update_order($data)
    {
        foreach ($data['order'] as $order) {
            $this->db->where('id', $order[0]);
            $this->db->update(db_prefix() . 'tasks', [
                'kanban_order' => $order[1],
            ]);
        }
    }

    public function get_distinct_tasks_years($get_from)
    {
        return $this->db->query('SELECT DISTINCT(YEAR(' . $get_from . ')) as year FROM ' . db_prefix() . 'tasks WHERE ' . $get_from . ' IS NOT NULL ORDER BY year DESC')->result_array();
    }

    public function is_task_billed($id)
    {
        return (total_rows(db_prefix() . 'tasks', [
            'id'     => $id,
            'billed' => 1,
        ]) > 0 ? true : false);
    }

    public function copy($data, $overwrites = [])
    {
        $task           = $this->get($data['copy_from']);
        $fields_tasks   = $this->db->list_fields(db_prefix() . 'tasks');
        $_new_task_data = [];
        foreach ($fields_tasks as $field) {
            if (isset($task->$field)) {
                $_new_task_data[$field] = $task->$field;
            }
        }

        unset($_new_task_data['id']);
        if (isset($data['copy_task_status']) && is_numeric($data['copy_task_status'])) {
            $_new_task_data['status'] = $data['copy_task_status'];
        } else {
            // fallback in case no status is provided
            $_new_task_data['status'] = 1;
        }

        $_new_task_data['dateadded']         = date('Y-m-d H:i:s');
        $_new_task_data['startdate']         = date('Y-m-d');
        $_new_task_data['deadline_notified'] = 0;
        $_new_task_data['billed']            = 0;
        $_new_task_data['invoice_id']        = 0;
        $_new_task_data['total_cycles']      = 0;
        $_new_task_data['is_recurring_from'] = null;

        if (!empty($task->duedate)) {
            $dStart                    = new DateTime($task->startdate);
            $dEnd                      = new DateTime($task->duedate);
            $dDiff                     = $dStart->diff($dEnd);
            $_new_task_data['duedate'] = date('Y-m-d', strtotime(date('Y-m-d', strtotime('+' . $dDiff->days . 'DAY'))));
        }
        // Overwrite data options
        if (count($overwrites) > 0) {
            foreach ($overwrites as $key => $val) {
                $_new_task_data[$key] = $val;
            }
        }
        unset($_new_task_data['datefinished']);

        $_new_task_data = hooks()->apply_filters('before_add_task', $_new_task_data);

        $this->db->insert(db_prefix() . 'tasks', $_new_task_data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            $tags = get_tags_in($data['copy_from'], 'task');
            handle_tags_save($tags, $insert_id, 'task');
            if (isset($data['copy_task_assignees']) && $data['copy_task_assignees'] == 'true') {
                $this->copy_task_assignees($data['copy_from'], $insert_id);
            }
            if (isset($data['copy_task_followers']) && $data['copy_task_followers'] == 'true') {
                $this->copy_task_followers($data['copy_from'], $insert_id);
            }
            if (isset($data['copy_task_checklist_items']) && $data['copy_task_checklist_items'] == 'true') {
                $this->copy_task_checklist_items($data['copy_from'], $insert_id);
            }
            if (isset($data['copy_task_attachments']) && $data['copy_task_attachments'] == 'true') {
                $attachments = $this->get_task_attachments($data['copy_from']);
                if (is_dir(get_upload_path_by_type('task') . $data['copy_from'])) {
                    xcopy(get_upload_path_by_type('task') . $data['copy_from'], get_upload_path_by_type('task') . $insert_id);
                }
                foreach ($attachments as $at) {
                    $_at      = [];
                    $_at[]    = $at;
                    $external = false;
                    if (!empty($at['external'])) {
                        $external       = $at['external'];
                        $_at[0]['name'] = $at['file_name'];
                        $_at[0]['link'] = $at['external_link'];
                        if (!empty($at['thumbnail_link'])) {
                            $_at[0]['thumbnailLink'] = $at['thumbnail_link'];
                        }
                    }
                    $this->add_attachment_to_database($insert_id, $_at, $external, false);
                }
            }
            $this->copy_task_custom_fields($data['copy_from'], $insert_id);

            hooks()->do_action('after_add_task', $insert_id);

            return $insert_id;
        }

        return false;
    }

    public function copy_task_followers($from_task, $to_task)
    {
        $followers = $this->get_task_followers($from_task);
        foreach ($followers as $follower) {
            $this->db->insert(db_prefix() . 'task_followers', [
                'taskid'  => $to_task,
                'staffid' => $follower['followerid'],
            ]);
        }
    }

    public function copy_task_assignees($from_task, $to_task)
    {
        $assignees = $this->get_task_assignees($from_task);
        foreach ($assignees as $assignee) {
            $this->db->insert(db_prefix() . 'task_assigned', [
                'taskid'        => $to_task,
                'staffid'       => $assignee['assigneeid'],
                'assigned_from' => get_staff_user_id(),
            ]);
        }
    }

    public function copy_task_checklist_items($from_task, $to_task)
    {
        $checklists = $this->get_checklist_items($from_task);
        foreach ($checklists as $list) {
            $this->db->insert(db_prefix() . 'task_checklist_items', [
                'taskid'      => $to_task,
                'finished'    => 0,
                'description' => $list['description'],
                'dateadded'   => date('Y-m-d H:i:s'),
                'addedfrom'   => $list['addedfrom'],
                'list_order'  => $list['list_order'],
            ]);
        }
    }

    public function copy_task_custom_fields($from_task, $to_task)
    {
        $custom_fields = get_custom_fields('tasks');
        foreach ($custom_fields as $field) {
            $value = get_custom_field_value($from_task, $field['id'], 'tasks', false);
            if ($value != '') {
                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $to_task,
                    'fieldid' => $field['id'],
                    'fieldto' => 'tasks',
                    'value'   => $value,
                ]);
            }
        }
    }

    public function get_billable_tasks($customer_id = false, $project_id = '')
    {
        $has_permission_view = has_permission('tasks', '', 'view');
        $noPermissionsQuery  = get_tasks_where_string(false);

        $this->db->where('billable', 1);
        $this->db->where('billed', 0);

        if ($project_id == '') {
            $this->db->where('rel_type != "project"');
        } else {
            $this->db->where('rel_type', 'project');
            $this->db->where('rel_id', $project_id);
        }

        if ($customer_id != false && $project_id == '') {
            $this->db->where(
                '
                (
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
                )'
                );
        }

        if (!$has_permission_view) {
            $this->db->where($noPermissionsQuery);
        }

        $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();

        $i = 0;
        foreach ($tasks as $task) {
            $task_rel_data         = get_relation_data($task['rel_type'], $task['rel_id']);
            $task_rel_value        = get_relation_values($task_rel_data, $task['rel_type']);
            $tasks[$i]['rel_name'] = $task_rel_value['name'];
            if (total_rows(db_prefix() . 'taskstimers', [
                'task_id' => $task['id'],
                'end_time' => null,
            ]) > 0) {
                $tasks[$i]['started_timers'] = true;
            } else {
                $tasks[$i]['started_timers'] = false;
            }
            $i++;
        }

        return $tasks;
    }

    public function get_billable_amount($taskId)
    {
        $data = $this->get_billable_task_data($taskId);

        return app_format_number($data->total_hours * $data->hourly_rate);
    }

    public function get_billable_task_data($task_id)
    {
        $this->db->where('id', $task_id);
        $data = $this->db->get(db_prefix() . 'tasks')->row();
        if ($data->rel_type == 'project') {
            $this->db->select('billing_type,project_rate_per_hour,name');
            $this->db->where('id', $data->rel_id);
            $project      = $this->db->get(db_prefix() . 'projects')->row();
            $billing_type = get_project_billing_type($data->rel_id);

            if ($project->billing_type == 2) {
                $data->hourly_rate = $project->project_rate_per_hour;
            }

            $data->name = $project->name . ' - ' . $data->name;
        }
        $total_seconds       = $this->calc_task_total_time($task_id);
        $data->total_hours   = sec2qty($total_seconds);
        $data->total_seconds = $total_seconds;

        return $data;
    }

    public function get_tasks_by_staff_id($id, $where = [])
    {
        $this->db->where($where);
        $this->db->where('(id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid=' . $id . '))');

        return $this->db->get(db_prefix() . 'tasks')->result_array();
    }

    /**
     * Add new staff task
     * @param array $data task $_POST data
     * @return mixed
     */
    public function add($data, $clientRequest = false)
    {
        $ticket_to_task = false;

        if (isset($data['ticket_to_task'])) {
            $ticket_to_task = true;
            unset($data['ticket_to_task']);
        }

        $data['startdate']             = to_sql_date($data['startdate']);
        $data['duedate']               = to_sql_date($data['duedate']);
        $data['dateadded']             = date('Y-m-d H:i:s');
        $data['addedfrom']             = $clientRequest == false ? get_staff_user_id() : get_contact_user_id();
        $data['is_added_from_contact'] = $clientRequest == false ? 0 : 1;

        $checklistItems = [];
        if (isset($data['checklist_items']) && count($data['checklist_items']) > 0) {
            $checklistItems = $data['checklist_items'];
            unset($data['checklist_items']);
        }

        if ($clientRequest == false) {
            $defaultStatus = get_option('default_task_status');
            if ($defaultStatus == 'auto') {
                if (date('Y-m-d') >= $data['startdate']) {
                    $data['status'] = 4;
                } else {
                    $data['status'] = 1;
                }
            } else {
                $data['status'] = $defaultStatus;
            }
        } else {
            // When client create task the default status is NOT STARTED
            // After staff will get the task will change the status
            $data['status'] = 1;
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        if (isset($data['is_public'])) {
            $data['is_public'] = 1;
        } else {
            $data['is_public'] = 0;
        }

        if (isset($data['repeat_every']) && $data['repeat_every'] != '') {
            $data['recurring'] = 1;
            if ($data['repeat_every'] == 'custom') {
                $data['repeat_every']     = $data['repeat_every_custom'];
                $data['recurring_type']   = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
            } else {
                $_temp                    = explode('-', $data['repeat_every']);
                $data['recurring_type']   = $_temp[1];
                $data['repeat_every']     = $_temp[0];
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['recurring'] = 0;
        }

        if (isset($data['repeat_type_custom']) && isset($data['repeat_every_custom'])) {
            unset($data['repeat_type_custom']);
            unset($data['repeat_every_custom']);
        }

        if (is_client_logged_in() || $clientRequest) {
            $data['visible_to_client'] = 1;
        } else {
            if (isset($data['visible_to_client'])) {
                $data['visible_to_client'] = 1;
            } else {
                $data['visible_to_client'] = 0;
            }
        }

        if (isset($data['billable'])) {
            $data['billable'] = 1;
        } else {
            $data['billable'] = 0;
        }

        if ((!isset($data['milestone']) || $data['milestone'] == '') || (isset($data['milestone']) && $data['milestone'] == '')) {
            $data['milestone'] = 0;
        } else {
            if ($data['rel_type'] != 'project') {
                $data['milestone'] = 0;
            }
        }
        if (empty($data['rel_type'])) {
            unset($data['rel_type']);
            unset($data['rel_id']);
        } else {
            if (empty($data['rel_id'])) {
                unset($data['rel_type']);
                unset($data['rel_id']);
            }
        }

        $data = hooks()->apply_filters('before_add_task', $data);

        $tags = '';
        if (isset($data['tags'])) {
            $tags = $data['tags'];
            unset($data['tags']);
        }

        $this->db->insert(db_prefix() . 'tasks', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            foreach ($checklistItems as $key => $chkID) {
                if ($chkID != '') {
                    $itemTemplate = $this->get_checklist_template($chkID);
                    $this->db->insert(db_prefix() . 'task_checklist_items', [
                        'description' => $itemTemplate->description,
                        'taskid'      => $insert_id,
                        'dateadded'   => date('Y-m-d H:i:s'),
                        'addedfrom'   => get_staff_user_id(),
                        'list_order'  => $key,
                        ]);
                }
            }
            handle_tags_save($tags, $insert_id, 'task');
            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            if (isset($data['rel_type']) && $data['rel_type'] == 'lead') {
                $this->load->model('leads_model');
                $this->leads_model->log_lead_activity($data['rel_id'], 'not_activity_new_task_created', false, serialize([
                    '<a href="' . admin_url('tasks/view/' . $insert_id) . '" onclick="init_task_modal(' . $insert_id . ');return false;">' . $data['name'] . '</a>',
                    ]));
            }

            if ($clientRequest == false) {
                $new_task_auto_assign_creator = (get_option('new_task_auto_assign_current_member') == '1' ? true : false);

                if (isset($data['rel_type']) && $data['rel_type'] == 'project' && !$this->projects_model->is_member($data['rel_id'])) {
                    $new_task_auto_assign_creator = false;
                }
                if ($new_task_auto_assign_creator == true) {
                    $this->db->insert(db_prefix() . 'task_assigned', [
                        'taskid'        => $insert_id,
                        'staffid'       => get_staff_user_id(),
                        'assigned_from' => get_staff_user_id(),
                    ]);
                }
                if (get_option('new_task_auto_follower_current_member') == '1') {
                    $this->db->insert(db_prefix() . 'task_followers', [
                        'taskid'  => $insert_id,
                        'staffid' => get_staff_user_id(),
                    ]);
                }

                if ($ticket_to_task && isset($data['rel_type']) && $data['rel_type'] == 'ticket') {
                    $ticket_attachments = $this->db->query('SELECT * FROM ' . db_prefix() . 'ticket_attachments WHERE ticketid=' . $data['rel_id'] . ' OR (ticketid=' . $data['rel_id'] . ' AND replyid IN (SELECT id FROM ' . db_prefix() . 'ticket_replies WHERE ticketid=' . $data['rel_id'] . '))')->result_array();

                    if (count($ticket_attachments) > 0) {
                        $task_path = get_upload_path_by_type('task') . $insert_id . '/';
                        _maybe_create_upload_path($task_path);

                        foreach ($ticket_attachments as $ticket_attachment) {
                            $path = get_upload_path_by_type('ticket') . $data['rel_id'] . '/' . $ticket_attachment['file_name'];
                            if (file_exists($path)) {
                                $f = fopen($path, FOPEN_READ);
                                if ($f) {
                                    $filename = unique_filename($task_path, $ticket_attachment['file_name']);
                                    $fpt      = fopen($task_path . $filename, 'w');
                                    if ($fpt && fwrite($fpt, stream_get_contents($f))) {
                                        $this->db->insert(db_prefix() . 'files', [
                                                            'rel_id'         => $insert_id,
                                                            'rel_type'       => 'task',
                                                            'file_name'      => $filename,
                                                            'filetype'       => $ticket_attachment['filetype'],
                                                            'staffid'        => get_staff_user_id(),
                                                            'dateadded'      => date('Y-m-d H:i:s'),
                                                            'attachment_key' => app_generate_hash(),
                                                        ]);
                                    }
                                    if ($fpt) {
                                        fclose($fpt);
                                    }
                                    fclose($f);
                                }
                            }
                        }
                    }
                }
            }

            log_activity('New Task Added [ID:' . $insert_id . ', Name: ' . $data['name'] . ']');
            hooks()->do_action('after_add_task', $insert_id);

            return $insert_id;
        }

        return false;
    }

    /**
     * Update task data
     * @param  array $data task data $_POST
     * @param  mixed $id   task id
     * @return boolean
     */
    public function update($data, $id, $clientRequest = false)
    {
        $affectedRows      = 0;
        $data['startdate'] = to_sql_date($data['startdate']);
        $data['duedate']   = to_sql_date($data['duedate']);

        $checklistItems = [];
        if (isset($data['checklist_items']) && count($data['checklist_items']) > 0) {
            $checklistItems = $data['checklist_items'];
            unset($data['checklist_items']);
        }

        if (isset($data['datefinished'])) {
            $data['datefinished'] = to_sql_date($data['datefinished'], true);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        if ($clientRequest == false) {
            $data['cycles'] = !isset($data['cycles']) ? 0 : $data['cycles'];

            $original_task = $this->get($id);

            // Recurring task set to NO, Cancelled
            if ($original_task->repeat_every != '' && $data['repeat_every'] == '') {
                $data['cycles']              = 0;
                $data['total_cycles']        = 0;
                $data['last_recurring_date'] = null;
            }

            if ($data['repeat_every'] != '') {
                $data['recurring'] = 1;
                if ($data['repeat_every'] == 'custom') {
                    $data['repeat_every']     = $data['repeat_every_custom'];
                    $data['recurring_type']   = $data['repeat_type_custom'];
                    $data['custom_recurring'] = 1;
                } else {
                    $_temp                    = explode('-', $data['repeat_every']);
                    $data['recurring_type']   = $_temp[1];
                    $data['repeat_every']     = $_temp[0];
                    $data['custom_recurring'] = 0;
                }
            } else {
                $data['recurring'] = 0;
            }

            if (isset($data['repeat_type_custom']) && isset($data['repeat_every_custom'])) {
                unset($data['repeat_type_custom']);
                unset($data['repeat_every_custom']);
            }

            if (isset($data['is_public'])) {
                $data['is_public'] = 1;
            } else {
                $data['is_public'] = 0;
            }
            if (isset($data['billable'])) {
                $data['billable'] = 1;
            } else {
                $data['billable'] = 0;
            }

            if (isset($data['visible_to_client'])) {
                $data['visible_to_client'] = 1;
            } else {
                $data['visible_to_client'] = 0;
            }
        }

        if ((!isset($data['milestone']) || $data['milestone'] == '') || (isset($data['milestone']) && $data['milestone'] == '')) {
            $data['milestone'] = 0;
        } else {
            if ($data['rel_type'] != 'project') {
                $data['milestone'] = 0;
            }
        }


        if (empty($data['rel_type'])) {
            $data['rel_id']   = null;
            $data['rel_type'] = null;
        } else {
            if (empty($data['rel_id'])) {
                $data['rel_id']   = null;
                $data['rel_type'] = null;
            }
        }

        $data = hooks()->apply_filters('before_update_task', $data, $id);

        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'task')) {
                $affectedRows++;
            }
            unset($data['tags']);
        }

        foreach ($checklistItems as $key => $chkID) {
            $itemTemplate = $this->get_checklist_template($chkID);
            $this->db->insert(db_prefix() . 'task_checklist_items', [
                    'description' => $itemTemplate->description,
                    'taskid'      => $id,
                    'dateadded'   => date('Y-m-d H:i:s'),
                    'addedfrom'   => get_staff_user_id(),
                    'list_order'  => $key,
                    ]);
            $affectedRows++;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'tasks', $data);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            hooks()->do_action('after_update_task', $id);
            log_activity('Task Updated [ID:' . $id . ', Name: ' . $data['name'] . ']');
        }

        if ($affectedRows > 0) {
            return true;
        }

        return false;
    }

    public function get_checklist_item($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'task_checklist_items')->row();
    }

    public function get_checklist_items($taskid)
    {
        $this->db->where('taskid', $taskid);
        $this->db->order_by('list_order', 'asc');

        return $this->db->get(db_prefix() . 'task_checklist_items')->result_array();
    }

    public function add_checklist_template($description)
    {
        $this->db->insert(db_prefix() . 'tasks_checklist_templates', [
            'description' => $description,
            ]);

        return $this->db->insert_id();
    }

    public function remove_checklist_item_template($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'tasks_checklist_templates');
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function get_checklist_templates()
    {
        $this->db->order_by('description', 'asc');

        return $this->db->get(db_prefix() . 'tasks_checklist_templates')->result_array();
    }

    public function get_checklist_template($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'tasks_checklist_templates')->row();
    }

    /**
     * Add task new blank check list item
     * @param mixed $data $_POST data with taxid
     */
    public function add_checklist_item($data)
    {
        $this->db->insert(db_prefix() . 'task_checklist_items', [
            'taskid'      => $data['taskid'],
            'description' => $data['description'],
            'dateadded'   => date('Y-m-d H:i:s'),
            'addedfrom'   => get_staff_user_id(),
            'list_order'  => 0,
        ]);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            hooks()->do_action('task_checklist_item_created', ['task_id' => $data['taskid'], 'checklist_id' => $insert_id]);

            return true;
        }

        return false;
    }

    public function delete_checklist_item($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'task_checklist_items');
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function update_checklist_order($data)
    {
        foreach ($data['order'] as $order) {
            $this->db->where('id', $order[0]);
            $this->db->update(db_prefix() . 'task_checklist_items', [
                'list_order' => $order[1],
            ]);
        }
    }

    /**
     * Update checklist item
     * @param  mixed $id          check list id
     * @param  mixed $description checklist description
     * @return void
     */
    public function update_checklist_item($id, $description)
    {
        $description = strip_tags($description, '<br>,<br/>');
        if ($description === '') {
            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'task_checklist_items');
        } else {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'task_checklist_items', [
                'description' => nl2br($description),
            ]);
        }
    }

    /**
     * Make task public
     * @param  mixed $task_id task id
     * @return boolean
     */
    public function make_public($task_id)
    {
        $this->db->where('id', $task_id);
        $this->db->update(db_prefix() . 'tasks', [
            'is_public' => 1,
        ]);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get task creator id
     * @param  mixed $taskid task id
     * @return mixed
     */
    public function get_task_creator_id($taskid)
    {
        $this->db->select('addedfrom');
        $this->db->where('id', $taskid);

        return $this->db->get(db_prefix() . 'tasks')->row()->addedfrom;
    }

    /**
     * Add new task comment
     * @param array $data comment $_POST data
     * @return boolean
     */
    public function add_task_comment($data)
    {
        if (is_client_logged_in()) {
            $data['staffid']    = 0;
            $data['contact_id'] = get_contact_user_id();
        } else {
            $data['staffid']    = get_staff_user_id();
            $data['contact_id'] = 0;
        }

        $this->db->insert(db_prefix() . 'task_comments', [
            'taskid'     => $data['taskid'],
            'content'    => is_client_logged_in() ? _strip_tags($data['content']) : $data['content'],
            'staffid'    => $data['staffid'],
            'contact_id' => $data['contact_id'],
            'dateadded'  => date('Y-m-d H:i:s'),
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            $this->db->select('rel_type,rel_id,name,visible_to_client');
            $this->db->where('id', $data['taskid']);
            $task = $this->db->get(db_prefix() . 'tasks')->row();

            $description     = 'not_task_new_comment';
            $additional_data = serialize([
                $task->name,
            ]);

            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_new_task_comment', $task->name, $task->visible_to_client);
            }

            $this->_send_task_responsible_users_notification($description, $data['taskid'], false, 'task_new_comment_to_staff', $additional_data, $insert_id);
            $this->_send_customer_contacts_notification($data['taskid'], 'task_new_comment_to_customer');

            hooks()->do_action('task_comment_added', ['task_id' => $data['taskid'], 'comment_id' => $insert_id]);

            return $insert_id;
        }

        return false;
    }

    /**
     * Add task followers
     * @param array $data followers $_POST data
     * @return boolean
     */
    public function add_task_followers($data)
    {
        $this->db->insert(db_prefix() . 'task_followers', [
            'taskid'  => $data['taskid'],
            'staffid' => $data['follower'],
        ]);
        if ($this->db->affected_rows() > 0) {
            $taskName = get_task_subject_by_id($data['taskid']);

            if (get_staff_user_id() != $data['follower']) {
                $notified = add_notification([
                    'description'     => 'not_task_added_you_as_follower',
                    'touserid'        => $data['follower'],
                    'link'            => '#taskid=' . $data['taskid'],
                    'additional_data' => serialize([
                        $taskName,
                    ]),
                ]);

                if ($notified) {
                    pusher_trigger_notification([$data['follower']]);
                }

                $member = $this->staff_model->get($data['follower']);

                send_mail_template('task_added_as_follower_to_staff', $member->email, $data['follower'], $data['taskid']);
            }

            $description = 'not_task_added_someone_as_follower';

            $additional_notification_data = serialize([
                get_staff_full_name($data['follower']),
                $taskName,
            ]);

            if ($data['follower'] == get_staff_user_id()) {
                $additional_notification_data = serialize([
                    $taskName,
                ]);
                $description = 'not_task_added_himself_as_follower';
            }

            $this->_send_task_responsible_users_notification($description, $data['taskid'], $data['follower'], '', $additional_notification_data);

            return true;
        }

        return false;
    }

    /**
     * Assign task to staff
     * @param array $data task assignee $_POST data
     * @return boolean
     */
    public function add_task_assignees($data, $cronOrIntegration = false, $clientRequest = false)
    {
        $assignData = [
            'taskid'  => $data['taskid'],
            'staffid' => $data['assignee'],
        ];
        if ($cronOrIntegration) {
            $assignData['assigned_from'] = $data['assignee'];
        } elseif ($clientRequest) {
            $assignData['is_assigned_from_contact'] = 1;
            $assignData['assigned_from']            = get_contact_user_id();
        } else {
            $assignData['assigned_from'] = get_staff_user_id();
        }
        $this->db->insert(db_prefix() . 'task_assigned', $assignData);

        $assigneeId = $this->db->insert_id();

        if ($assigneeId) {
            $this->db->select('name,visible_to_client,rel_id,rel_type');
            $this->db->where('id', $data['taskid']);
            $task = $this->db->get(db_prefix() . 'tasks')->row();

            if (get_staff_user_id() != $data['assignee'] || $clientRequest) {
                $notification_data = [
                    'description' => ($cronOrIntegration == false ? 'not_task_assigned_to_you' : 'new_task_assigned_non_user'),
                    'touserid'    => $data['assignee'],
                    'link'        => '#taskid=' . $data['taskid'],
                ];

                $notification_data['additional_data'] = serialize([
                    $task->name,
                ]);

                if ($cronOrIntegration) {
                    $notification_data['fromcompany'] = 1;
                }

                if ($clientRequest) {
                    $notification_data['fromclientid'] = get_contact_user_id();
                }

                if (add_notification($notification_data)) {
                    pusher_trigger_notification([$data['assignee']]);
                }

                $member = $this->staff_model->get($data['assignee']);

                send_mail_template('task_assigned_to_staff', $member->email, $data['assignee'], $data['taskid']);
            }

            $description                  = 'not_task_assigned_someone';
            $additional_notification_data = serialize([
                get_staff_full_name($data['assignee']),
                $task->name,
            ]);
            if ($data['assignee'] == get_staff_user_id()) {
                $description                  = 'not_task_will_do_user';
                $additional_notification_data = serialize([
                    $task->name,
                ]);
            }

            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_new_task_assignee', $task->name . ' - ' . get_staff_full_name($data['assignee']), $task->visible_to_client);
            }

            $this->_send_task_responsible_users_notification($description, $data['taskid'], $data['assignee'], '', $additional_notification_data);

            return $assigneeId;
        }

        return false;
    }

    /**
     * Get all task attachments
     * @param  mixed $taskid taskid
     * @return array
     */
    public function get_task_attachments($taskid, $where = [])
    {
        $this->db->select(implode(', ', prefixed_table_fields_array(db_prefix() . 'files')) . ', ' . db_prefix() . 'task_comments.id as comment_file_id');
        $this->db->where(db_prefix() . 'files.rel_id', $taskid);
        $this->db->where(db_prefix() . 'files.rel_type', 'task');

        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        $this->db->join(db_prefix() . 'task_comments', db_prefix() . 'task_comments.file_id = ' . db_prefix() . 'files.id', 'left');
        $this->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'files.rel_id');
        $this->db->order_by(db_prefix() . 'files.dateadded', 'desc');

        return $this->db->get(db_prefix() . 'files')->result_array();
    }

    /**
     * Remove task attachment from server and database
     * @param  mixed $id attachmentid
     * @return boolean
     */
    public function remove_task_attachment($id)
    {
        $comment_removed = false;
        $deleted         = false;
        // Get the attachment
        $this->db->where('id', $id);
        $attachment = $this->db->get(db_prefix() . 'files')->row();

        if ($attachment) {
            if (empty($attachment->external)) {
                $relPath  = get_upload_path_by_type('task') . $attachment->rel_id . '/';
                $fullPath = $relPath . $attachment->file_name;
                unlink($fullPath);
                $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
                $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);
                $thumbPath = $relPath . $fname . '_thumb.' . $fext;
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }

            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Task Attachment Deleted [TaskID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('task') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('task') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('task') . $attachment->rel_id);
                }
            }
        }

        if ($deleted) {
            if ($attachment->task_comment_id != 0) {
                $total_comment_files = total_rows(db_prefix() . 'files', ['task_comment_id' => $attachment->task_comment_id]);
                if ($total_comment_files == 0) {
                    $this->db->where('id', $attachment->task_comment_id);
                    $comment = $this->db->get(db_prefix() . 'task_comments')->row();

                    if ($comment) {
                        // Comment is empty and uploaded only with attachments
                        // Now all attachments are deleted, we need to delete the comment too
                        if (empty($comment->content) || $comment->content === '[task_attachment]') {
                            $this->db->where('id', $attachment->task_comment_id);
                            $this->db->delete(db_prefix() . 'task_comments');
                            $comment_removed = $comment->id;
                        } else {
                            $this->db->query('UPDATE ' . db_prefix() . "task_comments
                            SET content = REPLACE(content, '[task_attachment]', '')
                            WHERE id = " . $attachment->task_comment_id);
                        }
                    }
                }
            }

            $this->db->where('file_id', $id);
            $comment_attachment = $this->db->get(db_prefix() . 'task_comments')->row();

            if ($comment_attachment) {
                $this->remove_comment($comment_attachment->id);
            }
        }

        return ['success' => $deleted, 'comment_removed' => $comment_removed];
    }

    /**
     * Add uploaded attachments to database
     * @since  Version 1.0.1
     * @param mixed $taskid     task id
     * @param array $attachment attachment data
     */
    public function add_attachment_to_database($rel_id, $attachment, $external = false, $notification = true)
    {
        $file_id = $this->misc_model->add_attachment_to_database($rel_id, 'task', $attachment, $external);
        if ($file_id) {
            $this->db->select('rel_type,rel_id,name,visible_to_client');
            $this->db->where('id', $rel_id);
            $task = $this->db->get(db_prefix() . 'tasks')->row();

            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_new_task_attachment', $task->name, $task->visible_to_client);
            }

            if ($notification == true) {
                $description = 'not_task_new_attachment';
                $this->_send_task_responsible_users_notification($description, $rel_id, false, 'task_new_attachment_to_staff');
                $this->_send_customer_contacts_notification($rel_id, 'task_new_attachment_to_customer');
            }

            $task_attachment_as_comment = hooks()->apply_filters('add_task_attachment_as_comment', 'true');

            if ($task_attachment_as_comment == 'true') {
                $file = $this->misc_model->get_file($file_id);
                $this->db->insert(db_prefix() . 'task_comments', [
                    'content'    => '[task_attachment]',
                    'taskid'     => $rel_id,
                    'staffid'    => $file->staffid,
                    'contact_id' => $file->contact_id,
                    'file_id'    => $file_id,
                    'dateadded'  => date('Y-m-d H:i:s'),
                    ]);
            }

            return true;
        }

        return false;
    }

    /**
     * Get all task followers
     * @param  mixed $id task id
     * @return array
     */
    public function get_task_followers($id)
    {
        $this->db->select('id,' . db_prefix() . 'task_followers.staffid as followerid, CONCAT(firstname, " ", lastname) as full_name');
        $this->db->from(db_prefix() . 'task_followers');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'task_followers.staffid');
        $this->db->where('taskid', $id);

        return $this->db->get()->result_array();
    }

    /**
     * Get all task assigneed
     * @param  mixed $id task id
     * @return array
     */
    public function get_task_assignees($id)
    {
        $this->db->select('id,' . db_prefix() . 'task_assigned.staffid as assigneeid,assigned_from,firstname,lastname,CONCAT(firstname, " ", lastname) as full_name,is_assigned_from_contact');
        $this->db->from(db_prefix() . 'task_assigned');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'task_assigned.staffid');
        $this->db->where('taskid', $id);
        $this->db->order_by('firstname', 'asc');

        return $this->db->get()->result_array();
    }

    /**
     * Get task comment
     * @param  mixed $id task id
     * @return array
     */
    public function get_task_comments($id)
    {
        $task_comments_order = hooks()->apply_filters('task_comments_order', 'DESC');

        $this->db->select('id,dateadded,content,' . db_prefix() . 'staff.firstname,' . db_prefix() . 'staff.lastname,' . db_prefix() . 'task_comments.staffid,' . db_prefix() . 'task_comments.contact_id as contact_id,file_id,CONCAT(firstname, " ", lastname) as staff_full_name');
        $this->db->from(db_prefix() . 'task_comments');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'task_comments.staffid', 'left');
        $this->db->where('taskid', $id);
        $this->db->order_by('dateadded', $task_comments_order);

        $comments = $this->db->get()->result_array();

        $ids = [];
        foreach ($comments as $key => $comment) {
            array_push($ids, $comment['id']);
            $comments[$key]['attachments'] = [];
        }

        if (count($ids) > 0) {
            $allAttachments = $this->get_task_attachments($id, 'task_comment_id IN (' . implode(',', $ids) . ')');
            foreach ($comments as $key => $comment) {
                foreach ($allAttachments as $attachment) {
                    if ($comment['id'] == $attachment['task_comment_id']) {
                        $comments[$key]['attachments'][] = $attachment;
                    }
                }
            }
        }

        return $comments;
    }

    public function edit_comment($data)
    {
        // Check if user really creator
        $this->db->where('id', $data['id']);
        $comment = $this->db->get(db_prefix() . 'task_comments')->row();
        if ($comment->staffid == get_staff_user_id() || has_permission('tasks', '', 'edit') || $comment->contact_id == get_contact_user_id()) {
            $comment_added = strtotime($comment->dateadded);
            $minus_1_hour  = strtotime('-1 hours');
            if (get_option('client_staff_add_edit_delete_task_comments_first_hour') == 0 || (get_option('client_staff_add_edit_delete_task_comments_first_hour') == 1 && $comment_added >= $minus_1_hour) || is_admin()) {
                if (total_rows(db_prefix() . 'files', ['task_comment_id' => $comment->id]) > 0) {
                    $data['content'] .= '[task_attachment]';
                }

                $this->db->where('id', $data['id']);
                $this->db->update(db_prefix() . 'task_comments', [
                    'content' => $data['content'],
                ]);
                if ($this->db->affected_rows() > 0) {

                    hooks()->do_action('task_comment_updated', [
                        'comment_id' => $comment->id,
                        'task_id'    => $comment->taskid,
                    ]);

                    return true;
                }
            } else {
                return false;
            }

            return false;
        }
    }

    /**
     * Remove task comment from database
     * @param  mixed $id task id
     * @return boolean
     */
    public function remove_comment($id, $force = false)
    {
        // Check if user really creator
        $this->db->where('id', $id);
        $comment = $this->db->get(db_prefix() . 'task_comments')->row();

        if (!$comment) {
            return true;
        }

        if ($comment->staffid == get_staff_user_id() || has_permission('tasks', '', 'delete') || $comment->contact_id == get_contact_user_id() || $force === true) {
            $comment_added = strtotime($comment->dateadded);
            $minus_1_hour  = strtotime('-1 hours');
            if (get_option('client_staff_add_edit_delete_task_comments_first_hour') == 0 || (get_option('client_staff_add_edit_delete_task_comments_first_hour') == 1 && $comment_added >= $minus_1_hour)
                || (is_admin() || $force === true)) {
                $this->db->where('id', $id);
                $this->db->delete(db_prefix() . 'task_comments');
                if ($this->db->affected_rows() > 0) {
                    if ($comment->file_id != 0) {
                        $this->remove_task_attachment($comment->file_id);
                    }

                    $commentAttachments = $this->get_task_attachments($comment->taskid, 'task_comment_id=' . $id);
                    foreach ($commentAttachments as $attachment) {
                        $this->remove_task_attachment($attachment['id']);
                    }

                    hooks()->do_action('task_comment_deleted', [ 'task_id' => $comment->taskid, 'comment_id' => $id ]);

                    return true;
                }
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Remove task assignee from database
     * @param  mixed $id     assignee id
     * @param  mixed $taskid task id
     * @return boolean
     */
    public function remove_assignee($id, $taskid)
    {
        $this->db->select('rel_type,rel_id,name,visible_to_client');
        $this->db->where('id', $taskid);
        $task = $this->db->get(db_prefix() . 'tasks')->row();

        $this->db->where('id', $id);
        $assignee_data = $this->db->get(db_prefix() . 'task_assigned')->row();

        // Delete timers
        //   $this->db->where('task_id', $taskid);
        ////   $this->db->where('staff_id', $assignee_data->staffid);
        ///   $this->db->delete(db_prefix().'taskstimers');

        // Stop all timers
        $this->db->where('task_id', $taskid);
        $this->db->where('staff_id', $assignee_data->staffid);
        $this->db->where('end_time IS NULL');
        $this->db->update(db_prefix() . 'taskstimers', ['end_time' => time()]);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'task_assigned');
        if ($this->db->affected_rows() > 0) {
            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_task_assignee_removed', $task->name . ' - ' . get_staff_full_name($assignee_data->staffid), $task->visible_to_client);
            }

            return true;
        }

        return false;
    }

    /**
     * Remove task follower from database
     * @param  mixed $id     followerid
     * @param  mixed $taskid task id
     * @return boolean
     */
    public function remove_follower($id, $taskid)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'task_followers');
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Change task status
     * @param  mixed $status  task status
     * @param  mixed $task_id task id
     * @return boolean
     */
    public function mark_as($status, $task_id)
    {
        $this->db->select('rel_type,rel_id,name,visible_to_client,status');
        $this->db->where('id', $task_id);
        $task = $this->db->get(db_prefix() . 'tasks')->row();

        if ($task->status == self::STATUS_COMPLETE) {
            return $this->unmark_complete($task_id, $status);
        }

        $update = ['status' => $status];

        if ($status == self::STATUS_COMPLETE) {
            $update['datefinished'] = date('Y-m-d H:i:s');
        }

        $this->db->where('id', $task_id);
        $this->db->update(db_prefix() . 'tasks', $update);
        if ($this->db->affected_rows() > 0) {
            $description = 'not_task_status_changed';

            $not_data = [
                $task->name,
                format_task_status($status, false, true),
            ];

            if ($status == self::STATUS_COMPLETE) {
                $description = 'not_task_marked_as_complete';
                unset($not_data[1]);

                $this->db->where('end_time IS NULL');
                $this->db->where('task_id', $task_id);
                $this->db->update(db_prefix() . 'taskstimers', [
                    'end_time' => time(),
                ]);
            }

            if ($task->rel_type == 'project') {
                $project_activity_log = $status == self::STATUS_COMPLETE ? 'project_activity_task_marked_complete' : 'not_project_activity_task_status_changed';

                $project_activity_desc = $task->name;

                if ($status != self::STATUS_COMPLETE) {
                    $project_activity_desc .= ' - ' . format_task_status($status);
                }

                $this->projects_model->log_activity($task->rel_id, $project_activity_log, $project_activity_desc, $task->visible_to_client);
            }

            $this->_send_task_responsible_users_notification($description, $task_id, false, 'task_status_changed_to_staff', serialize($not_data));

            $this->_send_customer_contacts_notification($task_id, 'task_status_changed_to_customer');
            hooks()->do_action('task_status_changed', ['status' => $status, 'task_id' => $task_id]);

            return true;
        }

        return false;
    }

    /**
     * Unmark task as complete
     * @param  mixed $id task id
     * @return boolean
     */
    public function unmark_complete($id, $force_to_status = false)
    {
        if ($force_to_status != false) {
            $status = $force_to_status;
        } else {
            $status = 1;
            $this->db->select('startdate');
            $this->db->where('id', $id);
            $_task = $this->db->get(db_prefix() . 'tasks')->row();
            if (date('Y-m-d') > date('Y-m-d', strtotime($_task->startdate))) {
                $status = 4;
            }
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'tasks', [
            'datefinished' => null,
            'status'       => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            $this->db->select('rel_type,rel_id,name,visible_to_client');
            $this->db->where('id', $id);
            $task = $this->db->get(db_prefix() . 'tasks')->row();

            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_task_unmarked_complete', $task->name, $task->visible_to_client);
            }

            $description = 'not_task_unmarked_as_complete';

            $this->_send_task_responsible_users_notification('not_task_unmarked_as_complete', $id, false, 'task_status_changed_to_staff', serialize([
                $task->name,
            ]));

            hooks()->do_action('task_status_changed', ['status' => $status, 'task_id' => $id]);

            return true;
        }

        return false;
    }

    /**
     * Delete task and all connections
     * @param  mixed $id taskid
     * @return boolean
     */
    public function delete_task($id, $log_activity = true)
    {
        $this->db->select('rel_type,rel_id,name,visible_to_client');
        $this->db->where('id', $id);
        $task = $this->db->get(db_prefix() . 'tasks')->row();

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'tasks');
        if ($this->db->affected_rows() > 0) {

            // Log activity only if task is deleted indivudual not when deleting all projects
            if ($task->rel_type == 'project' && $log_activity == true) {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_task_deleted', $task->name, $task->visible_to_client);
            }

            $this->db->where('taskid', $id);
            $this->db->delete(db_prefix() . 'task_followers');

            $this->db->where('taskid', $id);
            $this->db->delete(db_prefix() . 'task_assigned');

            $this->db->where('taskid', $id);
            $comments = $this->db->get(db_prefix() . 'task_comments')->result_array();
            foreach ($comments as $comment) {
                $this->remove_comment($comment['id'], true);
            }

            $this->db->where('taskid', $id);
            $this->db->delete(db_prefix() . 'task_checklist_items');

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'tasks');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('task_id', $id);
            $this->db->delete(db_prefix() . 'taskstimers');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'task');
            $this->db->delete(db_prefix() . 'taggables');

            $this->db->where('rel_type', 'task');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'task');
            $attachments = $this->db->get(db_prefix() . 'files')->result_array();
            foreach ($attachments as $at) {
                $this->remove_task_attachment($at['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'task');
            $this->db->delete(db_prefix() . 'related_items');

            if (is_dir(get_upload_path_by_type('task') . $id)) {
                delete_dir(get_upload_path_by_type('task') . $id);
            }

            hooks()->do_action('task_deleted', $id);

            return true;
        }

        return false;
    }

    /**
     * Send notification on task activity to creator,follower/s,assignee/s
     * @param  string  $description notification description
     * @param  mixed  $taskid      task id
     * @param  boolean $excludeid   excluded staff id to not send the notifications
     * @return boolean
     */
    private function _send_task_responsible_users_notification($description, $taskid, $excludeid = false, $email_template = '', $additional_notification_data = '', $comment_id = false)
    {
        $this->load->model('staff_model');

        $staff = $this->staff_model->get('', ['active' => 1]);

        $notifiedUsers = [];
        foreach ($staff as $member) {
            if (is_numeric($excludeid)) {
                if ($excludeid == $member['staffid']) {
                    continue;
                }
            }
            if (!is_client_logged_in()) {
                if ($member['staffid'] == get_staff_user_id()) {
                    continue;
                }
            }

            if ($this->should_staff_receive_notification($member['staffid'], $taskid)) {
                $link = '#taskid=' . $taskid;

                if ($comment_id) {
                    $link .= '#comment_' . $comment_id;
                }

                $notified = add_notification([
                    'description'     => $description,
                    'touserid'        => $member['staffid'],
                    'link'            => $link,
                    'additional_data' => $additional_notification_data,
                ]);

                if ($notified) {
                    array_push($notifiedUsers, $member['staffid']);
                }

                if ($email_template != '') {
                    send_mail_template($email_template, $member['email'], $member['staffid'], $taskid);
                }
            }
        }

        pusher_trigger_notification($notifiedUsers);
    }

    public function _send_customer_contacts_notification($taskid, $template_name)
    {
        $this->db->select('rel_id,visible_to_client,rel_type');
        $this->db->from(db_prefix() . 'tasks');
        $this->db->where('id', $taskid);
        $task = $this->db->get()->row();

        if ($task->rel_type == 'project') {
            $this->db->where('project_id', $task->rel_id);
            $this->db->where('name', 'view_tasks');
            $project_settings = $this->db->get(db_prefix() . 'project_settings')->row();
            if ($project_settings) {
                if ($project_settings->value == 1 && $task->visible_to_client == 1) {
                    $this->db->select('clientid');
                    $this->db->from(db_prefix() . 'projects');
                    $this->db->where('id', $project_settings->project_id);
                    $project  = $this->db->get()->row();
                    $contacts = $this->clients_model->get_contacts($project->clientid, ['active' => 1, 'task_emails' => 1]);
                    foreach ($contacts as $contact) {
                        if (is_client_logged_in() && get_contact_user_id() == $contact['id']) {
                            continue;
                        }

                        send_mail_template($template_name, $contact['email'], $project->clientid, $contact['id'], $taskid);
                    }
                }
            }
        }
    }

    /**
     * Check if user has commented on task
     * @param  mixed $userid staff id to check
     * @param  mixed $taskid task id
     * @return boolean
     */
    public function staff_has_commented_on_task($userid, $taskid)
    {
        return total_rows(db_prefix() . 'task_comments', ['staffid' => $userid, 'taskid' => $taskid]) > 0 ? true : false;
    }

    /**
     * Check is user is task follower
     * @param  mixed  $userid staff id
     * @param  mixed  $taskid taskid
     * @return boolean
     */
    public function is_task_follower($userid, $taskid)
    {
        if (total_rows(db_prefix() . 'task_followers', [
            'staffid' => $userid,
            'taskid' => $taskid,
        ]) == 0) {
            return false;
        }

        return true;
    }

    /**
     * Check is user is task assignee
     * @param  mixed  $userid staff id
     * @param  mixed  $taskid taskid
     * @return boolean
     */
    public function is_task_assignee($userid, $taskid)
    {
        if (total_rows(db_prefix() . 'task_assigned', [
            'staffid' => $userid,
            'taskid' => $taskid,
        ]) == 0) {
            return false;
        }

        return true;
    }

    /**
     * Check is user is task creator
     * @param  mixed  $userid staff id
     * @param  mixed  $taskid taskid
     * @return boolean
     */
    public function is_task_creator($userid, $taskid)
    {
        if (total_rows(db_prefix() . 'tasks', [
            'addedfrom' => $userid,
            'id' => $taskid,
            'is_added_from_contact' => 0,
        ]) == 0) {
            return false;
        }

        return true;
    }

    /**
     * Timer action, START/STOP Timer
     * @param  mixed  $task_id   task id
     * @param  mixed  $timer_id  timer_id to stop the timer
     * @param  string  $note      note for timer
     * @param  boolean $adminStop is admin want to stop timer from another staff member
     * @return boolean
     */
    public function timer_tracking($task_id = '', $timer_id = '', $note = '', $adminStop = false)
    {
        if ($task_id == '' && $timer_id == '') {
            return false;
        }

        if ($task_id !== '0' && $adminStop == false) {
            if (!$this->is_task_assignee(get_staff_user_id(), $task_id)) {
                return false;
            } elseif ($this->is_task_billed($task_id)) {
                return false;
            }
        }

        $timer = $this->get_task_timer([
            'id' => $timer_id,
        ]);

        $newTimer = false;

        if ($timer == null) {
            $newTimer = true;
        }

        if ($newTimer) {
            $this->db->select('hourly_rate');
            $this->db->from(db_prefix() . 'staff');
            $this->db->where('staffid', get_staff_user_id());
            $hourly_rate = $this->db->get()->row()->hourly_rate;

            $this->db->insert(db_prefix() . 'taskstimers', [
                'start_time'  => time(),
                'staff_id'    => get_staff_user_id(),
                'task_id'     => $task_id,
                'hourly_rate' => $hourly_rate,
                'note'        => ($note != '' ? $note : null),
            ]);

            $_new_timer_id = $this->db->insert_id();

            if (get_option('auto_stop_tasks_timers_on_new_timer') == 1) {
                $this->db->where('id !=', $_new_timer_id);
                $this->db->where('end_time IS NULL');
                $this->db->where('task_id !=', '0');
                $this->db->where('staff_id', get_staff_user_id());
                $this->db->update(db_prefix() . 'taskstimers', [
                    'end_time' => time(),
                    'note'     => ($note != '' ? $note : null),
                ]);
            }

            if ($task_id != '0'
                && get_option('timer_started_change_status_in_progress') == '1'
                && total_rows(db_prefix() . 'tasks', ['id' => $task_id, 'status' => 1]) > 0) {
                $this->mark_as(4, $task_id);
            }

            hooks()->do_action('task_timer_started', ['task_id' => $task_id, 'timer_id' => $_new_timer_id]);

            return true;
        }

        if ($timer) {
            // time already ended
            if ($timer->end_time != null) {
                return false;
            }
            $this->db->where('id', $timer_id);
            $this->db->update(db_prefix() . 'taskstimers', [
                    'end_time' => time(),
                    'task_id'  => $task_id,
                    'note'     => ($note != '' ? $note : null),
                ]);
        }

        return true;
    }

    public function timesheet($data)
    {
        if (isset($data['timesheet_duration']) && $data['timesheet_duration'] != '') {
            $duration_array = explode(':', $data['timesheet_duration']);
            $hour           = $duration_array[0];
            $minutes        = $duration_array[1];
            $end_time       = time();
            $start_time     = strtotime('-' . $hour . ' hour -' . $minutes . ' minutes');
        } else {
            $start_time = to_sql_date($data['start_time'], true);
            $end_time   = to_sql_date($data['end_time'], true);
            $start_time = strtotime($start_time);
            $end_time   = strtotime($end_time);
        }

        if ($end_time < $start_time) {
            return [
                'end_time_smaller' => true,
            ];
        }

        $timesheet_staff_id = get_staff_user_id();
        if (isset($data['timesheet_staff_id']) && $data['timesheet_staff_id'] != '') {
            $timesheet_staff_id = $data['timesheet_staff_id'];
        }

        if (!isset($data['timer_id']) || (isset($data['timer_id']) && $data['timer_id'] == '')) {

            // Stop all other timesheets when adding new timesheet
            $this->db->where('task_id', $data['timesheet_task_id']);
            $this->db->where('staff_id', $timesheet_staff_id);
            $this->db->where('end_time IS NULL');
            $this->db->update(db_prefix() . 'taskstimers', [
                'end_time' => time(),
            ]);

            $this->db->select('hourly_rate');
            $this->db->from(db_prefix() . 'staff');
            $this->db->where('staffid', $timesheet_staff_id);
            $hourly_rate = $this->db->get()->row()->hourly_rate;

            $this->db->insert(db_prefix() . 'taskstimers', [
                'start_time'  => $start_time,
                'end_time'    => $end_time,
                'staff_id'    => $timesheet_staff_id,
                'task_id'     => $data['timesheet_task_id'],
                'hourly_rate' => $hourly_rate,
                'note'        => (isset($data['note']) && $data['note'] != '' ? nl2br($data['note']) : null),
            ]);

            $insert_id = $this->db->insert_id();
            $tags      = '';

            if (isset($data['tags'])) {
                $tags = $data['tags'];
            }

            handle_tags_save($tags, $insert_id, 'timesheet');

            if ($insert_id) {
                $this->db->select('rel_type,rel_id,name,visible_to_client');
                $this->db->where('id', $data['timesheet_task_id']);
                $task = $this->db->get(db_prefix() . 'tasks')->row();

                if ($task->rel_type == 'project') {
                    $total      = $end_time - $start_time;
                    $additional = '<seconds>' . $total . '</seconds>';
                    $additional .= '<br />';
                    $additional .= '<lang>project_activity_task_name</lang> ' . $task->name;
                    $this->projects_model->log_activity($task->rel_id, 'project_activity_recorded_timesheet', $additional, $task->visible_to_client);
                }

                return true;
            }

            return false;
        }
        $affectedRows = 0;
        $this->db->where('id', $data['timer_id']);
        $this->db->update(db_prefix() . 'taskstimers', [
                'start_time' => $start_time,
                'end_time'   => $end_time,
                'staff_id'   => $timesheet_staff_id,
                'task_id'    => $data['timesheet_task_id'],
                'note'       => (isset($data['note']) && $data['note'] != '' ? nl2br($data['note']) : null),
            ]);

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }

        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $data['timer_id'], 'timesheet')) {
                $affectedRows++;
            }
        }

        return ($affectedRows > 0 ? true : false);
    }

    public function get_timers($task_id, $where = [])
    {
        $this->db->where($where);
        $this->db->where('task_id', $task_id);
        $this->db->order_by('start_time', 'DESC');

        return $this->db->get(db_prefix() . 'taskstimers')->result_array();
    }

    public function get_task_timer($where)
    {
        $this->db->where($where);

        return $this->db->get(db_prefix() . 'taskstimers')->row();
    }

    public function is_timer_started($task_id, $staff_id = '')
    {
        if ($staff_id == '') {
            $staff_id = get_staff_user_id();
        }

        $timer = $this->get_last_timer($task_id, $staff_id);

        if (!$timer) {
            return false;
        }

        if ($timer->end_time != null) {
            return false;
        }

        return $timer;
    }

    public function is_timer_started_for_task($id, $where = [])
    {
        $this->db->where('task_id', $id);
        $this->db->where('end_time IS NULL');
        $this->db->where($where);
        $results = $this->db->count_all_results(db_prefix() . 'taskstimers');

        return $results > 0 ? true : false;
    }

    public function get_last_timer($task_id, $staff_id = '')
    {
        if ($staff_id == '') {
            $staff_id = get_staff_user_id();
        }
        $this->db->where('staff_id', $staff_id);
        $this->db->where('task_id', $task_id);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $timer = $this->db->get(db_prefix() . 'taskstimers')->row();

        return $timer;
    }

    public function task_tracking_stats($id)
    {
        $loggers    = $this->db->query('SELECT DISTINCT(staff_id) FROM ' . db_prefix() . 'taskstimers WHERE task_id=' . $id)->result_array();
        $labels     = [];
        $labels_ids = [];
        foreach ($loggers as $assignee) {
            array_push($labels, get_staff_full_name($assignee['staff_id']));
            array_push($labels_ids, $assignee['staff_id']);
        }
        $chart = [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label' => _l('task_stats_logged_hours'),
                    'data'  => [],
                ],
            ],
        ];
        $i = 0;
        foreach ($labels_ids as $staffid) {
            $chart['datasets'][0]['data'][$i] = sec2qty($this->calc_task_total_time($id, ' AND staff_id=' . $staffid));
            $i++;
        }

        return $chart;
    }

    public function get_timesheeets($task_id)
    {
        return $this->db->query("SELECT id,note,start_time,end_time,task_id,staff_id, CONCAT(firstname, ' ', lastname) as full_name,
        end_time - start_time time_spent FROM " . db_prefix() . 'taskstimers JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid=' . db_prefix() . "taskstimers.staff_id WHERE task_id = '$task_id' ORDER BY start_time DESC")->result_array();
    }

    public function get_time_spent($seconds)
    {
        $minutes = $seconds / 60;
        $hours   = $minutes / 60;
        if ($minutes >= 60) {
            return round($hours, 2);
        } elseif ($seconds > 60) {
            return round($minutes, 2);
        }

        return $seconds;
    }

    public function calc_task_total_time($task_id, $where = '')
    {
        $sql = get_sql_calc_task_logged_time($task_id) . $where;

        $result = $this->db->query($sql)->row();

        if ($result) {
            return $result->total_logged_time;
        }

        return 0;
    }

    public function get_unique_member_logged_task_ids($staff_id, $where = '')
    {
        $sql = 'SELECT DISTINCT(task_id)
        FROM ' . db_prefix() . 'taskstimers WHERE staff_id =' . $staff_id . $where;

        return $this->db->query($sql)->result();
    }

    /**
     * @deprecated
     */
    private function _cal_total_logged_array_from_timers($timers)
    {
        $total = [];
        foreach ($timers as $key => $timer) {
            $_tspent = 0;
            if (is_null($timer->end_time)) {
                $_tspent = time() - $timer->start_time;
            } else {
                $_tspent = $timer->end_time - $timer->start_time;
            }
            $total[] = $_tspent;
        }

        return array_sum($total);
    }

    public function delete_timesheet($id)
    {
        $this->db->where('id', $id);
        $timesheet = $this->db->get(db_prefix() . 'taskstimers')->row();
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'taskstimers');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'timesheet');
            $this->db->delete(db_prefix() . 'taggables');

            $this->db->select('rel_type,rel_id,name,visible_to_client');
            $this->db->where('id', $timesheet->task_id);
            $task = $this->db->get(db_prefix() . 'tasks')->row();

            if ($task->rel_type == 'project') {
                $additional_data = $task->name;
                $total           = $timesheet->end_time - $timesheet->start_time;
                $additional_data .= '<br /><seconds>' . $total . '</seconds>';
                $this->projects_model->log_activity($task->rel_id, 'project_activity_task_timesheet_deleted', $additional_data, $task->visible_to_client);
            }

            hooks()->do_action('task_timer_deleted', $timesheet);

            log_activity('Timesheet Deleted [' . $id . ']');

            return true;
        }

        return false;
    }

    public function get_reminders($task_id)
    {
        $this->db->where('rel_id', $task_id);
        $this->db->where('rel_type', 'task');
        $this->db->order_by('isnotified,date', 'ASC');

        return $this->db->get(db_prefix() . 'reminders')->result_array();
    }

    public function can_staff_access_task($staff_id, $task_id)
    {
        $retVal              = false;
        $staffCanAccessTasks = $this->get_staff_members_that_can_access_task($task_id);
        foreach ($staffCanAccessTasks as $staff) {
            if ($staff['staffid'] == $staff_id) {
                $retVal = true;

                break;
            }
        }

        return $retVal;
    }

    public function get_staff_members_that_can_access_task($task_id)
    {
        return $this->db->query('SELECT * FROM ' . db_prefix() . 'staff
            WHERE (
                    admin=1
                    OR staffid IN (SELECT staffid FROM ' . db_prefix() . "task_assigned WHERE taskid='.$task_id.')
                    OR staffid IN (SELECT staffid FROM " . db_prefix() . "task_followers WHERE taskid='.$task_id.')
                    OR staffid IN (SELECT addedfrom FROM " . db_prefix() . "tasks WHERE id='.$task_id.' AND is_added_from_contact=0)
                    OR staffid IN(SELECT staff_id FROM " . db_prefix() . 'staff_permissions WHERE feature = "tasks" AND capability="view")
                )
            AND active=1')->result_array();
    }

    private function should_staff_receive_notification($staffid, $taskid)
    {
        if (!$this->can_staff_access_task($staffid, $taskid)) {
            return false;
        }

        return ($this->is_task_assignee($staffid, $taskid)
                || $this->is_task_follower($staffid, $taskid)
                || $this->is_task_creator($staffid, $taskid)
                || $this->staff_has_commented_on_task($staffid, $taskid));
    }
}
