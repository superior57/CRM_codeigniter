<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Stripe_gateway extends App_gateway
{
    public $webhookEndPoint;

    public function __construct()
    {
        $this->webhookEndPoint = site_url('gateways/stripe/webhook_endpoint');

        /**
        * Call App_gateway __construct function
        */
        parent::__construct();

        /**
        * REQUIRED
        * Gateway unique id
        * The ID must be alpha/alphanumeric
        */
        $this->setId('stripe');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Stripe Checkout');

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
                'name'          => 'currencies',
                'label'         => 'settings_paymentmethod_currencies',
                'default_value' => 'USD,CAD',
            ],
            [
                'name'          => 'allow_primary_contact_to_update_credit_card',
                'type'          => 'yes_no',
                'default_value' => 1,
                'label'         => 'allow_primary_contact_to_update_credit_card',
            ],
        ]);

        hooks()->add_action('before_render_payment_gateway_settings', 'stripe_gateway_webhook_check');
    }

    /**
     * Get the current webhook object based on the endpoint
     *
     * @return boolean|\Stripe\WebhookEndpoint
     */
    public function get_webhook_object()
    {
        if (!class_exists('stripe_core', false)) {
            $this->ci->load->library('stripe_core');
        }

        $endpoints = $this->ci->stripe_core->list_webhook_endpoints();
        $webhook   = false;

        foreach ($endpoints->data as $endpoint) {
            if ($endpoint->url == $this->webhookEndPoint) {
                $webhook = $endpoint;

                break;
            }
        }

        return $webhook;
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
     * Process the payment
     *
     * @param  array $data
     *
     * @return mixed
     */
    public function process_payment($data)
    {
        $this->ci->load->library('stripe_core');

        $description = str_replace('{invoice_number}', format_invoice_number($data['invoiceid']), $this->getSetting('description_dashboard'));

        $items = [
                'name'     => $description,
                'amount'   => strcasecmp($data['invoice']->currency_name, 'JPY') == 0 ? intval($data['amount']) : $data['amount'] * 100,
                'currency' => strtolower($data['invoice']->currency_name),
                'quantity' => 1,
        ];

        $successUrl = site_url('gateways/stripe/success/' . $data['invoice']->id . '/' . $data['invoice']->hash);
        $cancelUrl  = site_url('invoice/' . $data['invoiceid'] . '/' . $data['invoice']->hash);

        $sessionData = [
              'payment_method_types' => ['card'],
              'line_items'           => [$items],
              'success_url'          => $successUrl,
              'cancel_url'           => $cancelUrl,
              'payment_intent_data'  => [
                  'description' => $description,
                  'metadata'    => [
                        'ClientId'  => $data['invoice']->clientid,
                        'InvoiceId' => $data['invoice']->id,
                ],
              ],
        ];

        if ($data['invoice']->client->stripe_id) {
            $sessionData['customer'] = $data['invoice']->client->stripe_id;
        }

        if (is_client_logged_in() && !$data['invoice']->client->stripe_id) {
            $contact = $this->ci->clients_model->get_contact(get_contact_user_id());
            if ($contact->email) {
                $sessionData['customer_email'] = $contact->email;
            }
        }

        try {
            $session = $this->ci->stripe_core->create_session($sessionData);
        } catch (Exception $e) {
            set_alert('warning', $e->getMessage());
            redirect(site_url('invoice/' . $data['invoiceid'] . '/' . $data['hash']));
        }

        redirect_to_stripe_checkout($session->id);
    }
}

function stripe_gateway_webhook_check($gateway)
{
    if ($gateway['id'] === 'stripe') {
        $CI = &get_instance();

        $CI->load->library('stripe_core');

        if ($CI->stripe_core->has_api_key() && $gateway['active'] == '1') {
            $webhook     = $CI->stripe_gateway->get_webhook_object();
            $environment = $CI->stripe_gateway->environment();
            $endpoint    = $CI->stripe_gateway->webhookEndPoint;

            if ($CI->session->has_userdata('stripe-webhook-failure')) {
                echo '<div class="alert alert-warning" style="margin-bottom:15px;">';
                echo 'The system was unable to create the <b>required</b> webhook endpoint for Stripe.';
                echo '<br />You should consider creating webhook manually directly via Stripe dashboard for your environment (' . $environment . ')';
                echo '<br /><br /><b>Webhook URL:</b><br />' . $endpoint;
                echo '<br /><br /><b>Webhook events:</b><br />' . implode(',<br />', $CI->stripe_core->get_webhook_events());
                echo '</div>';
            }

            if (!$webhook || !startsWith($webhook->url, site_url())) {
                echo '<div class="alert alert-warning">';
                echo 'Webhook endpoint (' . $endpoint . ') not found for ' . $environment . ' environment.';
                echo '<br />Click <a href="' . site_url('gateways/stripe/create_webhook') . '">here</a> to create the webhook directly in Stripe.';
                echo '</div>';
            }
        }
    }
}
