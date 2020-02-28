<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Subscriptions_merge_fields extends App_merge_fields
{
    public function build()
    {
        return  [
                [
                    'name'      => 'Subscription ID',
                    'key'       => '{subscription_id}',
                    'available' => [
                        'subscriptions',
                    ],
                ],
                [
                    'name'      => 'Subscription Name',
                    'key'       => '{subscription_name}',
                    'available' => [
                        'subscriptions',
                    ],
                ],
                [
                    'name'      => 'Subscription Description',
                    'key'       => '{subscription_description}',
                    'available' => [
                        'subscriptions',
                    ],
                ],
                [
                    'name'      => 'Subscription Subscribe Link',
                    'key'       => '{subscription_link}',
                    'available' => [
                        'subscriptions',
                    ],
                ],
                [
                    'name'      => 'Subscription Authorization Link',
                    'key'       => '{subscription_authorize_payment_link}',
                    'available' => [
                    ],
                    'templates' => ['subscription-payment-requires-action'],
                ],
            ];
    }

    /**
     * Subscription merge fields merge fields
     * @param  mixed id
     * @return array
     */
    public function format($id, $confirmation_link = '')
    {
        if (!class_exists('subscriptions_model')) {
            $this->ci->load->model('subscriptions_model');
        }
        $fields       = [];
        $subscription = $this->ci->subscriptions_model->get_by_id($id);

        if (!$subscription) {
            return $fields;
        }

        $fields['{subscription_authorize_payment_link}'] = '';

        if ($confirmation_link) {
            $fields['{subscription_authorize_payment_link}'] = $confirmation_link;
        }

        $fields['{subscription_link}']        = site_url('subscription/' . $subscription->hash);
        $fields['{subscription_id}']          = $subscription->id;
        $fields['{subscription_name}']        = $subscription->name;
        $fields['{subscription_description}'] = $subscription->description;

        return hooks()->apply_filters('subscription_merge_fields', $fields, [
        'id'           => $id,
        'subscription' => $subscription,
     ]);
    }
}
