<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Invoice_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
                [
                    'name'      => 'Invoice Link',
                    'key'       => '{invoice_link}',
                    'available' => [
                        'invoice',
                    ],
                    'templates' => [
                        'subscription-payment-succeeded',
                    ],
                ],
                [
                    'name'      => 'Invoice Number',
                    'key'       => '{invoice_number}',
                    'available' => [
                        'invoice',
                    ],
                    'templates' => [
                        'subscription-payment-succeeded',
                    ],
                ],
                [
                    'name'      => 'Invoice Duedate',
                    'key'       => '{invoice_duedate}',
                    'available' => [
                        'invoice',
                    ],
                ],
                [
                    'name'      => 'Invoice Date',
                    'key'       => '{invoice_date}',
                    'available' => [
                        'invoice',
                    ],
                    'templates' => [
                        'subscription-payment-succeeded',
                    ],

                ],
                [
                    'name'      => 'Invoice Status',
                    'key'       => '{invoice_status}',
                    'available' => [
                        'invoice',
                    ],
                    'templates' => [
                        'subscription-payment-succeeded',
                    ],
                ],
                [
                    'name'      => 'Invoice Sale Agent',
                    'key'       => '{invoice_sale_agent}',
                    'available' => [
                        'invoice',
                    ],
                ],
                [
                    'name'      => 'Invoice Total',
                    'key'       => '{invoice_total}',
                    'available' => [
                        'invoice',
                    ],
                    'templates' => [
                        'subscription-payment-succeeded',
                    ],
                ],
                [
                    'name'      => 'Invoice Subtotal',
                    'key'       => '{invoice_subtotal}',
                    'available' => [
                        'invoice',
                    ],
                    'templates' => [
                        'subscription-payment-succeeded',
                    ],
                ],
                [
                    'name'      => 'Invoice Amount Due',
                    'key'       => '{invoice_amount_due}',
                    'available' => [
                        'invoice',
                    ],
                ],
                [
                    'name'      => 'Payment Recorded Total',
                    'key'       => '{payment_total}',
                    'available' => [

                    ],
                    'templates' => [
                        'subscription-payment-succeeded',
                        'invoice-payment-recorded-to-staff',
                        'invoice-payment-recorded',
                    ],
                ],
                [
                    'name'      => 'Payment Recorded Date',
                    'key'       => '{payment_date}',
                    'available' => [

                    ],
                    'templates' => [
                        'subscription-payment-succeeded',
                        'invoice-payment-recorded-to-staff',
                        'invoice-payment-recorded',
                    ],
                ],
            ];
    }

    /**
     * Merge fields for invoices
     * @param  mixed $invoice_id invoice id
     * @param  mixed $payment_id payment id
     * @return array
     */
    public function format($invoice_id, $payment_id = false)
    {
        $fields = [];
        $this->ci->db->where('id', $invoice_id);
        $invoice = $this->ci->db->get(db_prefix().'invoices')->row();

        if (!$invoice) {
            return $fields;
        }

        $currency = get_currency($invoice->currency);

        $fields['{payment_total}'] = '';
        $fields['{payment_date}']  = '';

        if ($payment_id) {
            $this->ci->db->where('id', $payment_id);
            $payment = $this->ci->db->get(db_prefix().'invoicepaymentrecords')->row();

            $fields['{payment_total}'] = app_format_money($payment->amount, $currency);
            $fields['{payment_date}']  = _d($payment->date);
        }

        $fields['{invoice_amount_due}'] = app_format_money(get_invoice_total_left_to_pay($invoice_id, $invoice->total), $currency);
        $fields['{invoice_sale_agent}'] = get_staff_full_name($invoice->sale_agent);
        $fields['{invoice_total}']      = app_format_money($invoice->total, $currency);
        $fields['{invoice_subtotal}']   = app_format_money($invoice->subtotal, $currency);

        $fields['{invoice_link}']    = site_url('invoice/' . $invoice_id . '/' . $invoice->hash);
        $fields['{invoice_number}']  = format_invoice_number($invoice_id);
        $fields['{invoice_duedate}'] = _d($invoice->duedate);
        $fields['{invoice_date}']    = _d($invoice->date);
        $fields['{invoice_status}']  = format_invoice_status($invoice->status, '', false);

        $custom_fields = get_custom_fields('invoice');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($invoice_id, $field['id'], 'invoice');
        }

        return hooks()->apply_filters('invoice_merge_fields', $fields, [
        'id'      => $invoice_id,
        'invoice' => $invoice,
     ]);
    }
}
