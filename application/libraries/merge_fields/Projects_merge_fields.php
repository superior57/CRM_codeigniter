<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Projects_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
                [
                    'name'      => 'Project Name',
                    'key'       => '{project_name}',
                    'available' => [
                        'project',
                    ],
                ],
                [
                    'name'      => 'Project Description',
                    'key'       => '{project_description}',
                    'available' => [
                        'project',
                    ],
                ],
                [
                    'name'      => 'Project Start Date',
                    'key'       => '{project_start_date}',
                    'available' => [
                        'project',
                    ],
                ],
                [
                    'name'      => 'Project Deadline',
                    'key'       => '{project_deadline}',
                    'available' => [
                        'project',
                    ],
                ],
                [
                    'name'      => 'Project Link',
                    'key'       => '{project_link}',
                    'available' => [
                        'project',
                    ],
                ],
                    [
                    'name'      => 'File Creator',
                    'key'       => '{file_creator}',
                    'available' => [
                    ],
                    'templates' => [
                        'new-project-file-uploaded-to-customer',
                        'new-project-file-uploaded-to-staff',
                    ],
                ],
                [
                    'name'      => 'Comment Creator',
                    'key'       => '{comment_creator}',
                    'available' => [
                    ],
                    'templates' => [
                        'new-project-discussion-comment-to-customer',
                        'new-project-discussion-comment-to-staff',
                    ],
                ],
                [
                    'name'      => 'Discussion Link',
                    'key'       => '{discussion_link}',
                    'available' => [
                    ],
                    'templates' => [
                        'new-project-discussion-created-to-staff',
                        'new-project-discussion-created-to-customer',
                        'new-project-discussion-comment-to-customer',
                        'new-project-discussion-comment-to-staff',
                        'new-project-file-uploaded-to-staff',
                        'new-project-file-uploaded-to-customer',
                    ],
                ],
                [
                    'name'      => 'Discussion Subject',
                    'key'       => '{discussion_subject}',
                    'available' => [
                    ],
                     'templates' => [
                        'new-project-discussion-created-to-staff',
                        'new-project-discussion-created-to-customer',
                        'new-project-discussion-comment-to-customer',
                        'new-project-discussion-comment-to-staff',
                        'new-project-file-uploaded-to-staff',
                        'new-project-file-uploaded-to-customer',
                    ],
                ],
                [
                    'name'      => 'Discussion Description',
                    'key'       => '{discussion_description}',
                    'available' => [
                    ],
                     'templates' => [
                        'new-project-discussion-created-to-staff',
                        'new-project-discussion-created-to-customer',
                        'new-project-discussion-comment-to-customer',
                        'new-project-discussion-comment-to-staff',
                    ],
                ],
                [
                    'name'      => 'Discussion Creator',
                    'key'       => '{discussion_creator}',
                    'available' => [
                    ],
                    'templates' => [
                        'new-project-discussion-created-to-staff',
                        'new-project-discussion-created-to-customer',
                        'new-project-discussion-comment-to-customer',
                        'new-project-discussion-comment-to-staff',
                    ],
                ],
                [
                    'name'      => 'Discussion Comment',
                    'key'       => '{discussion_comment}',
                    'available' => [
                    ],
                    'templates' => [
                        'new-project-discussion-comment-to-customer',
                        'new-project-discussion-comment-to-staff',
                    ],
                ],
            ];
    }

    /**
     * Project merge fields
     * @param  mixed $project_id      project id
     * @param  array  $additional_data option to pass additional data for the templates eq is staff template or customer template
     * This field is also used for the project discussion files and regular discussions
     * @return array
     */
    public function format($project_id, $additional_data = [])
    {
        $fields = [];

        $fields['{project_name}']           = '';
        $fields['{project_deadline}']       = '';
        $fields['{project_start_date}']     = '';
        $fields['{project_description}']    = '';
        $fields['{project_link}']           = '';
        $fields['{discussion_link}']        = '';
        $fields['{discussion_creator}']     = '';
        $fields['{comment_creator}']        = '';
        $fields['{file_creator}']           = '';
        $fields['{discussion_subject}']     = '';
        $fields['{discussion_description}'] = '';
        $fields['{discussion_comment}']     = '';


        $this->ci->db->where('id', $project_id);
        $project = $this->ci->db->get(db_prefix().'projects')->row();

        $fields['{project_name}']        = $project->name;
        $fields['{project_deadline}']    = _d($project->deadline);
        $fields['{project_start_date}']  = _d($project->start_date);
        $fields['{project_description}'] = $project->description;

        $custom_fields = get_custom_fields('projects');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($project_id, $field['id'], 'projects');
        }

        if (is_client_logged_in()) {
            $cf = get_contact_full_name(get_contact_user_id());
        } else {
            $cf = get_staff_full_name(get_staff_user_id());
        }

        $fields['{file_creator}']       = $cf;
        $fields['{discussion_creator}'] = $cf;
        $fields['{comment_creator}']    = $cf;

        if (isset($additional_data['discussion_id'])) {
            $this->ci->db->where('id', $additional_data['discussion_id']);

            if (isset($additional_data['discussion_type']) && $additional_data['discussion_type'] == 'regular') {
                $table = db_prefix().'projectdiscussions';
            } else {
                // is file
                $table = db_prefix().'project_files';
            }

            $discussion = $this->ci->db->get($table)->row();

            $fields['{discussion_subject}']     = $discussion->subject;
            $fields['{discussion_description}'] = $discussion->description;

            if (isset($additional_data['discussion_comment_id'])) {
                $this->ci->db->where('id', $additional_data['discussion_comment_id']);
                $discussion_comment             = $this->ci->db->get(db_prefix().'projectdiscussioncomments')->row();
                $fields['{discussion_comment}'] = $discussion_comment->content;
            }
        }
        if (isset($additional_data['customer_template'])) {
            $fields['{project_link}'] = site_url('clients/project/' . $project_id);

            if (isset($additional_data['discussion_id']) && isset($additional_data['discussion_type']) && $additional_data['discussion_type'] == 'regular') {
                $fields['{discussion_link}'] = site_url('clients/project/' . $project_id . '?group=project_discussions&discussion_id=' . $additional_data['discussion_id']);
            } elseif (isset($additional_data['discussion_id']) && isset($additional_data['discussion_type']) && $additional_data['discussion_type'] == 'file') {
                // is file
                $fields['{discussion_link}'] = site_url('clients/project/' . $project_id . '?group=project_files&file_id=' . $additional_data['discussion_id']);
            }
        } else {
            $fields['{project_link}'] = admin_url('projects/view/' . $project_id);
            if (isset($additional_data['discussion_type']) && $additional_data['discussion_type'] == 'regular' && isset($additional_data['discussion_id'])) {
                $fields['{discussion_link}'] = admin_url('projects/view/' . $project_id . '?group=project_discussions&discussion_id=' . $additional_data['discussion_id']);
            } else {
                if (isset($additional_data['discussion_id'])) {
                    // is file
                    $fields['{discussion_link}'] = admin_url('projects/view/' . $project_id . '?group=project_files&file_id=' . $additional_data['discussion_id']);
                }
            }
        }

        $custom_fields = get_custom_fields('projects');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($project_id, $field['id'], 'projects');
        }

        return hooks()->apply_filters('project_merge_fields', $fields, [
        'id'              => $project_id,
        'project'         => $project,
        'additional_data' => $additional_data,
     ]);
    }
}
