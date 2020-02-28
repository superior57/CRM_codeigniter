<?php

defined('BASEPATH') or exit('No direct script access allowed');


class Lead_assigned extends App_mail_template
{
    protected $for = 'staff';

    protected $lead_id;

    protected $staff_email;

    public $slug = 'new-lead-assigned';

    public $rel_type = 'lead';

    public function __construct($lead_id, $staff_email)
    {
        parent::__construct();
        $this->lead_id     = $lead_id;
        $this->staff_email = $staff_email;
    }

    public function build()
    {
        $this->to($this->staff_email)
        ->set_rel_id($this->lead_id)
        ->set_merge_fields('leads_merge_fields', $this->lead_id);
    }
}
