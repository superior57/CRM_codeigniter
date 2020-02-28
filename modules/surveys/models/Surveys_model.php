<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Surveys_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get survey and all questions by id
     * @param  mixed $id survey id
     * @return object
     */
    public function get($id = '')
    {
        $this->db->where('surveyid', $id);
        $survey = $this->db->get(db_prefix().'surveys')->row();
        if (!$survey) {
            return false;
        }
        $this->db->where('rel_id', $survey->surveyid);
        $this->db->where('rel_type', 'survey');
        $this->db->order_by('question_order', 'asc');
        $questions = $this->db->get(db_prefix().'form_questions')->result_array();
        $i         = 0;
        foreach ($questions as $question) {
            $this->db->where('questionid', $question['questionid']);
            $box                      = $this->db->get(db_prefix().'form_question_box')->row();
            $questions[$i]['boxid']   = $box->boxid;
            $questions[$i]['boxtype'] = $box->boxtype;
            if ($box->boxtype == 'checkbox' || $box->boxtype == 'radio') {
                $this->db->order_by('questionboxdescriptionid', 'asc');
                $this->db->where('boxid', $box->boxid);
                $boxes_description = $this->db->get(db_prefix().'form_question_box_description')->result_array();
                if (count($boxes_description) > 0) {
                    $questions[$i]['box_descriptions'] = [];
                    foreach ($boxes_description as $box_description) {
                        $questions[$i]['box_descriptions'][] = $box_description;
                    }
                }
            }
            $i++;
        }
        $survey->questions = $questions;

        return $survey;
    }

    /**
     * Update survey
     * @param  array $data     survey $_POST data
     * @param  mixed $surveyid survey id
     * @return boolean
     */
    public function update($data, $surveyid)
    {
        if (isset($data['disabled'])) {
            $data['active'] = 0;
            unset($data['disabled']);
        } else {
            $data['active'] = 1;
        }
        if (isset($data['onlyforloggedin'])) {
            $data['onlyforloggedin'] = 1;
        } else {
            $data['onlyforloggedin'] = 0;
        }
        if (isset($data['iprestrict'])) {
            $data['iprestrict'] = 1;
        } else {
            $data['iprestrict'] = 0;
        }
        $this->db->where('surveyid', $surveyid);
        $this->db->update(db_prefix().'surveys', [
            'subject'         => $data['subject'],
            'slug'            => slug_it($data['subject']),
            'description'     => $data['description'],
            'viewdescription' => $data['viewdescription'],
            'iprestrict'      => $data['iprestrict'],
            'active'          => $data['active'],
            'onlyforloggedin' => $data['onlyforloggedin'],
            'redirect_url'    => $data['redirect_url'],
            'fromname'        => $data['fromname'],
        ]);
        if ($this->db->affected_rows() > 0) {
            log_activity('Survey Updated [ID: ' . $surveyid . ', Subject: ' . $data['subject'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Add new survey
     * @param array $data survey $_POST data
     * @return mixed
     */
    public function add($data)
    {
        if (isset($data['disabled'])) {
            $data['active'] = 0;
            unset($data['disabled']);
        } else {
            $data['active'] = 1;
        }
        if (isset($data['iprestrict'])) {
            $data['iprestrict'] = 1;
        } else {
            $data['iprestrict'] = 0;
        }
        if (isset($data['onlyforloggedin'])) {
            $data['onlyforloggedin'] = 1;
        } else {
            $data['onlyforloggedin'] = 0;
        }
        $datecreated = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix().'surveys', [
            'subject'         => $data['subject'],
            'slug'            => slug_it($data['subject']),
            'description'     => $data['description'],
            'viewdescription' => $data['viewdescription'],
            'datecreated'     => $datecreated,
            'active'          => $data['active'],
            'onlyforloggedin' => $data['onlyforloggedin'],
            'iprestrict'      => $data['iprestrict'],
            'redirect_url'    => $data['redirect_url'],
            'hash'            => md5($datecreated),
            'fromname'        => $data['fromname'],
        ]);
        $surveyid = $this->db->insert_id();
        if (!$surveyid) {
            // return false;
        }
        log_activity('New Survey Added [ID: ' . $surveyid . ', Subject: ' . $data['subject'] . ']');

        return $surveyid;
    }

    /**
     * Delete survey and all connections
     * @param  mixed $surveyid survey id
     * @return boolean
     */
    public function delete($surveyid)
    {
        $affectedRows = 0;
        $this->db->where('surveyid', $surveyid);
        $this->db->delete(db_prefix().'surveys');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            // get all questions from the survey
            $this->db->where('rel_id', $surveyid);
            $this->db->where('rel_type', 'survey');
            $questions = $this->db->get(db_prefix().'form_questions')->result_array();
            // Delete the question boxes
            foreach ($questions as $question) {
                $this->db->where('questionid', $question['questionid']);
                $this->db->delete(db_prefix().'form_question_box');
                $this->db->where('questionid', $question['questionid']);
                $this->db->delete(db_prefix().'form_question_box_description');
            }
            $this->db->where('rel_id', $surveyid);
            $this->db->where('rel_type', 'survey');
            $this->db->delete(db_prefix().'form_questions');

            $this->db->where('rel_id', $surveyid);
            $this->db->where('rel_type', 'survey');
            $this->db->delete(db_prefix().'form_results');

            $this->db->where('surveyid', $surveyid);
            $this->db->delete(db_prefix().'surveyresultsets');
        }
        if ($affectedRows > 0) {
            log_activity('Survey Deleted [ID: ' . $surveyid . ']');

            return true;
        }

        return false;
    }

    /**
     * Get survey send log
     * @param  mixed $surveyid surveyid
     * @return array
     */
    public function get_survey_send_log($surveyid)
    {
        $this->db->where('surveyid', $surveyid);

        return $this->db->get(db_prefix().'surveysendlog')->result_array();
    }

    /**
     * Add new survey send log
     * @param mixed $surveyid surveyid
     * @param integer @iscronfinished always to 0
     * @param integer $lists array of lists which survey has been send
     */
    public function init_survey_send_log($surveyid, $iscronfinished = 0, $lists = [])
    {
        $this->db->insert(db_prefix().'surveysendlog', [
            'date'               => date('Y-m-d H:i:s'),
            'surveyid'           => $surveyid,
            'total'              => 0,
            'iscronfinished'     => $iscronfinished,
            'send_to_mail_lists' => serialize($lists),
        ]);
        $log_id = $this->db->insert_id();
        log_activity('Survey Email Lists Send Setup [ID: ' . $surveyid . ', Lists: ' . implode(' ', $lists) . ']');

        return $log_id;
    }

    public function remove_survey_send($id)
    {
        $affectedRows = 0;
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'surveysendlog');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        $this->db->where('log_id', $id);
        $this->db->delete(db_prefix().'surveysemailsendcron');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        if ($affectedRows > 0) {
            return true;
        }

        return false;
    }

    /**
     * Add survey result by user
     * @param mixed $id     surveyid
     * @param mixed $result $_POST results/questions answers
     */
    public function add_survey_result($id, $result)
    {
        $this->db->insert(db_prefix().'surveyresultsets', [
            'date'      => date('Y-m-d H:i:s'),
            'surveyid'  => $id,
            'ip'        => $this->input->ip_address(),
            'useragent' => substr($this->input->user_agent(), 0, 149),
        ]);
        $resultsetid = $this->db->insert_id();
        if ($resultsetid) {
            if (isset($result['selectable']) && sizeof($result['selectable']) > 0) {
                foreach ($result['selectable'] as $boxid => $question_answers) {
                    foreach ($question_answers as $questionid => $answer) {
                        $count = count($answer);
                        for ($i = 0; $i < $count; $i++) {
                            $this->db->insert(db_prefix().'form_results', [
                                'boxid'            => $boxid,
                                'boxdescriptionid' => $answer[$i],
                                'rel_id'           => $id,
                                'rel_type'         => 'survey',
                                'questionid'       => $questionid,
                                'resultsetid'      => $resultsetid,
                            ]);
                        }
                    }
                }
            }
            unset($result['selectable']);

            if (isset($result['question'])) {
                foreach ($result['question'] as $questionid => $val) {
                    $boxid = $this->get_question_box_id($questionid);
                    $this->db->insert(db_prefix().'form_results', [
                        'boxid'       => $boxid,
                        'rel_id'      => $id,
                        'rel_type'    => 'survey',
                        'questionid'  => $questionid,
                        'answer'      => $val[0],
                        'resultsetid' => $resultsetid,
                    ]);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Remove survey question
     * @param  mixed $questionid questionid
     * @return boolean
     */
    public function remove_question($questionid)
    {
        $affectedRows = 0;
        $this->db->where('questionid', $questionid);
        $this->db->delete(db_prefix().'form_question_box_description');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        $this->db->where('questionid', $questionid);
        $this->db->delete(db_prefix().'form_question_box');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        $this->db->where('questionid', $questionid);
        $this->db->delete(db_prefix().'form_questions');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        if ($affectedRows > 0) {
            log_activity('Survey Question Deleted [' . $questionid . ']');

            return true;
        }

        return false;
    }

    /**
     * Remove survey question box description / radio/checkbox
     * @param  mixed $questionboxdescriptionid question box description id
     * @return boolean
     */
    public function remove_box_description($questionboxdescriptionid)
    {
        $this->db->where('questionboxdescriptionid', $questionboxdescriptionid);
        $this->db->delete(db_prefix().'form_question_box_description');
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Add survey box description radio/checkbox
     * @param mixed $questionid  question id
     * @param mixed $boxid       main box id
     * @param string $description box question
     */
    public function add_box_description($questionid, $boxid, $description = '')
    {
        $this->db->insert(db_prefix().'form_question_box_description', [
            'questionid'  => $questionid,
            'boxid'       => $boxid,
            'description' => $description,
        ]);

        return $this->db->insert_id();
    }

    /**
     * Private functino for insert question
     * @param  mixed $surveyid survey id
     * @param  string $question question
     * @return mixed
     */
    private function insert_survey_question($surveyid, $question = '')
    {
        $this->db->insert(db_prefix().'form_questions', [
            'rel_id'   => $surveyid,
            'rel_type' => 'survey',
            'question' => $question,
        ]);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Survey Question Added [SurveyID: ' . $surveyid . ']');
        }

        return $insert_id;
    }

    /**
     * Add new question type
     * @param  string $type       checkbox/textarea/radio/input
     * @param  mixed $questionid question id
     * @return mixed
     */
    private function insert_question_type($type, $questionid)
    {
        $this->db->insert(db_prefix().'form_question_box', [
            'boxtype'    => $type,
            'questionid' => $questionid,
        ]);

        return $this->db->insert_id();
    }

    /**
     * Add new question ti survey / ajax
     * @param array $data $_POST question data
     */
    public function add_survey_question($data)
    {
        $questionid = $this->insert_survey_question($data['surveyid']);
        if ($questionid) {
            $boxid    = $this->insert_question_type($data['type'], $questionid);
            $response = [
                'questionid' => $questionid,
                'boxid'      => $boxid,
            ];
            if ($data['type'] == 'checkbox' or $data['type'] == 'radio') {
                $questionboxdescriptionid = $this->add_box_description($questionid, $boxid);
                array_push($response, [
                    'questionboxdescriptionid' => $questionboxdescriptionid,
                ]);
            }

            return $response;
        }

        return false;
    }

    /**
     * Update question / ajax
     * @param  array $data $_POST question data
     * @return boolean
     */
    public function update_question($data)
    {
        $_required = 1;
        if ($data['question']['required'] == 'false') {
            $_required = 0;
        }
        $affectedRows = 0;
        $this->db->where('questionid', $data['questionid']);
        $this->db->update(db_prefix().'form_questions', [
            'question' => $data['question']['value'],
            'required' => $_required,
        ]);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        if (isset($data['boxes_description'])) {
            foreach ($data['boxes_description'] as $box_description) {
                $this->db->where('questionboxdescriptionid', $box_description[0]);
                $this->db->update(db_prefix().'form_question_box_description', [
                    'description' => $box_description[1],
                ]);
                if ($this->db->affected_rows() > 0) {
                    $affectedRows++;
                }
            }
        }
        if ($affectedRows > 0) {
            log_activity('Survey Question Updated [QuestionID: ' . $data['questionid'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Reorder survey quesions / ajax
     * @param  mixed $data surveys order and question id
     */
    public function update_survey_questions_orders($data)
    {
        foreach ($data['data'] as $question) {
            $this->db->where('questionid', $question[0]);
            $this->db->update(db_prefix().'form_questions', [
                'question_order' => $question[1],
            ]);
        }
    }

    /**
     * Get quesion box id
     * @param  mixed $questionid questionid
     * @return integer
     */
    private function get_question_box_id($questionid)
    {
        $this->db->select('boxid');
        $this->db->from(db_prefix().'form_question_box');
        $this->db->where('questionid', $questionid);
        $box = $this->db->get()->row();

        return $box->boxid;
    }

    /**
     * Change survey status / active / inactive
     * @param  mixed $id     surveyid
     * @param  integer $status active or inactive
     */
    public function change_survey_status($id, $status)
    {
        $this->db->where('surveyid', $id);
        $this->db->update(db_prefix().'surveys', [
            'active' => $status,
        ]);
        log_activity('Survey Status Changed [SurveyID: ' . $id . ' - Active: ' . $status . ']');
    }

    // MAIL LISTS

    /**
     * Get mail list/s
     * @param  mixed $id Optional
     * @return mixed     object if id is passed else array
     */
    public function get_mail_lists($id = '')
    {
        $this->db->select();
        $this->db->from(db_prefix().'emaillists');
        if (is_numeric($id)) {
            $this->db->where('listid', $id);

            return $this->db->get()->row();
        }
        $lists = $this->db->get()->result_array();

        return $lists;
    }

    /**
     * Add new mail list
     * @param array $data mail list data
     */
    public function add_mail_list($data)
    {
        $data['creator']     = get_staff_full_name(get_staff_user_id());
        $data['datecreated'] = date('Y-m-d H:i:s');
        if (isset($data['list_custom_fields_add'])) {
            $custom_fields = $data['list_custom_fields_add'];
            unset($data['list_custom_fields_add']);
        }
        $this->db->insert(db_prefix().'emaillists', $data);
        $listid = $this->db->insert_id();
        if (isset($custom_fields)) {
            foreach ($custom_fields as $field) {
                if (!empty($field)) {
                    $this->db->insert(db_prefix().'maillistscustomfields', [
                        'listid'    => $listid,
                        'fieldname' => $field,
                        'fieldslug' => slug_it($data['name'] . '-' . $field),
                    ]);
                }
            }
        }
        log_activity('New Email List Added [ID: ' . $listid . ', ' . $data['name'] . ']');

        return $listid;
    }

    /**
     * Update mail list
     * @param  mixed $data mail list data
     * @param  mixed $id   list id
     * @return boolean
     */
    public function update_mail_list($data, $id)
    {
        if (isset($data['list_custom_fields_add'])) {
            foreach ($data['list_custom_fields_add'] as $field) {
                if (!empty($field)) {
                    $this->db->insert(db_prefix().'maillistscustomfields', [
                        'listid'    => $id,
                        'fieldname' => $field,
                        'fieldslug' => slug_it($field),
                    ]);
                }
            }
            unset($data['list_custom_fields_add']);
        }
        if (isset($data['list_custom_fields_update'])) {
            foreach ($data['list_custom_fields_update'] as $key => $update_field) {
                $this->db->where('customfieldid', $key);
                $this->db->update(db_prefix().'maillistscustomfields', [
                    'fieldname' => $update_field,
                    'fieldslug' => slug_it($data['name'] . '-' . $update_field),
                ]);
            }
            unset($data['list_custom_fields_update']);
        }
        $this->db->where('listid', $id);
        $this->db->update(db_prefix().'emaillists', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Mail List Updated [ID: ' . $id . ', ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete mail list and all connections
     * @param  mixed $id list id
     * @return boolean
     */
    public function delete_mail_list($id)
    {
        $affectedRows = 0;
        $this->db->where('listid', $id);
        $this->db->delete(db_prefix().'maillistscustomfieldvalues');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        $this->db->where('listid', $id);
        $this->db->delete(db_prefix().'maillistscustomfields');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        $this->db->where('listid', $id);
        $this->db->delete(db_prefix().'listemails');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        $this->db->where('listid', $id);
        $this->db->delete(db_prefix().'emaillists');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        if ($affectedRows > 0) {
            log_activity('Mail List Deleted [ID: ' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Get all emails from mail list
     * @param  mixed $id list id
     * @return array
     */
    public function get_mail_list_emails($id)
    {
        $this->db->select('email,emailid')->from(db_prefix().'listemails')->where('listid', $id);

        return $this->db->get()->result_array();
    }

    /**
     * List data used in view
     * @param  mixed $id list id
     * @return mixed object
     */
    public function get_data_for_view_list($id)
    {
        $list         = $this->get_mail_lists($id);
        $list_emails  = $this->db->select('email,dateadded,emailid')->from(db_prefix().'listemails')->where('listid', $id)->get()->result_array();
        $list->emails = $list_emails;

        return $list;
    }

    /**
     * Get list custom fields added by staff
     * @param  mixed $listid list id
     * @return array
     */
    public function get_list_custom_fields($id)
    {
        $this->db->where('listid', $id);

        return $this->db->get(db_prefix().'maillistscustomfields')->result_array();
    }

    /**
     * Get custom field values
     * @param  mixed $emailid       email id from db
     * @param  mixed $listid        lis id
     * @param  mixed $customfieldid custom field id from db
     * @return mixed
     */
    public function get_email_custom_field_value($emailid, $listid, $customfieldid)
    {
        $this->db->where('emailid', $emailid);
        $this->db->where('listid', $listid);
        $this->db->where('customfieldid', $customfieldid);
        $row = $this->db->get(db_prefix().'maillistscustomfieldvalues')->row();
        if ($row) {
            return $row->value;
        }

        return '';
    }

    /**
     * Add new email to mail list
     * @param array $data
     * @return mixed
     */
    public function add_email_to_list($data)
    {
        $exists = total_rows(db_prefix().'listemails', [
            'email'  => $data['email'],
            'listid' => $data['listid'],
        ]);
        if ($exists > 0) {
            return [
                'success'       => false,
                'duplicate'     => true,
                'error_message' => _l('email_is_duplicate_mail_list'),
            ];
        }
        $dateadded = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix().'listemails', [
            'listid'    => $data['listid'],
            'email'     => $data['email'],
            'dateadded' => $dateadded,
        ]);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if (isset($data['customfields'])) {
                foreach ($data['customfields'] as $key => $val) {
                    $this->db->insert(db_prefix().'maillistscustomfieldvalues', [
                        'listid'        => $data['listid'],
                        'customfieldid' => $key,
                        'emailid'       => $insert_id,
                        'value'         => $val,
                    ]);
                }
            }
            log_activity('Email Added To Mail List [ID:' . $data['listid'] . ' - Email:' . $data['email'] . ']');

            return [
                'success'   => true,
                'dateadded' => $dateadded,
                'email'     => $data['email'],
                'emailid'   => $insert_id,
                'message'   => _l('email_added_to_mail_list_successfully'),
            ];
        }

        return [
            'success' => false,
        ];
    }

    /**
     * Remove email from mail list
     * @param  mixed $emailid email id (is unique)
     * @return mixed          array
     */
    public function remove_email_from_mail_list($emailid)
    {
        $this->db->where('emailid', $emailid);
        $this->db->delete(db_prefix().'listemails');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('emailid', $emailid);
            $this->db->delete(db_prefix().'maillistscustomfieldvalues');

            return [
                'success' => true,
                'message' => _l('email_removed_from_list'),
            ];
        }

        return [
            'success' => false,
            'message' => _l('email_remove_fail'),
        ];
    }

    /**
     * Remove mail list custom field and all connections
     * @param  mixed $fieldid custom field id from db
     * @return mixed          array
     */
    public function remove_list_custom_field($fieldid)
    {
        $this->db->where('customfieldid', $fieldid);
        $this->db->delete(db_prefix().'maillistscustomfields');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('customfieldid', $fieldid);
            $this->db->delete(db_prefix().'maillistscustomfieldvalues');

            return [
                'success' => true,
                'message' => _l('custom_field_deleted_success'),
            ];
        }

        return [
            'success' => false,
            'message' => _l('custom_field_deleted_fail'),
        ];
    }
}
