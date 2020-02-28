<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Stripe extends App_Controller
{
    protected $subscriptionMetaKey = 'pcrm-subscription-hash';

    /**
     * Create the application Stripe webhook endpoint
     *
     * @return mixed
     */
    public function create_webhook()
    {
        if (staff_can('edit', 'settings')) {
            $this->load->library('stripe_core');

            try {
                $this->stripe_core->create_webhook();
                set_alert('success', _l('webhook_created'));
            } catch (Exception $e) {
                $this->session->set_flashdata('stripe-webhook-failure', true);
                set_alert('warning', $e->getMessage());
            }

            redirect(admin_url('settings/?group=payment_gateways&tab=online_payments_stripe_tab'));
        }
    }

    /**
     * The application Stripe webhook endpoint
     *
     * @return mixed
     */
    public function webhook_endpoint()
    {
        $this->load->library('stripe_core');
        $this->load->library('stripe_subscriptions');
        $this->load->model('subscriptions_model');

        $payload = @file_get_contents('php://input');
        $event   = null;

        // Validate the webhook
        try {
            $event = $this->stripe_core->construct_event($payload, get_option('stripe_webhook_signing_secret'));
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
          http_response_code(400); // PHP 5.4 or greater
          exit();
        } catch (\Stripe\Error\SignatureVerification $e) {
            // Invalid signature
              http_response_code(400); // PHP 5.4 or greater
              exit();
        }

        // Handle the checkout.session.completed event
        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;

            // Regular invoice pay webhook
            if ($session->payment_intent) {
                $payment = $this->stripe_core->retrieve_payment_intent($session->payment_intent);

                if (isset($payment->metadata->InvoiceId)) {
                    $this->load->model('invoices_model');

                    $invoice = $this->invoices_model->get($payment->metadata->InvoiceId);

                    if ($invoice) {
                        $this->stripe_gateway->addPayment([
                              'amount'        => (strcasecmp($invoice->currency_name, 'JPY') == 0 ? $payment->amount : $payment->amount / 100),
                              'invoiceid'     => $invoice->id,
                              'transactionid' => $payment->id,
                        ]);

                        if (!$this->stripe_gateway->is_test()) {
                            $this->db->where('userid', $payment->metadata->ClientId);
                            $this->db->update('clients', ['stripe_id' => $payment->customer]);
                        }
                    }
                }
            }
        } elseif ($event->type == 'customer.subscription.created') {
            $this->customerSubscriptionCreatedEvent($event);
        } elseif ($event->type == 'invoice.payment_succeeded') {
            $this->invoicePaymentSucceededEvent($event);
        } elseif ($event->type == 'invoice.payment_failed') {
            $this->invoicePaymentFailedEevent($event);
        } elseif ($event->type == 'invoice.payment_action_required') {
            $this->invoicePaymentActionRequiredEevent($event);
        } elseif ($event->type == 'customer.subscription.deleted') {
            $this->customerSubscriptionDeletedEvent($event);
        } elseif ($event->type == 'customer.subscription.updated') {
            $this->customerSubscriptionUpdatedEvent($event);
        }
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
    public function success($invoice_id, $invoice_hash)
    {
        set_alert('success', _l('online_payment_recorded_success'));

        redirect(site_url('invoice/' . $invoice_id . '/' . $invoice_hash));
    }

    protected function customerSubscriptionCreatedEvent($event)
    {
        $subscription = $event->data->object;

        if (isset($subscription->metadata[$this->subscriptionMetaKey])) {
            $subscription = $this->stripe_subscriptions->get_subscription($subscription->id);

            $this->stripe_core->update_customer($subscription->customer, [
                    'invoice_settings' => [
                      'default_payment_method' => $subscription->default_payment_method,
                    ],
            ]);

            \Stripe\Subscription::update($subscription->id, ['default_payment_method' => '']);

            $dbSubscription = $this->subscriptions_model->get_by_hash($subscription->metadata[$this->subscriptionMetaKey]);
            $update         = ['in_test_environment' => $this->stripe_gateway->is_test() ? 1 : 0];

            if (!empty($dbSubscription->date)) {
                if ($dbSubscription->date <= date('Y-m-d')) {
                    // Updates the first billing date to be today because
                    // in the create_subscription method is now
                    $update['date'] = date('Y-m-d');
                }

                if ($dbSubscription->date > date('Y-m-d')) {
                    $update['status']                 = 'future';
                    $update['next_billing_cycle']     = strtotime($dbSubscription->date);
                    $update['stripe_subscription_id'] = $subscription->id;
                    $update['date_subscribed']        = date('Y-m-d H:i:s');
                }
            }

            $this->subscriptions_model->update($dbSubscription->id, $update);
        }
    }

    protected function invoicePaymentSucceededEvent($event)
    {
        $invoice = $event->data->object;
        if (isset($invoice->lines->data[0]->metadata[$this->subscriptionMetaKey])) {
            $dbSubscription = $this->subscriptions_model->get_by_hash($invoice->lines->data[0]->metadata[$this->subscriptionMetaKey]);

            if ($dbSubscription) {
                if (!$this->stripe_gateway->is_test()) {
                    $this->db->where('userid', $dbSubscription->clientid);
                    $this->db->update('clients', ['stripe_id' => $invoice->customer]);
                }

                $new_invoice_data = create_subscription_invoice_data($dbSubscription, $invoice);
                $this->subscriptions_model->update($dbSubscription->id, ['next_billing_cycle' => $invoice->lines->data[0]->period->end]);

                $this->load->model('invoices_model');

                if (!defined('STRIPE_SUBSCRIPTION_INVOICE')) {
                    define('STRIPE_SUBSCRIPTION_INVOICE', true);
                }

                $id = $this->invoices_model->add($new_invoice_data);

                if ($id) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'invoices', [
                            'addedfrom' => $dbSubscription->created_from,
                        ]);

                    $payment_data['paymentmode']   = 'stripe';
                    $payment_data['amount']        = $new_invoice_data['total'];
                    $payment_data['invoiceid']     = $id;
                    $payment_data['transactionid'] = $invoice->charge;

                    $this->load->model('payments_model');
                    $this->payments_model->add($payment_data, $dbSubscription->id);

                    $update = [
                            'status'                 => 'active',
                            'stripe_subscription_id' => $invoice->subscription,
                          ];

                    // In case updated previously in subscription.created event and the subscription
                    // was in future
                    if (empty($dbSubscription->date_subscribed)) {
                        $update['date_subscribed'] = date('Y-m-d H:i:s');
                    }

                    if (empty($dbSubscription->date)) {
                        $update['date'] = date('Y-m-d');
                    }

                    $this->subscriptions_model->update($dbSubscription->id, $update);

                    send_email_customer_subscribed_to_subscription_to_staff($dbSubscription);

                    hooks()->do_action('customer_subscribed_to_subscription', $dbSubscription);
                }
            }
        }
    }

    protected function invoicePaymentFailedEevent($event)
    {
        $invoice = $event->data->object;
        if (isset($invoice->lines->data[0]->metadata[$this->subscriptionMetaKey])) {
            $dbSubscription = $this->subscriptions_model->get_by_hash($invoice->lines->data[0]->metadata[$this->subscriptionMetaKey]);

            if ($dbSubscription) {
                $payment_intent = $this->stripe_core->retrieve_payment_intent($invoice->payment_intent);
                //log_message('error', json_encode($payment_intent, JSON_PRETTY_PRINT));
                // Will handle requires action in the event invoice.payment_action_required
                if ($payment_intent->status != 'requires_action') {
                    $this->subscriptions_model->send_email_template(
                            $dbSubscription->id,
                            $this->getStaffCCForMailTemplate($dbSubscription->created_from),
                            'subscription_payment_failed_to_customer'
                        );
                }
            }
        }
    }

    protected function invoicePaymentActionRequiredEevent($event)
    {
        $invoice = $event->data->object;
        if (isset($invoice->lines->data[0]->metadata[$this->subscriptionMetaKey])) {

            // Customer was on session while trying to subscribe to the invoice
            // In this case, in the Subscription.php class he will be redirected to the
            // invoice hosted url to confirm the payment
            // he already know that he need to confir the payment and no email is needed
            // perhaps Stripe will send one if configured
            if (isset($invoice->lines->data[0]->metadata['customer-on-session'])) {
                return;
            }

            $dbSubscription = $this->subscriptions_model->get_by_hash($invoice->lines->data[0]->metadata[$this->subscriptionMetaKey]);

            if ($dbSubscription) {
                $contact = $this->clients_model->get_contact(get_primary_contact_user_id($dbSubscription->clientid));

                if (!$contact) {
                    return false;
                }

                send_mail_template(
                'subscription_payment_requires_action',
                 $dbSubscription,
                 $contact,
                 $invoice->hosted_invoice_url,
                 $this->getStaffCCForMailTemplate($dbSubscription->created_from)
             );
            }
        }
    }

    protected function customerSubscriptionUpdatedEvent($event)
    {
        $subscription = $event->data->object;
        if (isset($subscription->metadata[$this->subscriptionMetaKey])) {
            $dbSubscription = $this->subscriptions_model->get_by_hash($subscription->metadata[$this->subscriptionMetaKey]);

            if ($dbSubscription) {
                $update = [
                        'status'             => $subscription->status,
                        'next_billing_cycle' => $subscription->current_period_end,
                        'quantity'           => $subscription->items->data[0]->quantity,
                        'ends_at'            => $subscription->cancel_at_period_end ? $subscription->current_period_end : null,
                    ];

                if ($dbSubscription->status == 'future') {
                    unset($update['status']);
                    unset($update['next_billing_cycle']);
                }

                $this->subscriptions_model->update($dbSubscription->id, $update);
            }
        }
    }

    protected function customerSubscriptionDeletedEvent($event)
    {
        $subscription = $event->data->object;
        if (isset($subscription->metadata[$this->subscriptionMetaKey])) {
            $dbSubscription = $this->subscriptions_model->get_by_hash($subscription->metadata[$this->subscriptionMetaKey]);

            if ($dbSubscription) {
                $this->subscriptions_model->send_email_template(
                        $dbSubscription->id,
                        $this->getStaffCCForMailTemplate($dbSubscription->created_from),
                        'subscription_cancelled_to_customer'
                    );

                $this->subscriptions_model->update(
                        $dbSubscription->id,
                        ['status' => $subscription->status, 'next_billing_cycle' => null]
                    );
            }
        }
    }

    protected function getStaffCCForMailTemplate($staff_id)
    {
        $this->db->select('email')
                ->from(db_prefix() . 'staff')
                ->where('staffid', $staff_id);
        $staff = $this->db->get()->row();

        $cc = '';
        if ($staff) {
            $cc = $staff->email;
        }

        return $cc;
    }
}
