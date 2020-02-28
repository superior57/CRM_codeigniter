<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Task_added_as_follower_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $staff_email;

    protected $follower_id;

    protected $task_id;

    public $slug = 'task-added-as-follower';

    public $rel_type = 'task';

    public function __construct($staff_email, $follower_id, $task_id)
    {
        parent::__construct();

        $this->staff_email = $staff_email;
        $this->follower_id = $follower_id;
        $this->task_id     = $task_id;
    }

    public function build()
    {
        $this->to($this->staff_email)
        ->set_rel_id($this->task_id)
        ->set_staff_id($this->follower_id)
        ->set_merge_fields('staff_merge_fields', $this->follower_id)
        ->set_merge_fields('tasks_merge_fields', $this->task_id);
    }
}
