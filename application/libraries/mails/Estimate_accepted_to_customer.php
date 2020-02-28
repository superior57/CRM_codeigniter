<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Estimate_accepted_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $estimate;

    protected $contact;

    public $slug = 'estimate-thank-you-to-customer';

    public $rel_type = 'estimate';

    public function __construct($estimate, $contact)
    {
        parent::__construct();

        $this->estimate = $estimate;
        $this->contact  = $contact;
    }

    public function build()
    {
        $this->to($this->contact['email'])
        ->set_rel_id($this->estimate->id)
        ->set_merge_fields('client_merge_fields', $this->estimate->clientid, $this->contact['id'])
        ->set_merge_fields('estimate_merge_fields', $this->estimate->id);
    }
}
