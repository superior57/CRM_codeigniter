<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Contract extends ClientsController
{
    public function index($id, $hash)
    {
        check_contract_restrictions($id, $hash);
        $contract = $this->contracts_model->get($id);

        if (!$contract) {
            show_404();
        }

        if (!is_client_logged_in()) {
            load_client_language($contract->client);
        }

        if ($this->input->post()) {
            $action = $this->input->post('action');

            switch ($action) {
            case 'contract_pdf':
                    $pdf = contract_pdf($contract);
                    $pdf->Output(slug_it($contract->subject . '-' . get_option('companyname')) . '.pdf', 'D');

                    break;
            case 'sign_contract':
                    process_digital_signature_image($this->input->post('signature', false), CONTRACTS_UPLOADS_FOLDER . $id);
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix().'contracts', array_merge(get_acceptance_info_array(), [
                        'signed' => 1,
                    ]));

                    // Notify contract creator that customer signed the contract
                    send_contract_signed_notification_to_staff($id);

                    set_alert('success', _l('document_signed_successfully'));
                    redirect($_SERVER['HTTP_REFERER']);

            break;
             case 'contract_comment':
                    // comment is blank
                    if (!$this->input->post('content')) {
                        redirect($this->uri->uri_string());
                    }
                    $data                = $this->input->post();
                    $data['contract_id'] = $id;
                    $this->contracts_model->add_comment($data, true);
                    redirect($this->uri->uri_string() . '?tab=discussion');

                    break;
            }
        }

        $this->disableNavigation();
        $this->disableSubMenu();

        $data['title']     = $contract->subject;
        $data['contract']  = hooks()->apply_filters('contract_html_pdf_data', $contract);
        $data['bodyclass'] = 'contract contract-view';

        $data['identity_confirmation_enabled'] = true;
        $data['bodyclass'] .= ' identity-confirmation';
        $this->app_scripts->theme('sticky-js','assets/plugins/sticky/sticky.js');
        $data['comments'] = $this->contracts_model->get_comments($id);
        //add_views_tracking('proposal', $id);
        hooks()->do_action('contract_html_viewed', $id);
        $this->app_css->remove('reset-css','customers-area-default');
        $data                      = hooks()->apply_filters('contract_customers_area_view_data', $data);
        $this->data($data);
        no_index_customers_area();
        $this->view('contracthtml');
        $this->layout();
    }
}
