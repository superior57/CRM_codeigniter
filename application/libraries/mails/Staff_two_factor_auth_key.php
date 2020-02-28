<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Staff_two_factor_auth_key extends App_mail_template
{
    protected $for = 'staff';

    protected $staff;

    public $slug = 'two-factor-authentication';

    public $rel_type = 'staff';

    public function __construct($staff)
    {
        parent::__construct();
        $this->staff = $staff;
    }

    public function build()
    {
        $this->to($this->staff->email)
        ->set_rel_id($this->staff->staffid)
        ->set_merge_fields('staff_merge_fields', $this->staff->staffid);
    }
}
