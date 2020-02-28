<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_removal_request_by_customer extends App_mail_template
{
    protected $for = 'staff';

    protected $staff;

    protected $user_id;

    public $slug = 'gdpr-removal-request';

    public function __construct($staff, $user_id)
    {
        parent::__construct();

        $this->staff   = $staff;
        $this->user_id = $user_id;
    }

    public function build()
    {
        $this->to($this->staff['email'])
        ->set_merge_fields('staff_merge_fields', $this->staff['staffid'])
        ->set_merge_fields('client_merge_fields', get_user_id_by_contact_id($this->user_id), $this->user_id);
    }
}
