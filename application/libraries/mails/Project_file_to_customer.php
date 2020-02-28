<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Project_file_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $project;

    protected $contact;

    protected $additional_data;

    public $slug = 'new-project-file-uploaded-to-customer';

    public $rel_type = 'project';

    public function __construct($project, $contact, $additional_data)
    {
        parent::__construct();
        $this->project         = $project;
        $this->contact           = $contact;
        $this->additional_data = $additional_data;
    }

    public function build()
    {
        $this->to($this->contact['email'])
        ->set_rel_id($this->project->id)
        ->set_merge_fields('client_merge_fields', $this->project->clientid, $this->contact['id'])
        ->set_merge_fields('projects_merge_fields', $this->project->id, $this->additional_data);
    }
}
