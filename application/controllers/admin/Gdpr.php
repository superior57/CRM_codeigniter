<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $notAdminAllowed = ['lead_consent_opt_action', 'contact_consent_opt_action'];
        if (!is_admin() && !in_array($this->uri->segment(3), $notAdminAllowed)) {
            access_denied('GDPR');
        }
        $this->load->model('gdpr_model');
    }

    public function index()
    {
        $data['page'] = $this->input->get('page') ? $this->input->get('page') : 'general';
        $data['save'] = true;
        if ($data['page'] == 'forgotten') {
            $data['requests'] = $this->gdpr_model->get_removal_requests();
            $data['not_pending_requests'] = total_rows(db_prefix().'gdpr_requests', array('status '=>'pending'));
        } elseif ($data['page'] == 'consent') {
            $data['consent_purposes'] = $this->gdpr_model->get_consent_purposes();
        }
        $data['title'] = _l('gdpr');
        $this->load->view('admin/gdpr/index', $data);
    }

    public function save()
    {
        $page = $this->input->get('page') ? $this->input->get('page') : 'general';
        $data = $this->input->post('settings');

        //XSS filtered from tinymce
        $noXSS = ['terms_and_conditions', 'privacy_policy', 'gdpr_consent_public_page_top_block', 'gdpr_page_top_information_block'];

        if($page == 'portability') {
            $data['gdpr_lead_data_portability_allowed'] = isset($data['gdpr_lead_data_portability_allowed']) ? $data['gdpr_lead_data_portability_allowed'] : array();
            $data['gdpr_lead_data_portability_allowed'] = serialize($data['gdpr_lead_data_portability_allowed']);

            $data['gdpr_contact_data_portability_allowed'] = isset($data['gdpr_contact_data_portability_allowed']) ? $data['gdpr_contact_data_portability_allowed'] : array();
            $data['gdpr_contact_data_portability_allowed'] = serialize($data['gdpr_contact_data_portability_allowed']);
        }

        foreach ($data as $name => $val) {
            if (in_array($name, $noXSS)) {
                $val = $this->input->post('settings', false)[$name];
            }
            update_option($name, $val);
        }

        redirect(admin_url('gdpr/index?page=' . $page));
    }

    public function change_removal_request_status($id, $status)
    {
        $this->gdpr_model->update($id, ['status' => $status]);
    }

    public function consent_purpose($id = false)
    {
        if ($this->input->post()) {
            $data = $this->input->post();

            $data['description'] = nl2br($data['description']);

            if (!$id) {
                $this->gdpr_model->add_consent_purpose(['name' => $data['name'], 'description' => $data['description']]);
            } else {
                $update = ['description' => $data['description']];
                if (isset($data['name'])) {
                    $update['name'] = $data['name'];
                }
                $this->gdpr_model->update_consent_purpose($id, $update);
            }
            redirect(admin_url('gdpr/index?page=consent'));
        }

        $data = [];
        if (!empty($id)) {
            $data['purpose'] = $this->gdpr_model->get_consent_purpose($id);
        }
        $this->load->view('admin/gdpr/pages/includes/consent', $data);
    }

    public function delete_consent_purpose($id)
    {
        $this->gdpr_model->delete_consent_purpose($id);
        redirect(admin_url('gdpr/index?page=consent'));
    }

    public function enable()
    {
        update_option('enable_gdpr', 1);
        redirect(admin_url('gdpr'));
    }

    public function contact_consent_opt_action()
    {
        if ($this->input->post()) {
            $data       = $this->input->post();
            $contact_id = $data['contact_id'];
            $client_id  = get_user_id_by_contact_id($contact_id);

            if (!has_permission('customers', '', 'view')) {
                if (!is_customer_admin($client_id)) {
                    access_denied('Contact Consents Action');
                }
            }

            $data               = $this->prepare_consent_opt_action_data($data);
            $data['contact_id'] = $contact_id;
            $this->gdpr_model->add_consent($data);

            if (strpos($_SERVER['HTTP_REFERER'], 'all_contacts') !== false) {
                redirect(admin_url('clients/all_contacts?&consents=' . $contact_id));
            } else {
                redirect(admin_url('clients/client/' . $client_id . '?group=contacts&consents=' . $contact_id));
            }
        }
    }

    public function lead_consent_opt_action()
    {
        if ($this->input->post()) {
            $data    = $this->input->post();
            $lead_id = $data['lead_id'];

            $this->load->model('leads_model');
            if (!is_staff_member() || !$this->leads_model->staff_can_access_lead($lead_id)) {
                ajax_access_denied();
            }

            $data            = $this->prepare_consent_opt_action_data($data);
            $data['lead_id'] = $lead_id;
            $this->gdpr_model->add_consent($data);
            echo json_encode(['lead_id' => $lead_id]);
        }
    }

    private function prepare_consent_opt_action_data($data)
    {
        return [
            'action'                     => $data['action'],
            'purpose_id'                 => $data['purpose_id'],
            'description'                => nl2br($data['description']),
            'opt_in_purpose_description' => isset($data['opt_in_purpose_description']) ? nl2br($data['opt_in_purpose_description']) : '',
            'staff_name'                 => get_staff_full_name(),
        ];
    }
}
