<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Proposal_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $proposal;

    protected $attach_pdf;

    public $slug = 'proposal-send-to-customer';

    public $rel_type = 'proposal';

    public function __construct($proposal, $attach_pdf, $cc = '')
    {
        parent::__construct();

        $this->proposal   = $proposal;
        $this->attach_pdf = $attach_pdf;
        $this->cc         = $cc;
    }

    public function build()
    {
        if ($this->attach_pdf) {
            set_mailing_constant();
            $pdf    = proposal_pdf($this->proposal);
            $attach = $pdf->Output(slug_it($this->proposal->subject) . '.pdf', 'S');
            $this->add_attachment([
                'attachment' => $attach,
                'filename'   => slug_it($this->proposal->subject) . '.pdf',
                'type'       => 'application/pdf',
            ]);
        }

        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->proposals_model->get_attachments($this->proposal->id, $attachment);
                $this->add_attachment([
                    'attachment' => get_upload_path_by_type('proposal') . $this->proposal->id . '/' . $_attachment->file_name,
                    'filename'   => $_attachment->file_name,
                    'type'       => $_attachment->filetype,
                    'read'       => true,
                ]);
            }
        }

        $this->to($this->proposal->email)
        ->set_rel_id($this->proposal->id)
        ->set_merge_fields('proposals_merge_fields', $this->proposal->id);
    }
}
