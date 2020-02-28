<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Task_new_comment_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $contact_email;

    protected $client_id;

    protected $contact_id;

    protected $task_id;

    public $slug = 'task-commented-to-contacts';

    public $rel_type = 'task';

    public function __construct($contact_email, $client_id, $contact_id, $task_id)
    {
        parent::__construct();

        $this->contact_email = $contact_email;
        $this->client_id     = $client_id;
        $this->contact_id    = $contact_id;
        $this->task_id       = $task_id;
    }

    public function build()
    {
        $this->to($this->contact_email)
        ->set_rel_id($this->task_id)
        ->set_merge_fields('client_merge_fields', $this->client_id, $this->contact_id)
        ->set_merge_fields('tasks_merge_fields', $this->task_id, true);
    }
}
