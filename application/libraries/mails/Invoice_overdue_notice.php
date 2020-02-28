<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Invoice_overdue_notice extends App_mail_template
{
    protected $for = 'customer';

    protected $invoice;

    protected $contact;

    public $slug = 'invoice-overdue-notice';

    public $rel_type = 'invoice';

    public function __construct($invoice, $contact)
    {
        parent::__construct();

        $this->invoice = $invoice;
        $this->contact = $contact;

        // For SMS
        $this->set_merge_fields('client_merge_fields', $this->invoice->clientid, $this->contact['id']);
        $this->set_merge_fields('invoice_merge_fields', $this->invoice->id);
    }

    public function build()
    {
        $this->to($this->contact['email'])
        ->set_rel_id($this->invoice->id);
    }
}
