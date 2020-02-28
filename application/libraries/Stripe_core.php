<?php

defined('BASEPATH') or exit('No direct script access allowed');
// For Stripe Checkout
class Stripe_core
{
    protected $ci;

    protected $secretKey;

    protected $publishableKey;

    protected $apiVersion = '2019-08-14';

    public function __construct()
    {
        $this->ci             = &get_instance();
        $this->secretKey      = $this->ci->stripe_gateway->decryptSetting('api_secret_key');
        $this->publishableKey = $this->ci->stripe_gateway->getSetting('api_publishable_key');

        \Stripe\Stripe::setApiVersion($this->apiVersion);
        \Stripe\Stripe::setApiKey($this->secretKey);
    }

    public function create_customer($data)
    {
        return \Stripe\Customer::create($data);
    }

    public function get_customer($id)
    {
        return \Stripe\Customer::retrieve($id);
    }

    public function update_customer($id, $payload)
    {
        return \Stripe\Customer::update($id, $payload);
    }

    public function get_publishable_key()
    {
        return $this->publishableKey;
    }

    public function list_webhook_endpoints()
    {
        return \Stripe\WebhookEndpoint::all();
    }

    public function get_webhook_events()
    {
        return hooks()->apply_filters('stripe_webhook_events', ['checkout.session.completed', 'invoice.payment_succeeded', 'invoice.payment_action_required', 'invoice.payment_failed', 'customer.subscription.created', 'customer.subscription.deleted', 'customer.subscription.updated']);
    }

    public function get_tax_rates()
    {
        return \Stripe\TaxRate::all();
    }

    public function retrieve_tax_rate($id)
    {
        return \Stripe\TaxRate::retrieve($id);
    }

    public function create_webhook()
    {
        $webhook = \Stripe\WebhookEndpoint::create([
            'url'            => $this->ci->stripe_gateway->webhookEndPoint,
            'enabled_events' => $this->get_webhook_events(),
        ]);

        update_option('stripe_webhook_id', $webhook->id);
        update_option('stripe_webhook_signing_secret', $webhook->secret);

        return $webhook;
    }

    public function create_session($data)
    {
        return \Stripe\Checkout\Session::create($data);
    }

    public function retrieve_session($data)
    {
        return \Stripe\Checkout\Session::retrieve($data);
    }

    public function retrieve_payment_intent($data)
    {
        return \Stripe\PaymentIntent::retrieve($data);
    }

    public function retrieve_payment_method($data)
    {
        return \Stripe\PaymentMethod::retrieve($data);
    }

    public function construct_event($payload, $secret)
    {
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        return \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $secret
          );
    }

    public function has_api_key()
    {
        return $this->secretKey != '';
    }
}
