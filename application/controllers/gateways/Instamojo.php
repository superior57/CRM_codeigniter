<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Instamojo extends App_Controller
{
    public function redirect($invoice_id, $invoice_hash)
    {
        check_invoice_restrictions($invoice_id, $invoice_hash);

        $payment_id         = $this->input->get('payment_id');
        $payment_request_id = $this->input->get('payment_request_id');

        if (!$payment_id) {
            set_alert('warning', 'Payment ID Not Returned via Response');
            redirect(site_url('invoice/' . $invoice_id . '/' . $invoice_hash));
        }

        try {
            $api      = $this->instamojo_gateway->createApi();
            $response = $api->paymentRequestPaymentStatus($payment_request_id, $payment_id);

            if ($response['payment']['status'] == 'Credit') {
                $success = $this->instamojo_gateway->addPayment(
                  [
                        'amount'        => $response['amount'],
                        'invoiceid'     => $invoice_id,
                        'paymentmethod' => $response['payment']['instrument_type'],
                        'transactionid' => $response['payment']['payment_id'],
                  ]
                );

                set_alert($success
                  ? 'success' : 'danger', _l($success ? 'online_payment_recorded_success'
                    : 'online_payment_recorded_success_fail_database'));
            } else {
                // handle failed payment
                // https://docs.instamojo.com/docs/get-payment-details#response-fields
                //var_dump($response);
                set_alert('danger', _l('invoice_payment_record_failed'));
            }
        } catch (Exception $e) {
            $errors = json_decode($e->getMessage());

            foreach ($errors as $err) {
                set_alert('warning', $err[0]);

                break;
            }
        }

        redirect(site_url('invoice/' . $invoice_id . '/' . $invoice_hash));
    }
}
