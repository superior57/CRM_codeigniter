<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Consent extends ClientsController
{
    public function index()
    {
        show_404();
    }

    public function contact($key)
    {
        if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '0' || !is_gdpr()) {
            show_error('This page is currently disabled, check back later.');
        }

        $this->db->where('meta_value', $key);
        $this->db->where('meta_key', 'consent_key');
        $meta = $this->db->get(db_prefix() . 'user_meta')->row();

        if (!$meta) {
            show_404();
        }

        $contact = $this->clients_model->get_contact($meta->contact_id);

        if (!$contact) {
            show_404();
        }

        $this->load->model('gdpr_model');

        if ($this->input->post()) {
            foreach ($this->input->post('action') as $purpose_id => $action) {
                $purpose = $this->gdpr_model->get_consent_purpose($purpose_id);
                if ($purpose) {
                    $this->gdpr_model->add_consent([
                        'action'                     => $action,
                        'purpose_id'                 => $purpose_id,
                        'contact_id'                 => $contact->id,
                        'description'                => 'Consent Updated From Web Form',
                        'opt_in_purpose_description' => $purpose->description,
                    ]);
                }
            }
            redirect($_SERVER['HTTP_REFERER']);
        }

        $data['contact']  = $contact;
        $data['purposes'] = $this->gdpr_model->get_consent_purposes($contact->id, 'contact');
        $data['title']    = _l('gdpr') . ' - ' . $contact->firstname . ' ' . $contact->lastname;

        $data['bodyclass'] = 'consent';
        $this->data($data);
        $this->view('consent');
        no_index_customers_area();

        $this->disableNavigation();
        $this->disableSubMenu();
        $this->layout();
    }

    public function l($hash)
    {
        if (is_gdpr() && get_option('gdpr_enable_consent_for_leads') == '0' || !is_gdpr()) {
            show_error('This page is currently disabled, check back later.');
        }

        $this->db->where('hash', $hash);
        $lead = $this->db->get(db_prefix() . 'leads')->row();

        if (!$lead) {
            show_404();
        }

        $this->load->model('gdpr_model');

        if ($this->input->post()) {
            foreach ($this->input->post('action') as $purpose_id => $action) {
                $purpose = $this->gdpr_model->get_consent_purpose($purpose_id);
                if ($purpose) {
                    $this->gdpr_model->add_consent([
                        'action'                     => $action,
                        'purpose_id'                 => $purpose_id,
                        'lead_id'                    => $lead->id,
                        'description'                => 'Consent Updated From Web Form',
                        'opt_in_purpose_description' => $purpose->description,
                    ]);
                }
            }
            redirect($_SERVER['HTTP_REFERER']);
        }

        $data['lead']     = $lead;
        $data['purposes'] = $this->gdpr_model->get_consent_purposes($lead->id, 'lead');
        $data['title']    = _l('gdpr') . ' - ' . $lead->name;

        $data['bodyclass'] = 'consent';
        $this->data($data);
        $this->view('consent');
        no_index_customers_area();

        $this->disableNavigation();
        $this->disableSubMenu();
        $this->layout();
    }
}
