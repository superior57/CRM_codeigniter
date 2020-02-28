<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Staff_created extends App_mail_template
{
    protected $for = 'staff';

    protected $staff_email;

    protected $original_password;

    protected $staffid;

    public $slug = 'new-staff-created';

    public $rel_type = 'staff';

    public function __construct($staff_email, $staffid, $original_password)
    {
        parent::__construct();
        $this->staff_email       = $staff_email;
        $this->staffid           = $staffid;
        $this->original_password = $original_password;
    }

    public function build()
    {
        $this->to($this->staff_email)
        ->set_rel_id($this->staffid)
        ->set_merge_fields('staff_merge_fields', $this->staffid, $this->original_password);
    }
}
