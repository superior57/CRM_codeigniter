<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Staff_event_notification extends App_mail_template
{
    protected $for = 'staff';

    protected $staffid;

    protected $event;

    public $slug = 'event-notification-to-staff';

    public $rel_type = 'staff';

    public function __construct($event, $staff)
    {
        parent::__construct();

        $this->staff = $staff;
        $this->event = $event;
    }

    public function build()
    {
        $this->set_merge_fields('staff_merge_fields', $this->staff->staffid);
        $this->set_merge_fields('event_merge_fields', $this->event);

        $this->to($this->staff->email)
        ->set_rel_id($this->staff->staffid);
    }
}
