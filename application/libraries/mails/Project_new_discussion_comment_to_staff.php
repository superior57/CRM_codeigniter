<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Project_new_discussion_comment_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $project;

    protected $staff;

    protected $additional_data;

    public $slug = 'new-project-discussion-comment-to-staff';

    public $rel_type = 'project';

    public function __construct($project, $staff, $additional_data)
    {
        parent::__construct();
        $this->project         = $project;
        $this->staff           = $staff;
        $this->additional_data = $additional_data;
    }

    public function build()
    {
        $this->to($this->staff['email'])
        ->set_rel_id($this->project->id)
        ->set_merge_fields('client_merge_fields', $this->project->clientid)
        ->set_merge_fields('staff_merge_fields', $this->staff['staff_id'])
        ->set_merge_fields('projects_merge_fields', $this->project->id, $this->additional_data);
    }
}
