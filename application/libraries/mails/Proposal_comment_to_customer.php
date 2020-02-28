<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Proposal_comment_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $proposal;

    public $slug = 'proposal-comment-to-client';

    public $rel_type = 'proposal';

    public function __construct($proposal)
    {
        parent::__construct();

        $this->proposal = $proposal;

        // For SMS
        $this->set_merge_fields('proposals_merge_fields', $this->proposal->id);
    }

    public function build()
    {
        $this->to($this->proposal->email)
        ->set_rel_id($this->proposal->id);
    }
}
