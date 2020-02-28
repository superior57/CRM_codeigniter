<?php

class Surveys_module
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('surveys/surveys_model');
    }

    public function send($cronManuallyInvoked = false)
    {
        $last_survey_cron = get_option('last_survey_send_cron');
        if ($last_survey_cron == '' || (time() > ($last_survey_cron + 3600)) || $cronManuallyInvoked === true) {
            $found_emails = $this->ci->db->count_all_results(db_prefix().'surveysemailsendcron');
            if ($found_emails > 0) {
                $total_emails_per_cron = get_option('survey_send_emails_per_cron_run');
                // Initialize mail library
                $this->ci->email->initialize();
                $this->ci->load->library('email');
                // Load survey model
                $this->ci->load->model('surveys_model');
                // Get all surveys send log where sending emails is not finished
                $this->ci->db->where('iscronfinished', 0);
                $unfinished_surveys_send_log = $this->ci->db->get(db_prefix().'surveysendlog')->result_array();
                foreach ($unfinished_surveys_send_log as $_survey) {
                    $surveyid = $_survey['surveyid'];
                    // Get survey emails that has been not sent yet.
                    $this->ci->db->where('surveyid', $surveyid);
                    $this->ci->db->limit($total_emails_per_cron);
                    $emails = $this->ci->db->get(db_prefix().'surveysemailsendcron')->result_array();
                    $survey = $this->ci->surveys_model->get($surveyid);
                    if ($survey->fromname == '' || $survey->fromname == null) {
                        $survey->fromname = get_option('companyname');
                    }
                    if (stripos($survey->description, '{survey_link}') !== false) {
                        $survey->description = str_ireplace('{survey_link}', '<a href="' . site_url('survey/' . $survey->surveyid . '/' . $survey->hash) . '" target="_blank">' . $survey->subject . '</a>', $survey->description);
                    }
                    $total = $_survey['total'];
                    foreach ($emails as $data) {
                        $emailDescription = $survey->description;

                        if (isset($data['emailid']) && isset($data['listid'])) {
                            $customfields = $this->ci->surveys_model->get_list_custom_fields($data['listid']);

                            foreach ($customfields as $custom_field) {
                                $value = $this->ci->surveys_model->get_email_custom_field_value($data['emailid'], $data['listid'], $custom_field['customfieldid']);

                                $custom_field['fieldslug'] = '{' . $custom_field['fieldslug'] . '}';
                                if (stripos($emailDescription, $custom_field['fieldslug']) !== false) {
                                    $emailDescription = str_ireplace($custom_field['fieldslug'], $value, $emailDescription);
                                }
                            }
                        }
                        $this->ci->email->clear(true);
                        $this->ci->email->from(get_option('smtp_email'), $survey->fromname);
                        $this->ci->email->to($data['email']);
                        $this->ci->email->subject($survey->subject);
                        $this->ci->email->message($emailDescription);

                        if ($this->ci->email->send(true)) {
                            $total++;
                        }

                        $this->ci->db->where('id', $data['id']);
                        $this->ci->db->delete(db_prefix().'surveysemailsendcron');
                    }
                    // Update survey send log
                    $this->ci->db->where('id', $_survey['id']);
                    $this->ci->db->update(db_prefix().'surveysendlog', [
                        'total' => $total,
                    ]);
                    // Check if all emails send
                    $this->ci->db->where('surveyid', $surveyid);
                    $found_emails = $this->ci->db->count_all_results(db_prefix().'surveysemailsendcron');
                    if ($found_emails == 0) {
                        // Update that survey send is finished
                        $this->ci->db->where('id', $_survey['id']);
                        $this->ci->db->update(db_prefix().'surveysendlog', [
                            'iscronfinished' => 1,
                        ]);
                    }
                }
                update_option('last_survey_send_cron', time());
            }
        }
    }
}
