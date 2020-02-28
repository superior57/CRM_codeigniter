<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_lead
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($id)
    {
        define('GDPR_EXPORT', true);
        @ini_set('memory_limit', '256M');
        @ini_set('max_execution_time', 360);


        // $lead = $this->ci->leads_model->get($id);
        $this->ci->load->library('zip');

        $tmpDir     = get_temp_dir();
        $valAllowed = get_option('gdpr_lead_data_portability_allowed');
        if (empty($valAllowed)) {
            $valAllowed = [];
        } else {
            $valAllowed = unserialize($valAllowed);
        }

        $json = [];


        $this->ci->db->where('id', $id);
        $lead = $this->ci->db->get(db_prefix().'leads')->row_array();
        $slug = slug_it($lead['name']);

        if (in_array('profile_data', $valAllowed) || in_array('custom_fields', $valAllowed)) {
            if (in_array('profile_data', $valAllowed)) {
                $json = $lead;

                $json['country'] = get_country($lead['country']);
                $json['status']  = $this->ci->leads_model->get_status($lead['status']);
                $json['source']  = $this->ci->leads_model->get_source($lead['source']);
            }

            if (in_array('custom_fields', $valAllowed)) {
                $custom_fields = get_custom_fields('leads');

                $this->ci->db->where('show_on_client_portal', 1)
              ->where('fieldto', 'leads')
              ->order_by('field_order', 'asc');

                $custom_fields = $this->ci->db->get(db_prefix().'customfields')->result_array();

                $json['additional_fields'] = [];

                foreach ($custom_fields as $field) {
                    $json['additional_fields'][] = ['name' => $field['name'], 'value' => get_custom_field_value($lead['id'], $field['id'], 'leads')];
                }
            }
        }

        // consent
        if (in_array('consent', $valAllowed)) {
            $this->ci->load->model('gdpr_model');
            $json['consent'] = $this->ci->gdpr_model->get_consents(['lead_id' => $lead['id']]);
        }

        // Notes
        if (in_array('notes', $valAllowed)) {
            $this->ci->db->where('rel_id', $lead['id']);
            $this->ci->db->where('rel_type', 'lead');
            $json['notes'] = $this->ci->db->get(db_prefix().'notes')->result_array();
        }

        if (in_array('activity_log', $valAllowed)) {
            $json['activity'] = $this->ci->leads_model->get_lead_activity_log($lead['id']);
        }

        if (in_array('integration_emails', $valAllowed)) {
            $this->ci->db->where('leadid', $lead['id']);
            $data['emails'] = $this->ci->db->get(db_prefix().'lead_integration_emails')->result_array();
        }

        if (in_array('proposals', $valAllowed)) {
            $this->ci->load->library('gdpr/gdpr_proposals');
            $json['proposals'] = $this->ci->gdpr_proposals->export($lead['id'], 'lead');
        }

        $tmpDirLeadData = $tmpDir . '/' . $lead['id'] . time() . '-lead';
        mkdir($tmpDirLeadData, 0755);


        $fp = fopen($tmpDirLeadData . '/data.json', 'w');
        fwrite($fp, json_encode($json, JSON_PRETTY_PRINT));
        fclose($fp);

        $this->ci->zip->read_file($tmpDirLeadData . '/data.json');

        if (is_dir($tmpDirLeadData)) {
            @delete_dir($tmpDirLeadData);
        }

        $this->ci->zip->download($slug . '-data.zip');
    }
}
