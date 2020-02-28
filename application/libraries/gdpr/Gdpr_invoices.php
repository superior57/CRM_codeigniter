<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_invoices
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($customer_id)
    {
        if (!class_exists('Invoices_model')) {
            $this->ci->load->model('invoices_model');
        }

        $valAllowed = get_option('gdpr_contact_data_portability_allowed');
        if (empty($valAllowed)) {
            $valAllowed = [];
        } else {
            $valAllowed = unserialize($valAllowed);
        }

        $this->ci->db->where('clientid', $customer_id);
        $invoices = $this->ci->db->get(db_prefix().'invoices')->result_array();

        $this->ci->db->where('show_on_client_portal', 1);
        $this->ci->db->where('fieldto', 'invoice');
        $this->ci->db->order_by('field_order', 'asc');
        $custom_fields = $this->ci->db->get(db_prefix().'customfields')->result_array();

        $this->ci->load->model('currencies_model');
        foreach ($invoices as $invoicesKey => $invoice) {
            unset($invoices[$invoicesKey]['adminnote']);
            $invoices[$invoicesKey]['shipping_country'] = get_country($invoice['shipping_country']);
            $invoices[$invoicesKey]['billing_country']  = get_country($invoice['billing_country']);

            $invoices[$invoicesKey]['currency'] = $this->ci->currencies_model->get($invoice['currency']);

            $invoices[$invoicesKey]['items'] = _prepare_items_array_for_export(get_items_by_type('invoice', $invoice['id']), 'invoice');

            // Payments
            $paymentFields = $this->ci->db->list_fields(db_prefix().'invoicepaymentrecords');
            if ($noteKey = array_search('note', $paymentFields)) {
                unset($paymentFields[$noteKey]);
            }

            $this->ci->db->select(implode(',', $paymentFields));
            $this->ci->db->where('invoiceid', $invoice['id']);
            $invoices[$invoicesKey]['payments'] = $this->ci->db->get(db_prefix().'invoicepaymentrecords')->result_array();

            if (in_array('invoices_notes', $valAllowed)) {
                // Notes
                $this->ci->db->where('rel_id', $invoice['id']);
                $this->ci->db->where('rel_type', 'invoice');

                $invoices[$invoicesKey]['notes'] = $this->ci->db->get(db_prefix().'notes')->result_array();
            }

            if (in_array('invoices_activity_log', $valAllowed)) {
                // Activity
                $this->ci->db->where('rel_id', $invoice['id']);
                $this->ci->db->where('rel_type', 'invoice');

                $invoices[$invoicesKey]['activity'] = $this->ci->db->get(db_prefix().'sales_activity')->result_array();
            }

            $invoices[$invoicesKey]['views']          = get_views_tracking('invoice', $invoice['id']);
            $invoices[$invoicesKey]['tracked_emails'] = get_tracked_emails($invoice['id'], 'invoice');

            $invoices[$invoicesKey]['additional_fields'] = [];
            foreach ($custom_fields as $cf) {
                $invoices[$invoicesKey]['additional_fields'][] = [
                    'name'  => $cf['name'],
                    'value' => get_custom_field_value($invoice['id'], $cf['id'], 'invoice'),
                ];
            }
        }

        return $invoices;
    }
}
