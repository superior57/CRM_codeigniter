<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Subscription_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $subscription;

    protected $contact;

    public $slug = 'send-subscription';

    public $rel_type = 'subscription';

    public function __construct($subscription, $contact, $cc = '')
    {
        parent::__construct();

        $this->subscription = $subscription;
        $this->contact      = $contact;
        $this->cc           = $cc;
    }

    public function build()
    {
        $this->to($this->contact->email)
        ->set_rel_id($this->subscription->id)
        ->set_merge_fields('subscriptions_merge_fields', $this->subscription->id)
        ->set_merge_fields('client_merge_fields', $this->subscription->clientid, $this->contact->id);
    }
}
