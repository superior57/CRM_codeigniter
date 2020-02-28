<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_estimates
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($customer_id)
    {
        $valAllowed = get_option('gdpr_contact_data_portability_allowed');
        if (empty($valAllowed)) {
            $valAllowed = [];
        } else {
            $valAllowed = unserialize($valAllowed);
        }

        $this->ci->db->where('clientid', $customer_id);
        $estimates = $this->ci->db->get(db_prefix().'estimates')->result_array();

        $this->ci->db->where('show_on_client_portal', 1);
        $this->ci->db->where('fieldto', 'estimate');
        $this->ci->db->order_by('field_order', 'asc');
        $custom_fields = $this->ci->db->get(db_prefix().'customfields')->result_array();

        $this->ci->load->model('currencies_model');
        foreach ($estimates as $estimatesKey => $estimate) {
            unset($estimates[$estimatesKey]['adminnote']);
            $estimates[$estimatesKey]['shipping_country'] = get_country($estimate['shipping_country']);
            $estimates[$estimatesKey]['billing_country']  = get_country($estimate['billing_country']);

            $estimates[$estimatesKey]['currency'] = $this->ci->currencies_model->get($estimate['currency']);

            $estimates[$estimatesKey]['items'] = _prepare_items_array_for_export(get_items_by_type('estimate', $estimate['id']), 'estimate');

            if (in_array('estimates_notes', $valAllowed)) {
                // Notes
                $this->ci->db->where('rel_id', $estimate['id']);
                $this->ci->db->where('rel_type', 'estimate');

                $estimates[$estimatesKey]['notes'] = $this->ci->db->get(db_prefix().'notes')->result_array();
            }
            if (in_array('estimates_activity_log', $valAllowed)) {
                // Activity
                $this->ci->db->where('rel_id', $estimate['id']);
                $this->ci->db->where('rel_type', 'estimate');

                $estimates[$estimatesKey]['activity'] = $this->ci->db->get(db_prefix().'sales_activity')->result_array();
            }
            $estimates[$estimatesKey]['views'] = get_views_tracking('estimate', $estimate['id']);

            $estimates[$estimatesKey]['tracked_emails'] = get_tracked_emails($estimate['id'], 'estimate');

            $estimates[$estimatesKey]['additional_fields'] = [];

            foreach ($custom_fields as $cf) {
                $estimates[$estimatesKey]['additional_fields'][] = [
                    'name'  => $cf['name'],
                    'value' => get_custom_field_value($estimate['id'], $cf['id'], 'estimate'),
                ];
            }
        }

        return $estimates;
    }
}
