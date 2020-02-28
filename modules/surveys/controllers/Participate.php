<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Participate extends ClientsController
{
    public function index($id, $hash)
    {
        $this->load->model('surveys_model');
        $survey = $this->surveys_model->get($id);

        // Last statement is for
        if (!$survey
            || ($survey->hash != $hash)
            || (!$hash || !$id)
            // Users with permission manage surveys to preview the survey even if is not active
            || ($survey->active == 0 && !has_permission('surveys', '', 'view'))
             // Check if survey is only for logged in participants / staff / clients
            || ($survey->onlyforloggedin == 1 && !is_logged_in())
        ) {
            show_404();
        }

        // Ip Restrict Check
        if ($survey->iprestrict == 1) {
            $this->db->where('surveyid', $id);
            $this->db->where('ip', $this->input->ip_address());
            $total = $this->db->count_all_results(db_prefix().'surveyresultsets');
            if ($total > 0) {
                show_404();
            }
        }
        if ($this->input->post()) {
            $success = $this->surveys_model->add_survey_result($id, $this->input->post());
            if ($success) {
                $survey = $this->surveys_model->get($id);
                if ($survey->redirect_url !== '') {
                    redirect($survey->redirect_url);
                }
                // Message is by default in English because there is no easy way to know the customer language
                set_alert('success', hooks()->apply_filters('survey_success_message', 'Thank you for participating in this survey. Your answers are very important to us.'));

                redirect(hooks()->apply_filters('survey_default_redirect', site_url('survey/' . $id . '/' . $hash . '?participated=yes')));
            }
        }

        $this->app_css->theme('surveys-css', module_dir_url('surveys', 'assets/css/surveys.css'));

        $this->disableNavigation()
        ->disableSubMenu();

        $this->data(['survey'=>$survey]);
        $this->title($survey->subject);
        no_index_customers_area();
        $this->view('participate');
        $this->layout();
    }
}
