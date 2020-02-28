<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Project_staff_added_as_member extends App_mail_template
{
    protected $for = 'staff';

    protected $project_id;

    protected $client_id;

    protected $staff;

    public $slug = 'staff-added-as-project-member';

    public $rel_type = 'project';

    public function __construct($staff, $project_id, $client_id)
    {
        parent::__construct();

        $this->staff      = $staff;
        $this->project_id = $project_id;
        $this->client_id  = $client_id;
    }

    public function build()
    {
        $this->to($this->staff['email'])
        ->set_rel_id($this->project_id)
        ->set_merge_fields('client_merge_fields', $this->client_id)
        ->set_merge_fields('staff_merge_fields', $this->staff['staff_id'])
        ->set_merge_fields('projects_merge_fields', $this->project_id);
    }
}
