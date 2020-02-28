<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Customer_statement extends App_mail_template
{
    protected $for = 'customer';

    protected $contact_email;

    protected $contact_id;

    protected $statement;

    public $slug = 'client-statement';

    public $rel_type = 'contact';

    public function __construct($contact_email, $contact_id, $statement, $cc = '')
    {
        parent::__construct();

        $this->contact_email = $contact_email;
        $this->contact_id    = $contact_id;
        $this->statement     = $statement;
        $this->cc            = $cc;
    }

    public function build()
    {
        $this->ci->load->library('merge_fields/client_merge_fields');

        $this->to($this->contact_email)
        ->set_rel_id($this->contact_id)
        ->set_merge_fields('client_merge_fields', $this->statement['client']->userid, $this->contact_id)
        ->set_merge_fields($this->ci->client_merge_fields->statement($this->statement));
    }
}
