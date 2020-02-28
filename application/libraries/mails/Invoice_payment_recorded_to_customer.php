<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Invoice_payment_recorded_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $contact;

    protected $invoice;

    protected $subscription;

    protected $payment_id;

    public $slug = 'invoice-payment-recorded';

    public $rel_type = 'invoice';

    public function __construct($contact, $invoice, $subscription, $payment_id)
    {
        parent::__construct();

        $this->contact      = $contact;
        $this->invoice      = $invoice;
        $this->subscription = $subscription;
        $this->payment_id   = $payment_id;
        // For SMS
        if ($this->subscription) {
            $this->set_merge_fields('subscriptions_merge_fields', $this->subscription);
        }

        $this->set_merge_fields('client_merge_fields', $this->invoice->clientid, $this->contact['id']);
        $this->set_merge_fields('invoice_merge_fields', $this->invoice->id, $this->payment_id);
    }

    public function build()
    {
        $this->to($this->contact['email'])
        ->set_rel_id($this->invoice->id);
    }
}
