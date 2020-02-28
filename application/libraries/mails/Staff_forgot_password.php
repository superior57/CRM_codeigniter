<?php

defined('BASEPATH') or exit('No direct script access allowed');


class Staff_forgot_password extends App_mail_template
{
    protected $for = 'staff';

    protected $staff_email;

    protected $staffid;

    protected $password_data;

    public $slug = 'staff-forgot-password';

    public $rel_type = 'staff';

    public function __construct($staff_email, $staffid, $password_data)
    {
        parent::__construct();
        $this->staff_email   = $staff_email;
        $this->staffid       = $staffid;
        $this->password_data = $password_data;
    }

    public function build()
    {
        $this->ci->load->library('merge_fields/staff_merge_fields');

        $this->to($this->staff_email)
        ->set_rel_id($this->staffid)
        ->set_merge_fields('staff_merge_fields', $this->staffid)
        ->set_merge_fields($this->ci->staff_merge_fields->password($this->password_data, 'forgot'));
    }
}
