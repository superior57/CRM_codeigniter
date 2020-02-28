<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Customer_new_registration_to_admins extends App_mail_template
{
    protected $for = 'staff';

    protected $staff_email;

    protected $client_id;

    protected $staffid;

    public $slug = 'new-client-registered-to-admin';

    public $rel_type = 'staff';

    public function __construct($staff_email, $client_id, $staffid)
    {
        parent::__construct();

        $this->staff_email = $staff_email;
        $this->client_id   = $client_id;
        $this->staffid    = $staffid;
    }

    public function build()
    {
        $primary_contact_id = get_primary_contact_user_id($this->client_id);

        $this->to($this->staff_email)
        ->set_rel_id($this->staffid)
        ->set_merge_fields('client_merge_fields', $this->client_id, $primary_contact_id);
    }
}
