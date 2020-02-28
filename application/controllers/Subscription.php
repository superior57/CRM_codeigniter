<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Subscription extends ClientsController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('subscriptions_model');
        $this->load->library('stripe_subscriptions');
        $this->load->library('stripe_core');
    }

    public function index($hash)
    {
        $subscription = $this->subscriptions_model->get_by_hash($hash);

        if (!$subscription) {
            show_404();
        }

        $language               = load_client_language($subscription->clientid);
        $data['locale']         = get_locale_key($language);
        $data['publishableKey'] = $this->stripe_subscriptions->get_publishable_key();
        $plan                   = $this->stripe_subscriptions->get_plan($subscription->stripe_plan_id);

        check_stripe_subscription_environment($subscription);

        if (!empty($subscription->stripe_subscription_id) && !empty($data['publishableKey'])) {
            $data['stripeSubscription'] = $this->stripe_subscriptions->get_subscription([
                'id'     => $subscription->stripe_subscription_id,
                'expand' => ['latest_invoice'],
            ]);

            if ($this->input->get('complete')) {
                redirect($data['stripeSubscription']->latest_invoice->hosted_invoice_url);
            }
        }

        $upcomingInvoice           = new stdClass();
        $upcomingInvoice->total    = $plan->amount * $subscription->quantity;
        $upcomingInvoice->subtotal = $upcomingInvoice->total;

        if (!empty($subscription->tax_percent)) {
            $totalTax = $upcomingInvoice->total * ($subscription->tax_percent / 100);
            $upcomingInvoice->total += $totalTax;
        }

        $data['total']                = $upcomingInvoice->total;
        $upcomingInvoice->tax_percent = $subscription->tax_percent;
        $product                      = $this->stripe_subscriptions->get_product($plan->product);

        $upcomingInvoice->lines       = new stdClass();
        $upcomingInvoice->lines->data = [];

        $upcomingInvoice->lines->data[] = [
            'description' => $product->name . ' (' . app_format_money(strcasecmp($plan->currency, 'JPY') == 0 ? $plan->amount : $plan->amount / 100, strtoupper($subscription->currency_name)) . ' / ' . $plan->interval . ')',
            'amount'      => $plan->amount * $subscription->quantity,
            'quantity'    => $subscription->quantity,
        ];



        $this->disableNavigation();
        $this->disableSubMenu();
        $data['child_invoices'] = $this->subscriptions_model->get_child_invoices($subscription->id);
        $data['invoice']        = subscription_invoice_preview_data($subscription, $upcomingInvoice);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $data['plan']         = $plan;
        $data['subscription'] = $subscription;
        $data['title']        = $subscription->name;
        $data['hash']         = $hash;
        $data['bodyclass']    = 'subscriptionhtml';
        $this->data($data);
        $this->view('subscriptionhtml');
        $this->layout();
    }

    public function subscribe($subscription_hash)
    {
        $subscription = $this->subscriptions_model->get_by_hash($subscription_hash);

        if (!$subscription) {
            show_404();
        }

        $stripe_customer_id                    = $subscription->stripe_customer_id;
        $cancelUrl                             = site_url('subscription/' . $subscription_hash);
        $customerExistsButWithoutPaymentMethod = false;
        if (!empty($stripe_customer_id)) {
            // Check if the stripe customer actually have default payment method
            // Perhaps the stripe_id is saved via regular invoice payments where
            // the payment method is not stored

            $customer = $this->stripe_core->get_customer($stripe_customer_id);

            if (!empty($customer->invoice_settings->default_payment_method)) {
                $this->create_subscription($subscription, $stripe_customer_id);
            } else {
                $customerExistsButWithoutPaymentMethod = true;
            }
        }

        $sessionData = [
            'payment_method_types' => ['card'],
            'mode'                 => 'setup',
            'success_url'          => site_url('subscription/complete_setup/' . $subscription->hash . '?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url'           => $cancelUrl,
        ];

        if ($customerExistsButWithoutPaymentMethod) {
            $sessionData['client_reference_id'] = $stripe_customer_id;
        }

        if (is_client_logged_in()) {
            $contact = $this->clients_model->get_contact(get_contact_user_id());
            if ($contact->email) {
                $sessionData['customer_email'] = $contact->email;
            }
        }

        try {
            $session = $this->stripe_core->create_session($sessionData);
        } catch (Exception $e) {
            set_alert('warning', $e->getMessage());

            redirect($cancelUrl);
        }

        redirect_to_stripe_checkout($session->id);
    }

    /**
     * After collection payments for future subscriptions
     *
     * @return mixed
     */
    public function complete_setup($hash)
    {
        $subscription = $this->subscriptions_model->get_by_hash($hash);

        if (!$subscription) {
            show_404();
        }

        try {
            $session = $this->stripe_core->retrieve_session([
                'id'     => $this->input->get('session_id'),
                'expand' => ['setup_intent.payment_method'],
            ]);

            $payment_method = $session->setup_intent->payment_method;

            $customerPayload = [
                    'email'       => $payment_method->billing_details->email,
                    'name'        => $payment_method->billing_details->name,
                    'description' => $subscription->company,
            ];

            if ($session->client_reference_id) {
                $customer = $this->stripe_core->get_customer($session->client_reference_id);
                // Update the existing customer with the new provided email and name
                // this can happen if customer previously paid only invoice and it was saved in database
                // but without payment method, now becase above client_reference_id is passed
                // so we can determine here the customer
                $customer = $this->stripe_core->update_customer($session->client_reference_id, $customerPayload);
            } else {
                $customer = $this->stripe_subscriptions->create_customer($customerPayload);
            }

            $payment_method->attach(['customer' => $customer->id]);

            $this->stripe_core->update_customer($customer->id, [
                'invoice_settings' => [
                    'default_payment_method' => $payment_method->id,
                  ],
              ]);

            $this->create_subscription($subscription, $customer->id);
        } catch (Exception $e) {
            set_alert('warning', $e->getMessage());
        }

        redirect(site_url('subscription/' . $hash));
    }

    protected function create_subscription($subscription, $customer_id)
    {
        try {
            $params = [];

            if (!empty($subscription->stripe_tax_id)) {
                $params['default_tax_rates'] = [
                    $subscription->stripe_tax_id,
                ];
            }

            $params['metadata'] = [
                'pcrm-subscription-hash' => $subscription->hash,
                // Indicated the the customer was on session,
                // see requires action event
                'customer-on-session' => true,
            ];

            $params['items'] = [
                [
                    'plan' => $subscription->stripe_plan_id,
                ],
            ];

            $params['expand'] = ['latest_invoice.payment_intent'];

            if (!empty($subscription->date)) {
                if ($subscription->date > date('Y-m-d')) {
                    // is future
                    $params['billing_cycle_anchor'] = strtotime($subscription->date);
                    // https://stripe.com/docs/billing/subscriptions/billing-cycle#new-subscriptions
                    $params['prorate'] = false;
                }
            }

            $params['off_session'] = true;

            if ($subscription->quantity > 1) {
                $params['items'][0]['quantity'] = $subscription->quantity;
            }

            $stripeSubscription = $this->stripe_subscriptions->subscribe($customer_id, $params);

            // https://stripe.com/docs/billing/subscriptions/payment#signup-3b
            if ($stripeSubscription->status === 'incomplete') {
                if ($stripeSubscription->latest_invoice->payment_intent->status === 'requires_action') {
                    $this->subscriptions_model->update($subscription->id, [
                        'status'                 => $stripeSubscription->status,
                        'stripe_subscription_id' => $stripeSubscription->id,
                    ]);

                    redirect($stripeSubscription->latest_invoice->hosted_invoice_url);
                }
            } elseif ($stripeSubscription->status === 'active') {
                // In case the webhook is slower, update the stripe_subscription_id so the user won't see
                // the subscribe button again
                $this->subscriptions_model->update($subscription->id, ['stripe_subscription_id' => $stripeSubscription->id]);
                set_alert('success', _l('customer_successfully_subscribed_to_subscription', $subscription->name));
            }
        } catch (Exception $e) {
            set_alert('warning', $e->getMessage());
        }

        redirect(site_url('subscription/' . $subscription->hash));
    }

    /**
     * After stripe checkout succcess
     * Used only to display success message to the customer
     *
     * @param  string $invoice_id   The invoice id the payment is made to
     * @param  strgin $invoice_hash invoice hash
     *
     * @return mixed
     */
    public function success($hash)
    {
        $subscription = $this->subscriptions_model->get_by_hash($hash);

        set_alert('success', _l('customer_successfully_subscribed_to_subscription', $subscription->name));

        send_email_customer_subscribed_to_subscription_to_staff($subscription);

        redirect(site_url('subscription/' . $hash));
    }
}
