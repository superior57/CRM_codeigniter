<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Contract_comment_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $contract;

    protected $staff;

    public $slug = 'contract-comment-to-admin';

    public $rel_type = 'contract';

    public function __construct($contract, $staff)
    {
        parent::__construct();

        $this->contract = $contract;
        $this->staff    = $staff;

        // For SMS
        $this->set_merge_fields('client_merge_fields', $this->contract->client);
        $this->set_merge_fields('contract_merge_fields', $this->contract->id);
        $this->set_merge_fields('staff_merge_fields', $this->staff['staffid']);
    }

    public function build()
    {
        $this->to($this->staff['email'])
        ->set_rel_id($this->contract->id);
    }
}
