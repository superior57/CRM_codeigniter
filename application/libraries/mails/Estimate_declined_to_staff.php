<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Estimate_declined_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $estimate;

    protected $staff_email;

    protected $contact_id;

    public $slug = 'estimate-declined-to-staff';

    public $rel_type = 'estimate';

    public function __construct($estimate, $staff_email, $contact_id)
    {
        parent::__construct();

        $this->estimate    = $estimate;
        $this->staff_email = $staff_email;
        $this->contact_id  = $contact_id;
    }

    public function build()
    {

        $this->to($this->staff_email)
        ->set_rel_id($this->estimate->id)
        ->set_merge_fields('client_merge_fields', $this->estimate->clientid, $this->contact_id)
        ->set_merge_fields('estimate_merge_fields', $this->estimate->id);
    }
}
