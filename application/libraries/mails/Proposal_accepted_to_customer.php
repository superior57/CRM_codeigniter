<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Proposal_accepted_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $proposal;

    public $slug = 'proposal-client-thank-you';

    public $rel_type = 'proposal';

    public function __construct($proposal)
    {
        parent::__construct();

        $this->proposal = $proposal;
    }

    public function build()
    {
        $this->to($this->proposal->email)
        ->set_rel_id($this->proposal->id)
        ->set_merge_fields('proposals_merge_fields', $this->proposal->id);
    }
}
