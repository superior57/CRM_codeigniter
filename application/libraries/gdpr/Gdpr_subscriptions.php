<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_subscriptions
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($customer_id)
    {
        $this->ci->db->where('clientid', $customer_id);
        $subscriptions = $this->ci->db->get(db_prefix().'subscriptions')->result_array();

        $this->ci->load->model('currencies_model');
        foreach ($subscriptions as $subscriptionsKey => $subscription) {
            $subscriptions[$subscriptionsKey]['currency'] = $this->ci->currencies_model->get($subscription['currency']);

            $subscriptions[$subscriptionsKey]['tax'] = get_tax_by_id($subscription['tax_id']);
            unset($subscriptions[$subscriptionsKey]['tax_id']);

            $subscriptions[$subscriptionsKey]['tracked_emails'] = get_tracked_emails($subscription['id'], 'subscription');
        }

        return $subscriptions;
    }
}
