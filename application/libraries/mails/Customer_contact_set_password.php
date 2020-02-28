<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Customer_contact_set_password extends App_mail_template
{
    protected $for = 'customer';

    protected $contact;

    protected $password_data;

    public $slug = 'contact-set-password';

    public $rel_type = 'contact';

    public function __construct($contact, $password_data)
    {
        parent::__construct();

        $this->contact       = $contact;
        $this->password_data = $password_data;
    }

    public function build()
    {
        $this->ci->load->library('merge_fields/client_merge_fields');

        $this->to($this->contact->email)
        ->set_rel_id($this->contact->id)
        ->set_merge_fields('client_merge_fields', $this->contact->userid, $this->contact->id)
        ->set_merge_fields($this->ci->client_merge_fields->password($this->password_data, 'set'));
    }
}
