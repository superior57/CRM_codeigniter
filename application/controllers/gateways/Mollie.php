<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mollie extends App_Controller
{
    public function verify_payment()
    {
        $invoiceid = $this->input->get('invoiceid');
        $hash      = $this->input->get('hash');
        check_invoice_restrictions($invoiceid, $hash);

        $this->db->where('id', $invoiceid);
        $invoice = $this->db->get(db_prefix().'invoices')->row();

        $oResponse = $this->mollie_gateway->fetch_payment([
            'transaction_id' => $invoice->token,
        ]);
        if ($oResponse->isSuccessful()) {
            $data = $oResponse->getData();
            if ($data['status'] == 'paid') {
                set_alert('success', _l('online_payment_recorded_success'));
            }
        } else {
            set_alert('danger', $oResponse->getMessage());
        }
        redirect(site_url('invoice/' . $invoice->id . '/' . $invoice->hash));
    }

    public function webhook($key = null)
    {
        $ip = $this->input->ip_address();

        // Backward compatibility
        if (!$key) {
            if (!ip_in_range($ip, '87.233.229.26-87.233.229.27')) {
                return false;
            }
        }

        $trans_id  = $this->input->post('id');
        $oResponse = $this->mollie_gateway->fetch_payment([
                'transaction_id' => $trans_id,
        ]);

        if ($oResponse->isSuccessful()) {
            $data = $oResponse->getData();

            // When key is not passed is checked at the top with the ip range
            if (!$key || $data['metadata']['webhookKey'] == $key) {
                if ($data['status'] == 'paid') {
                    $this->mollie_gateway->addPayment(
                    [
                      'amount'        => $data['amount'],
                      'invoiceid'     => $data['metadata']['order_id'],
                      'paymentmethod' => $data['method'],
                      'transactionid' => $trans_id,
                    ]
                );
                } elseif ($data['status'] == 'refunded'
                    || $data['status'] == 'cancelled'
                    || $data['status'] == 'charged_back') {
                    if ($data['status'] == 'refunded') {
                        $this->db->where('transactionid', $trans_id);
                        $this->db->where('invoiceid', $data['metadata']['order_id']);
                        $payment = $this->db->get(db_prefix().'invoicepaymentrecords')->row();

                        if ($data['amountRemaining'] == 0) {
                            $this->db->where('id', $payment->id);
                            $this->db->delete(db_prefix().'invoicepaymentrecords');
                        } else {
                            $this->db->where('id', $payment->id);
                            $this->db->update(db_prefix().'invoicepaymentrecords', ['amount' => $data['amountRemaining']]);
                        }
                    } else {
                        $this->db->where('invoiceid', $data['metadata']['order_id']);
                        $this->db->where('transactionid', $trans_id);
                        $this->db->delete(db_prefix().'invoicepaymentrecords');
                    }

                    update_invoice_status($data['metadata']['order_id']);
                }
            }
        }
    }
}
