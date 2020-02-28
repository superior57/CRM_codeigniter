<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Stripe_ideal extends App_Controller
{
    public function response($id, $hash)
    {
        $this->load->model('invoices_model');

        check_invoice_restrictions($id, $hash);

        $invoice = $this->invoices_model->get($id);
        load_client_language($invoice->clientid);

        try {
            $source_id = $this->input->get('source');
            $source    = $this->stripe_ideal_gateway->get_source($source_id);

            if ($source->status == 'chargeable') {
                try {
                    try {
                        $charge = $this->stripe_ideal_gateway->charge($source->id, $source->amount, $source->metadata->invoice_id);

                        if ($charge->status == 'succeeded') {
                            $charge->invoice_id = $source->metadata->invoice_id;
                            $success            = $this->stripe_ideal_gateway->finish_payment($charge);

                            set_alert('success', $success ? _l('online_payment_recorded_success') : _l('online_payment_recorded_success_fail_database'));
                        } elseif ($charge->status == 'pending') {
                            set_alert('success', _l('payment_received_awaiting_confirmation'));
                        } else {
                            // In the mean time the webhook probably got the source
                            $source = $this->stripe_ideal_gateway->get_source($source_id);

                            if ($source->status == 'consumed') {
                                set_alert('success', _l('online_payment_recorded_success'));
                            } else {
                                $errMsg = _l('invoice_payment_record_failed');
                                if ($charge->failure_message) {
                                    $errMsg .= ' - ' . $charge->failure_message;
                                }
                                set_alert('warning', $errMsg);
                            }
                        }
                    } catch (Exception $e) {
                        // In the mean time the webhook probably got the source
                        $source = $this->stripe_ideal_gateway->get_source($source_id);
                        if ($source->status == 'consumed') {
                            set_alert('success', _l('online_payment_recorded_success'));
                        } else {
                            set_alert('warning', $e->getMessage());
                        }
                    }
                } catch (Exception $e) {
                    set_alert('warning', $e->getMessage());
                }
            } else {
                set_alert('warning', _l('invoice_payment_record_failed'));
            }
        } catch (Exception $e) {
            set_alert('warning', $e->getMessage());
        }

        redirect(site_url('invoice/' . $id . '/' . $hash));
    }

    /**
     * Create the application Stripe webhook endpoint
     *
     * @return mixed
     */
    public function create_webhook()
    {
        if (staff_can('edit', 'settings')) {
            try {
                $this->stripe_ideal_gateway->create_webhook();
                set_alert('success', _l('webhook_created'));
            } catch (Exception $e) {
                $this->session->set_flashdata('stripe-ideal-webhook-failure', true);
                set_alert('warning', $e->getMessage());
            }

            redirect(admin_url('settings/?group=payment_gateways&tab=online_payments_stripe_ideal_tab'));
        }
    }

    public function webhook()
    {
        \Stripe\Stripe::setApiVersion($this->stripe_ideal_gateway->apiVersion);
        \Stripe\Stripe::setApiKey($this->stripe_ideal_gateway->decryptSetting('api_secret_key'));

        $payload    = @file_get_contents('php://input');
        $event      = null;
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        // Validate the webhook
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                get_option('stripe_ideal_webhook_signing_secret')
          );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
          http_response_code(400); // PHP 5.4 or greater
          exit();
        } catch (\Stripe\Error\SignatureVerification $e) {
            // Invalid signature
              http_response_code(400); // PHP 5.4 or greater
              exit();
        }

        if ($event->type == 'source.chargeable') {
            $source = $event->data->object;

            if (isset($source->metadata['pcrm-stripe-ideal'])
                    && $source->type == 'ideal'
                    && $source->status == 'chargeable') {
                $invoice_id = intval($source->metadata['invoice_id']);
                $charge     = $this->stripe_ideal_gateway->charge($source->id, $source->amount, $invoice_id);

                if ($charge->status == 'succeeded') {
                    $this->stripe_ideal_gateway->finish_payment($charge);
                }
            }
        }
    }
}
