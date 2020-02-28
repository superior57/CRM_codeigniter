<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_credit_notes
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($customer_id)
    {
        $this->ci->db->where('clientid', $customer_id);
        $credit_notes = $this->ci->db->get(db_prefix().'creditnotes')->result_array();

        $this->ci->db->where('show_on_client_portal', 1);
        $this->ci->db->where('fieldto', 'credit_note');
        $this->ci->db->order_by('field_order', 'asc');
        $custom_fields = $this->ci->db->get(db_prefix().'customfields')->result_array();

        $this->ci->load->model('currencies_model');
        foreach ($credit_notes as $creditNoteKey => $credit_note) {
            unset($credit_notes[$creditNoteKey]['adminnote']);

            $credit_notes[$creditNoteKey]['shipping_country'] = get_country($credit_note['shipping_country']);
            $credit_notes[$creditNoteKey]['billing_country']  = get_country($credit_note['billing_country']);

            $credit_notes[$creditNoteKey]['currency'] = $this->ci->currencies_model->get($credit_note['currency']);

            $credit_notes[$creditNoteKey]['items'] = _prepare_items_array_for_export(get_items_by_type('credit_note', $credit_note['id']), 'credit_note');

            // Credits
            $this->ci->db->where('credit_id', $credit_note['id']);

            $credit_notes[$creditNoteKey]['credits'] = $this->ci->db->get(db_prefix().'credits')->result_array();

            $credit_notes[$creditNoteKey]['tracked_emails'] = get_tracked_emails($credit_note['id'], 'credit_note');

            $credit_notes[$creditNoteKey]['additional_fields'] = [];
            foreach ($custom_fields as $cf) {
                $credit_notes[$creditNoteKey]['additional_fields'][] = [
                    'name'  => $cf['name'],
                    'value' => get_custom_field_value($credit_note['id'], $cf['id'], 'credit_note'),
                ];
            }
        }

        return $credit_notes;
    }
}
