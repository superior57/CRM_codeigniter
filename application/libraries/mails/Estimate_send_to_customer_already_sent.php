<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Estimate_send_to_customer_already_sent extends App_mail_template
{
    protected $for = 'customer';

    protected $estimate;

    protected $contact;

    public $slug = 'estimate-already-send';

    public $rel_type = 'estimate';

    public function __construct($estimate, $contact, $cc = '')
    {
        parent::__construct();

        $this->estimate = $estimate;
        $this->contact = $contact;
        $this->cc      = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->estimates_model->get_attachments($this->estimate->id, $attachment);
                $this->add_attachment([
                                'attachment' => get_upload_path_by_type('estimate') . $this->estimate->id . '/' . $_attachment->file_name,
                                'filename'   => $_attachment->file_name,
                                'type'       => $_attachment->filetype,
                                'read'       => true,
                            ]);
            }
        }

        $this->to($this->contact->email)
        ->set_rel_id($this->estimate->id)
        ->set_merge_fields('client_merge_fields', $this->estimate->clientid, $this->contact->id)
        ->set_merge_fields('estimate_merge_fields', $this->estimate->id);
    }
}
