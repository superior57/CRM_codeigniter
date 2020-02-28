<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_contact
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($contact_id)
    {
        define('GDPR_EXPORT', true);
        @ini_set('memory_limit', '256M');
        @ini_set('max_execution_time', 360);

        // $lead = $CI->leads_model->get($id);
        $this->ci->load->library('zip');

        $tmpDir     = get_temp_dir();
        $valAllowed = get_option('gdpr_contact_data_portability_allowed');
        if (empty($valAllowed)) {
            $valAllowed = [];
        } else {
            $valAllowed = unserialize($valAllowed);
        }

        $json = [];

        $contactFields = $this->ci->db->list_fields(db_prefix().'contacts');

        if ($passwordKey = array_search('password', $contactFields)) {
            unset($contactFields[$passwordKey]);
        }


        $this->ci->db->select(implode(',', $contactFields));
        $this->ci->db->where('id', $contact_id);
        $contact = $this->ci->db->get(db_prefix().'contacts')->row_array();
        $slug    = slug_it($contact['firstname'] . ' ' . $contact['lastname']);

        $isIndividual = is_empty_customer_company($contact['userid']);
        $json         = [];

        $this->ci->db->where('show_on_client_portal', 1)
        ->where('fieldto', 'contacts')
        ->order_by('field_order', 'asc');

        $contactsCustomFields = $this->ci->db->get(db_prefix().'customfields')->result_array();

        if (in_array('profile_data', $valAllowed)) {
            $contact['additional_fields'] = [];

            foreach ($contactsCustomFields as $field) {
                $contact['additional_fields'][] = [
                'name'  => $field['name'],
                'value' => get_custom_field_value($contact['id'], $field['id'], 'contacts'),
            ];
            }

            $json = $contact;
        }

        if (in_array('consent', $valAllowed)) {
            $this->ci->load->model('gdpr_model');
            $json['consent'] = $this->ci->gdpr_model->get_consents(['contact_id' => $contact['id']]);
        }

        if (in_array('customer_profile_data', $valAllowed)
        && $contact['is_primary'] == '1'
        && !$isIndividual) {
            $this->ci->db->where('userid', $contact['userid']);
            $customer = $this->ci->db->get(db_prefix().'clients')->row_array();

            $customer['country']          = get_country($customer['country']);
            $customer['billing_country']  = get_country($customer['billing_country']);
            $customer['shipping_country'] = get_country($customer['shipping_country']);

            $this->ci->db->where('show_on_client_portal', 1)
              ->where('fieldto', 'customers')
              ->order_by('field_order', 'asc');

            $custom_fields                 = $this->ci->db->get(db_prefix().'customfields')->result_array();
            $customer['additional_fields'] = [];

            $groups    = $this->ci->clients_model->get_customer_groups($customer['userid']);
            $groupsIds = [];
            foreach ($groups as $group) {
                $groupsIds[] = $group['groupid'];
            }

            $groupNames = [];
            if (count($groupsIds) > 0) {
                $this->ci->db->where('id IN (' . implode(', ', $groupsIds) . ')');
                $groups = $this->ci->db->get(db_prefix().'customers_groups')->result_array();
                foreach ($groups as $group) {
                    $groupNames[] = $group['name'];
                }
            }

            $customer['groups'] = $groupNames;

            foreach ($custom_fields as $field) {
                $customer['additional_fields'][] = [
                'name'  => $field['name'],
                'value' => get_custom_field_value($customer['userid'], $field['id'], 'customers'),
            ];
            }

            $json['company'] = $customer;
        }

        // Notes
        if (in_array('profile_notes', $valAllowed) && $contact['is_primary'] == '1') {
            $this->ci->db->where('rel_id', $contact['userid']);
            $this->ci->db->where('rel_type', 'customer');
            $json['notes'] = $this->ci->db->get(db_prefix().'notes')->result_array();
        }

        // Contacts
        if (in_array('contacts', $valAllowed) && $contact['is_primary'] == '1' && !$isIndividual) {
            $this->ci->db->where('id !=', $contact['id']);
            $this->ci->db->where('userid', $contact['userid']);
            $otherContacts = $this->ci->db->get(db_prefix().'contacts')->result_array();

            foreach ($otherContacts as $keyContact => $otherContact) {
                $otherContacts[$keyContact]['additional_fields'] = [];

                foreach ($contactsCustomFields as $field) {
                    $otherContacts[$keyContact]['additional_fields'][] = [
                    'name'  => $field['name'],
                    'value' => get_custom_field_value($otherContact['id'], $field['id'], 'contacts'),
                ];
                }
            }
        }

        // Invoices
        if (in_array('invoices', $valAllowed) && $contact['is_primary'] == '1') {
            $this->ci->load->library('gdpr/gdpr_invoices');

            $json['invoices'] = $this->ci->gdpr_invoices->export($contact['userid']);
        }

        // Credit Notes
        if (in_array('credit_notes', $valAllowed) && $contact['is_primary'] == '1') {
            $this->ci->load->library('gdpr/gdpr_credit_notes');

            $json['credit_notes'] = $this->ci->gdpr_credit_notes->export($contact['userid']);
        }

        // Estimates
        if (in_array('estimates', $valAllowed) && $contact['is_primary'] == '1') {
            $this->ci->load->library('gdpr/gdpr_estimates');

            $json['estimates'] = $this->ci->gdpr_estimates->export($contact['userid']);
        }

        // Proposals
        if (in_array('proposals', $valAllowed) && $contact['is_primary'] == '1') {
            $this->ci->load->library('gdpr/gdpr_proposals');

            $json['proposals'] = $this->ci->gdpr_proposals->export($contact['userid'], 'customer');
        }

        // Subscriptions
        if (in_array('subscriptions', $valAllowed) && $contact['is_primary'] == '1') {
            $this->ci->load->library('gdpr/gdpr_subscriptions');

            $json['subscriptions'] = $this->ci->gdpr_subscriptions->export($contact['userid']);
        }

        // Expenses
        if (in_array('expenses', $valAllowed) && $contact['is_primary'] == '1') {
            $this->ci->load->library('gdpr/gdpr_expenses');

            $json['expenses'] = $this->ci->gdpr_expenses->export($contact['userid']);
        }

        // Contracts
        if (in_array('contracts', $valAllowed) && $contact['is_primary'] == '1') {
            $this->ci->load->library('gdpr/gdpr_contracts');

            $json['contracts'] = $this->ci->gdpr_contracts->export($contact['userid']);
        }

        // Tickets
        if (in_array('tickets', $valAllowed)) {
            $this->ci->load->library('gdpr/gdpr_tickets');

            $json['tickets'] = $this->ci->gdpr_tickets->export($contact['id']);
        }

        // Projects
        if (in_array('projects', $valAllowed) && $contact['is_primary'] == '1') {
            $this->ci->load->library('gdpr/gdpr_projects');

            $json['projects'] = $this->ci->gdpr_projects->export($contact['userid'], $contact['id']);
        }

        $tmpDirContactData = $tmpDir . '/' . $contact['id'] . time() . '-contact';
        mkdir($tmpDirContactData, 0755);

        $fp = fopen($tmpDirContactData . '/data.json', 'w');
        fwrite($fp, json_encode($json, JSON_PRETTY_PRINT));
        fclose($fp);

        $this->ci->zip->read_file($tmpDirContactData . '/data.json');

        if (is_dir($tmpDirContactData)) {
            @delete_dir($tmpDirContactData);
        }

        $this->ci->zip->download($slug . '-data.zip');

        /*header('Content-type:application/json');
        echo json_encode($json, JSON_PRETTY_PRINT);
        die;*/
    }
}
