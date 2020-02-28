<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Task_assigned_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $staff_email;

    protected $assignee_id;

    protected $task_id;

    public $slug = 'task-assigned';

    public $rel_type = 'task';

    public function __construct($staff_email, $assignee_id, $task_id)
    {
        parent::__construct();

        $this->staff_email = $staff_email;
        $this->assignee_id    = $assignee_id;
        $this->task_id     = $task_id;
    }

    public function build()
    {

        $this->to($this->staff_email)
        ->set_rel_id($this->task_id)
        ->set_staff_id($this->assignee_id)
        ->set_merge_fields('staff_merge_fields', $this->assignee_id)
        ->set_merge_fields('tasks_merge_fields', $this->task_id);
    }
}
