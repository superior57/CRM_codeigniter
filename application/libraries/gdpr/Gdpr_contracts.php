<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_contracts
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($customer_id)
    {
        if (!class_exists('contracts_model')) {
            $this->ci->load->model('contracts_model');
        }

        $this->ci->db->where('client', $customer_id);
        $contracts = $this->ci->db->get(db_prefix().'contracts')->result_array();

        $this->ci->db->where('show_on_client_portal', 1);
        $this->ci->db->where('fieldto', 'contracts');
        $this->ci->db->order_by('field_order', 'asc');
        $custom_fields = $this->ci->db->get(db_prefix().'customfields')->result_array();

        $this->ci->load->model('currencies_model');
        foreach ($contracts as $contractsKey => $contract) {
            $contracts[$contractsKey]['comments']        = $this->ci->contracts_model->get_comments($contract['id']);
            $contracts[$contractsKey]['renewal_history'] = $this->ci->contracts_model->get_contract_renewal_history($contract['id']);
            $contracts[$contractsKey]['tracked_emails']  = get_tracked_emails($contract['id'], 'contract');

            $contracts[$contractsKey]['additional_fields'] = [];
            foreach ($custom_fields as $cf) {
                $contracts[$contractsKey]['additional_fields'][] = [
                    'name'  => $cf['name'],
                    'value' => get_custom_field_value($contract['id'], $cf['id'], 'contracts'),
                ];
            }
        }

        return $contracts;
    }
}
