<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Contract_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $contract;

    protected $contact;

    public $slug = 'send-contract';

    public $rel_type = 'contract';

    public function __construct($contract, $contact, $cc = '')
    {
        parent::__construct();

        $this->contract = $contract;
        $this->contact  = $contact;
        $this->cc       = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->contracts_model->get_contract_attachments($attachment);
                $this->add_attachment([
                                'attachment' => get_upload_path_by_type('contract') . $this->contract->id . '/' . $_attachment->file_name,
                                'filename'   => $_attachment->file_name,
                                'type'       => $_attachment->filetype,
                                'read'       => true,
                            ]);
            }
        }

        $this->to($this->contact->email)
        ->set_rel_id($this->contract->id)
        ->set_merge_fields('client_merge_fields', $this->contract->client, $this->contact->id)
        ->set_merge_fields('contract_merge_fields', $this->contract->id);
    }
}
