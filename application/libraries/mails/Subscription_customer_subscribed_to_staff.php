<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Subscription_customer_subscribed_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $subscription;

    protected $staff_email;

    public $slug = 'customer-subscribed-to-staff';

    public $rel_type = 'subscription';

    public function __construct($subscription, $staff_email)
    {
        parent::__construct();

        $this->subscription = $subscription;
        $this->staff_email  = $staff_email;
    }

    public function build()
    {
        $primary_contact = get_primary_contact_user_id($this->subscription->clientid);

        $this->to($this->staff_email)
        ->set_rel_id($this->subscription->id)
        ->set_merge_fields('subscriptions_merge_fields', $this->subscription->id)
        ->set_merge_fields('client_merge_fields', $this->subscription->clientid, $primary_contact);
    }
}
