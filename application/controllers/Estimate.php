<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Estimate extends ClientsController
{
    public function index($id, $hash)
    {
        check_estimate_restrictions($id, $hash);
        $estimate = $this->estimates_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($estimate->clientid);
        }

        $identity_confirmation_enabled = get_option('estimate_accept_identity_confirmation');

        if ($this->input->post('estimate_action')) {
            $action = $this->input->post('estimate_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->estimates_model->mark_action_status($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;
                if (is_array($success) && $success['invoiced'] == true) {
                    $accepted = true;
                    $invoice  = $this->invoices_model->get($success['invoiceid']);
                    set_alert('success', _l('clients_estimate_invoiced_successfully'));
                    $redURL = site_url('invoice/' . $invoice->id . '/' . $invoice->hash);
                } elseif (is_array($success) && $success['invoiced'] == false || $success === true) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_estimate_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_estimate_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_estimate_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), ESTIMATE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'estimates', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Estimate PDF generator
        if ($this->input->post('estimatepdf')) {
            try {
                $pdf = estimate_pdf($estimate);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            $estimate_number = format_estimate_number($estimate->id);
            $companyname     = get_option('invoice_company_name');
            if ($companyname != '') {
                $estimate_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_estimate_filename', mb_strtoupper(slug_it($estimate_number), 'UTF-8') . '.pdf', $estimate);

            $pdf->Output($filename, 'D');
            die();
        }
        $this->load->library('app_number_to_word', [
            'clientid' => $estimate->clientid,
        ], 'numberword');

        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');

        $data['title'] = format_estimate_number($estimate->id);
        $this->disableNavigation();
        $this->disableSubMenu();
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['estimate']                      = hooks()->apply_filters('estimate_html_pdf_data', $estimate);
        $data['bodyclass']                     = 'viewestimate';
        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $this->data($data);
        $this->view('estimatehtml');
        add_views_tracking('estimate', $id);
        hooks()->do_action('estimate_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
    }
}
