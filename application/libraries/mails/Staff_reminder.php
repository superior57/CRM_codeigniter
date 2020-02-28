<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Staff_reminder extends App_mail_template
{
    protected $for = 'staff';

    protected $staff_email;

    protected $staffid;

    protected $reminder;

    public $slug = 'reminder-email-staff';

    public $rel_type = 'staff';

    public function __construct($staff_email, $staffid, $reminder)
    {
        parent::__construct();
        $this->staff_email = $staff_email;
        $this->staffid    = $staffid;
        $this->reminder    = $reminder;

        $this->ci->load->library('merge_fields/staff_merge_fields');

        // For SMS
        $this->set_merge_fields('staff_merge_fields', $this->staffid);
        $this->set_merge_fields($this->ci->staff_merge_fields->reminder($this->reminder));
    }

    public function build()
    {
        $this->to($this->staff_email)
        ->set_rel_id($this->staffid);
    }
}
