<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Proposal_comment_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $proposal_id;

    protected $staff_email;

    public $slug = 'proposal-comment-to-admin';

    public $rel_type = 'proposal';

    public function __construct($proposal_id, $staff_email)
    {
        parent::__construct();

        $this->proposal_id = $proposal_id;
        $this->staff_email = $staff_email;

        // For SMS
        $this->set_merge_fields('proposals_merge_fields', $this->proposal_id);
    }

    public function build()
    {
        $this->to($this->staff_email)
        ->set_rel_id($this->proposal_id);
    }
}
