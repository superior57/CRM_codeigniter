<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Staff_password_resetted extends App_mail_template
{
    protected $for = 'staff';

    protected $staff_email;

    protected $staffid;

    public $slug = 'staff-password-reseted';

    public $rel_type = 'staff';

    public function __construct($staff_email, $staffid)
    {
        parent::__construct();
        $this->staff_email = $staff_email;
        $this->staffid    = $staffid;
    }

    public function build()
    {
        $this->to($this->staff_email)
        ->set_rel_id($this->staffid)
        ->set_merge_fields('staff_merge_fields', $this->staffid);
    }
}
