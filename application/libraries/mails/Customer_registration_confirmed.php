<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Customer_registration_confirmed extends App_mail_template
{
    protected $for = 'customer';

    protected $contact;

    public $slug = 'client-registration-confirmed';

    public $rel_type = 'contact';

    public function __construct($contact)
    {
        parent::__construct();
        $this->contact = $contact;
    }

    public function build()
    {
        $this->to($this->contact->email)
        ->set_rel_id($this->contact->id)
        ->set_merge_fields('client_merge_fields', $this->contact->userid, $this->contact->id);
    }
}
