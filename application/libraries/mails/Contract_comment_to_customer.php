<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Contract_comment_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $contract;

    protected $contact;

    public $slug = 'contract-comment-to-client';

    public $rel_type = 'contract';

    public function __construct($contract, $contact)
    {
        parent::__construct();

        $this->contract = $contract;
        $this->contact  = $contact;
        // For SMS
        $this->set_merge_fields('client_merge_fields', $this->contract->client, $this->contact['id']);
        $this->set_merge_fields('contract_merge_fields', $this->contract->id);
    }

    public function build()
    {
        $this->to($this->contact['email'])
        ->set_rel_id($this->contract->id);
    }
}
