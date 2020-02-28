<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Proposal_accepted_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $proposal;

    protected $staff_email;

    public $slug = 'proposal-client-accepted';

    public $rel_type = 'proposal';

    public function __construct($proposal, $staff_email)
    {
        parent::__construct();

        $this->proposal    = $proposal;
        $this->staff_email = $staff_email;
    }

    public function build()
    {
        $this->to($this->staff_email)
        ->set_rel_id($this->proposal->id)
        ->set_merge_fields('proposals_merge_fields', $this->proposal->id);
    }
}
