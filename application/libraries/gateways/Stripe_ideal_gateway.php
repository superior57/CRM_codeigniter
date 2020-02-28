<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Stripe_ideal_gateway extends App_gateway
{
    public $webhookEndPoint;

    public $apiVersion = '2019-08-14';

    public $webhookEvents = ['source.chargeable', 'source.failed', 'source.canceled'];

    public function __construct()
    {
        $this->webhookEndPoint = site_url('gateways/stripe_ideal/webhook');

        /**
        * Call App_gateway __construct function
        */
        parent::__construct();

        /**
        * REQUIRED
        * Gateway unique id
        * The ID must be alpha/alphanumeric
        */
        $this->setId('stripe_ideal');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Stripe iDEAL');

        /**
         * Add gateway settings
        */
        $this->setSettings([
            [
                'name'      => 'api_secret_key',
                'encrypted' => true,
                'label'     => 'settings_paymentmethod_stripe_api_secret_key',
            ],
            [
                'name'  => 'api_publishable_key',
                'label' => 'settings_paymentmethod_stripe_api_publishable_key',
            ],
            [
                'name'          => 'description_dashboard',
                'label'         => 'settings_paymentmethod_description',
                'type'          => 'textarea',
                'default_value' => 'Payment for Invoice {invoice_number}',
            ],
            [
                'name'             => 'statement_descriptor',
                'label'            => 'ideal_customer_statement_descriptor',
                'type'             => 'textarea',
                'default_value'    => 'Payment for Invoice {invoice_number}',
                'field_attributes' => ['maxlength' => 22],
                'after'            => '<p class="mbot15">Statement descriptors are limited to 22 characters, cannot use the special characters <, >, \', ", or *, and must not consist solely of numbers.</p>',
            ],
            [
                'name'             => 'currencies',
                'label'            => 'settings_paymentmethod_currencies',
                'default_value'    => 'EUR',
                'field_attributes' => ['disabled' => true],
            ],
        ]);

        hooks()->add_action('before_render_payment_gateway_settings', 'stripe_ideal_gateway_webhook_check');
    }

    /**
     * Determine the Stripe environment based on the keys
     *
     * @return string
     */
    public function environment()
    {
        $environment = 'production';
        $apiKey      = $this->decryptSetting('api_secret_key');

        if (strpos($apiKey, 'sk_test') !== false) {
            $environment = 'test';
        }

        return $environment;
    }

    /**
     * Check whether the environment is test
     *
     * @return boolean
     */
    public function is_test()
    {
        return $this->environment() === 'test';
    }

    /**
     * Get the current webhook object based on the endpoint
     *
     * @return boolean|\Stripe\WebhookEndpoint
     */
    public function get_webhook_object()
    {
        \Stripe\Stripe::setApiVersion($this->apiVersion);
        \Stripe\Stripe::setApiKey($this->decryptSetting('api_secret_key'));

        $endpoints = \Stripe\WebhookEndpoint::all();
        $webhook   = false;

        foreach ($endpoints->data as $endpoint) {
            if ($endpoint->url == $this->webhookEndPoint) {
                $webhook = $endpoint;

                break;
            }
        }

        return $webhook;
    }

    public function create_webhook()
    {
        \Stripe\Stripe::setApiVersion($this->apiVersion);
        \Stripe\Stripe::setApiKey($this->decryptSetting('api_secret_key'));

        $webhook = \Stripe\WebhookEndpoint::create([
            'url'            => $this->webhookEndPoint,
            'enabled_events' => $this->webhookEvents,
        ]);

        update_option('stripe_ideal_webhook_id', $webhook->id);
        update_option('stripe_ideal_webhook_signing_secret', $webhook->secret);

        return $webhook;
    }

    public function get_source($id)
    {
        \Stripe\Stripe::setApiVersion($this->apiVersion);
        \Stripe\Stripe::setApiKey($this->decryptSetting('api_secret_key'));

        return \Stripe\Source::retrieve($id);
    }

    public function charge($source, $amount, $invoice_id)
    {
        \Stripe\Stripe::setApiVersion($this->apiVersion);
        \Stripe\Stripe::setApiKey($this->decryptSetting('api_secret_key'));

        return \Stripe\Charge::create([
                'currency'    => 'eur',
                'amount'      => $amount,
                'source'      => $source,
                'description' => str_replace('{invoice_number}', format_invoice_number($invoice_id), $this->getSetting('description_dashboard')),
                'metadata'    => [
                    'invoice_id'        => $invoice_id,
                    'pcrm-stripe-ideal' => true,
                ],
            ]);
    }

    public function finish_payment($charge)
    {
        $success = $this->addPayment(
            [
                'amount'        => ($charge->amount / 100),
                'invoiceid'     => $charge->metadata->invoice_id,
                'transactionid' => $charge->id,
                'paymentmethod' => strtoupper($charge->source->ideal->bank),
            ]
        );

        return (bool) $success;
    }

    public function process_payment($data)
    {
        $name = $data['invoice']->client->company;
        // Address information
        $country = '';

        $db_country = get_country_short_name($data['invoice']->billing_country);
        if ($db_country != '') {
            $country = $db_country;
        }

        $city        = $data['invoice']->billing_city;
        $line1       = $data['invoice']->billing_street;
        $postal_code = $data['invoice']->billing_zip;
        $state       = $data['invoice']->billing_state;

        $address = [
            'city'        => "$city",
            'country'     => "$country",
            'line1'       => "$line1",
            'postal_code' => "$postal_code",
            'state'       => "$state",
        ];

        $stripe_data = [
            'type'  => 'ideal',
            'ideal' => [
                'statement_descriptor' => str_replace('{invoice_number}', format_invoice_number($data['invoice']->id), $this->getSetting('statement_descriptor')),
            ],
            'amount'   => $data['amount'] * 100,
            'currency' => 'eur',

            'owner' => [
                'name'    => $name,
                'address' => $address,
            ],

            'redirect' => [
                'return_url' => site_url('gateways/stripe_ideal/response/' . $data['invoice']->id . '/' . $data['invoice']->hash),
            ],

            'metadata' => [
                'invoice_id'        => $data['invoice']->id,
                'pcrm-stripe-ideal' => true,
            ],
        ];

        try {
            \Stripe\Stripe::setApiVersion($this->apiVersion);
            \Stripe\Stripe::setApiKey($this->decryptSetting('api_secret_key'));

            $source = \Stripe\Source::create($stripe_data);

            if ($source->created != '') {
                redirect($source->redirect->url);
            } else {
                if (!empty($source->failure_reason)) {
                    set_alert('warning', $source->failure_reason);
                }
            }
        } catch (Exception $e) {
            set_alert('warning', $e->getMessage());
        }

        redirect(site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash));
    }
}

function stripe_ideal_gateway_webhook_check($gateway)
{
    if ($gateway['id'] === 'stripe_ideal') {
        $CI = &get_instance();

        if (!empty($CI->stripe_ideal_gateway->decryptSetting('api_secret_key')) && $gateway['active'] == '1') {
            $webhook     = $CI->stripe_ideal_gateway->get_webhook_object();
            $environment = $CI->stripe_ideal_gateway->environment();
            $endpoint    = $CI->stripe_ideal_gateway->webhookEndPoint;

            if ($CI->session->has_userdata('stripe-ideal-webhook-failure')) {
                echo '<div class="alert alert-warning" style="margin-bottom:15px;">';
                echo 'The system was unable to create the <b>required</b> webhook endpoint for Stripe Ideal.';
                echo '<br />You should consider creating webhook manually directly via Stripe dashboard for your environment (' . $environment . ')';
                echo '<br /><br /><b>Webhook URL:</b><br />' . $endpoint;
                echo '<br /><br /><b>Webhook events:</b><br />' . implode(',<br />', $CI->stripe_ideal_gateway->webhookEvents());
                echo '</div>';
            }

            if (!$webhook || !startsWith($webhook->url, site_url())) {
                echo '<div class="alert alert-warning">';
                echo 'Webhook endpoint (' . $endpoint . ') not found for ' . $environment . ' environment.';
                echo '<br />Click <a href="' . site_url('gateways/stripe_ideal/create_webhook') . '">here</a> to create the webhook directly in Stripe.';
                echo '</div>';
            }
        }
    }
}
