<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Customer_created_welcome_mail extends App_mail_template
{
    protected $for = 'customer';

    protected $email;

    protected $client_id;

    protected $contact_id;

    protected $password;

    public $slug = 'new-client-created';

    public $rel_type = 'client';

    public function __construct($email, $client_id, $contact_id, $password)
    {
        parent::__construct();
        $this->email      = $email;
        $this->client_id  = $client_id;
        $this->contact_id = $contact_id;
        $this->password   = $password;
    }

    public function build()
    {
        $this->to($this->email)
        ->set_rel_id($this->client_id)
        ->set_merge_fields('client_merge_fields', $this->client_id, $this->contact_id, $this->password);
    }
}
