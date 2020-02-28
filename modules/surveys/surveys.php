<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Surveys
Description: Default module for sending surveys
Version: 2.3.0
Requires at least: 2.3.*
*/

define('SURVEYS_MODULE_NAME', 'surveys');

hooks()->add_action('after_cron_run', 'surveys_send');
hooks()->add_action('admin_init', 'surveys_module_init_menu_items');
hooks()->add_action('admin_init', 'surveys_permissions');
hooks()->add_action('after_cron_settings_last_tab', 'survey_cron_settings_tab');
hooks()->add_action('after_cron_settings_last_tab_content', 'survey_cron_settings_tab_content');
hooks()->add_action('contact_deleted', 'survey_contact_deleted_hook', 10, 2);

hooks()->add_filter('numbers_of_features_using_cron_job', 'surveys_numbers_of_features_using_cron_job');
hooks()->add_filter('used_cron_features', 'surveys_used_cron_features');
hooks()->add_filter('migration_tables_to_replace_old_links', 'surveys_migration_tables_to_replace_old_links');
hooks()->add_filter('global_search_result_query', 'surveys_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'surveys_global_search_result_output', 10, 2);

function surveys_global_search_result_output($output, $data)
{
    if ($data['type'] == 'surveys') {
        $output = '<a href="' . admin_url('surveys/survey/' . $data['result']['surveyid']) . '">' . $data['result']['subject'] . '</a>';
    }

    return $output;
}

function surveys_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('surveys', '', 'view')) {
        // Surveys
        $CI->db->select()
        ->from(db_prefix() . 'surveys')
        ->like('subject', $q)
        ->or_like('slug', $q)
        ->or_like('description', $q)
        ->or_like('viewdescription', $q)
        ->limit($limit);

        $CI->db->order_by('subject', 'ASC');

        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'surveys',
                'search_heading' => _l('surveys'),
            ];
    }

    return $result;
}

function survey_contact_deleted_hook($id, $contact)
{
    $CI = &get_instance();
    $CI->db->where('email', $contact->email);
    $CI->db->delete(db_prefix() . 'surveysemailsendcron');
    if (is_gdpr()) {
        $CI->db->where('ip', $contact->last_ip);
        $CI->db->delete(db_prefix() . 'surveyresultsets');
    }
}

function survey_cron_settings_tab()
{
    get_instance()->load->view('surveys/settings_tab');
}

function survey_cron_settings_tab_content()
{
    get_instance()->load->view('surveys/settings_tab_content');
}

function surveys_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                    'table' => db_prefix() . 'surveys',
                    'field' => 'description',
                ];
    $tables[] = [
                    'table' => db_prefix() . 'surveys',
                    'field' => 'viewdescription',
                ];

    return $tables;
}

function surveys_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('surveys', $capabilities, _l('surveys'));
}

function surveys_numbers_of_features_using_cron_job($number)
{
    $feature = total_rows(db_prefix() . 'surveysemailsendcron');
    $number += $feature;

    return $number;
}

function surveys_used_cron_features($features)
{
    $feature = total_rows(db_prefix() . 'surveysemailsendcron');
    if ($feature > 0) {
        array_push($features, 'Surveys');
    }

    return $features;
}

function surveys_send($cronManuallyInvoked)
{
    $CI = &get_instance();
    $CI->load->library(SURVEYS_MODULE_NAME . '/' . 'surveys_module');
    $CI->surveys_module->send($cronManuallyInvoked);
}

/**
* Register activation module hook
*/
register_activation_hook(SURVEYS_MODULE_NAME, 'surveys_module_activation_hook');

function surveys_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(SURVEYS_MODULE_NAME, [SURVEYS_MODULE_NAME]);

/**
 * Init surveys module menu items in setup in admin_init hook
 * @return null
 */
function surveys_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
            'name'       => _l('survey'),
            'permission' => 'surveys',
            'url'        => 'surveys/survey',
            'position'   => 69,
            ]);

    if (has_permission('surveys', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('utilities', [
                'slug'     => 'surveys',
                'name'     => _l('surveys'),
                'href'     => admin_url('surveys'),
                'position' => 26,
        ]);
    }
}
/**
 * Helper function to get text question answers
 * @param  integer $questionid
 * @param  itneger $surveyid
 * @return array
 */
function surveys_get_text_question_answers($questionid, $surveyid)
{
    $CI = & get_instance();
    $CI->db->select('answer,resultid');
    $CI->db->from(db_prefix() . 'form_results');
    $CI->db->where('questionid', $questionid);
    $CI->db->where('rel_id', $surveyid);
    $CI->db->where('rel_type', 'survey');

    return $CI->db->get()->result_array();
}
