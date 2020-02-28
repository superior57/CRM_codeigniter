<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Credit_note_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $credit_note;

    protected $contact;

    public $slug = 'credit-note-send-to-client';

    public $rel_type = 'credit_note';

    public function __construct($credit_note, $contact, $cc = '')
    {
        parent::__construct();

        $this->credit_note = $credit_note;
        $this->contact     = $contact;
        $this->cc          = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->misc_model->get_file($attachment);
                $this->add_attachment([
                                'attachment' => get_upload_path_by_type('credit_note') . $this->credit_note->id . '/' . $_attachment->file_name,
                                'filename'   => $_attachment->file_name,
                                'type'       => $_attachment->filetype,
                                'read'       => true,
                            ]);
            }
        }

        $this->to($this->contact->email)
        ->set_rel_id($this->credit_note->id)
        ->set_merge_fields('client_merge_fields', $this->credit_note->clientid, $this->contact->id)
        ->set_merge_fields('credit_note_merge_fields', $this->credit_note->id);
    }
}
