<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Subscription_payment_succeeded extends App_mail_template
{
    protected $for = 'customer';

    protected $contact;

    protected $invoice;

    protected $subscription_id;

    protected $payment_id;

    public $slug = 'subscription-payment-succeeded';

    public $rel_type = 'subscription';

    public function __construct($contact, $invoice, $subscription_id, $payment_id)
    {
        parent::__construct();

        $this->contact         = $contact;
        $this->invoice         = $invoice;
        $this->subscription_id = $subscription_id;
        $this->payment_id      = $payment_id;


        // For SMS
        $this->set_merge_fields('subscriptions_merge_fields', $this->subscription_id);
        $this->set_merge_fields('client_merge_fields', $this->invoice->clientid, $this->contact['id']);
        $this->set_merge_fields('invoice_merge_fields', $this->invoice->id, $this->payment_id);
    }

    public function build()
    {
        $this->to($this->contact['email'])
        ->set_rel_id($this->subscription_id);
    }
}
