<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Invoice_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $invoice;

    protected $contact;

    public $slug = 'invoice-send-to-client';

    public $rel_type = 'invoice';

    public function __construct($invoice, $contact, $cc = '')
    {
        parent::__construct();

        $this->invoice = $invoice;
        $this->contact = $contact;
        $this->cc      = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->invoices_model->get_attachments($this->invoice->id, $attachment);
                $this->add_attachment([
                                'attachment' => get_upload_path_by_type('invoice') . $this->invoice->id . '/' . $_attachment->file_name,
                                'filename'   => $_attachment->file_name,
                                'type'       => $_attachment->filetype,
                                'read'       => true,
                            ]);
            }
        }

        $this->to($this->contact->email)
        ->set_rel_id($this->invoice->id)
        ->set_merge_fields('client_merge_fields', $this->invoice->clientid, $this->contact->id)
        ->set_merge_fields('invoice_merge_fields', $this->invoice->id);
    }
}
