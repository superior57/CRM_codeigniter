<?php

defined('BASEPATH') or exit('No direct script access allowed');


class Customer_contact_forgot_password extends App_mail_template
{
    protected $for = 'customer';

    protected $contact_email;

    protected $contact_id;

    protected $client_id;

    protected $password_data;

    public $slug = 'contact-forgot-password';

    public $rel_type = 'contact';

    public function __construct($contact_email, $client_id, $contact_id, $password_data)
    {
        parent::__construct();

        $this->contact_email = $contact_email;
        $this->contact_id    = $contact_id;
        $this->client_id     = $client_id;
        $this->password_data = $password_data;
    }

    public function build()
    {
        $this->ci->load->library('merge_fields/client_merge_fields');

        $this->to($this->contact_email)
        ->set_rel_id($this->contact_id)
        ->set_merge_fields('client_merge_fields', $this->client_id, $this->contact_id)
        ->set_merge_fields($this->ci->client_merge_fields->password($this->password_data, 'forgot'));
    }
}
