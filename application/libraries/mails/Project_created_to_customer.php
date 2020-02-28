<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Project_created_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $project_id;

    protected $client_id;

    protected $contact;

    public $slug = 'assigned-to-project';

    public $rel_type = 'project';

    public function __construct($project_id, $client_id, $contact)
    {
        parent::__construct();
        $this->project_id = $project_id;
        $this->client_id  = $client_id;
        $this->contact    = $contact;
    }

    public function build()
    {
        $this->to($this->contact['email'])
        ->set_rel_id($this->project_id)
        ->set_merge_fields('client_merge_fields', $this->client_id, $this->contact['id'])
        ->set_merge_fields('projects_merge_fields',$this->project_id, [
                    'customer_template' => true,
                ]);
    }
}
