<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Subscription_payment_requires_action extends App_mail_template
{
    protected $for = 'customer';

    protected $subscription;

    protected $contact;

    protected $confirmation_link;

    public $slug = 'subscription-payment-requires-action';

    public $rel_type = 'subscription';

    public function __construct($subscription, $contact, $confirmation_link, $cc = '')
    {
        parent::__construct();

        $this->subscription      = $subscription;
        $this->contact           = $contact;
        $this->confirmation_link = $confirmation_link;
        $this->cc                = $cc;
    }

    public function build()
    {
        $this->to($this->contact->email)
        ->set_rel_id($this->subscription->id)
        ->set_merge_fields('subscriptions_merge_fields', $this->subscription->id, $this->confirmation_link)
        ->set_merge_fields('client_merge_fields', $this->subscription->clientid, $this->contact->id);
    }
}
