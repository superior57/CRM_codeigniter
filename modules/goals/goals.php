<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Goals
Description: Default module for defining goals
Version: 2.3.0
Requires at least: 2.3.*
*/

define('GOALS_MODULE_NAME', 'goals');

hooks()->add_action('after_cron_run', 'goals_notification');
hooks()->add_action('admin_init', 'goals_module_init_menu_items');
hooks()->add_action('staff_member_deleted', 'goals_staff_member_deleted');
hooks()->add_action('admin_init', 'goals_permissions');

hooks()->add_filter('migration_tables_to_replace_old_links', 'goals_migration_tables_to_replace_old_links');
hooks()->add_filter('global_search_result_query', 'goals_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'goals_global_search_result_output', 10, 2);
hooks()->add_filter('get_dashboard_widgets', 'goals_add_dashboard_widget');

function goals_add_dashboard_widget($widgets)
{
    $widgets[] = [
            'path'      => 'goals/widget',
            'container' => 'right-4',
        ];

    return $widgets;
}

function goals_staff_member_deleted($data)
{
    $CI = &get_instance();
    $CI->db->where('staff_id', $data['id']);
    $CI->db->update(db_prefix() . 'goals', [
            'staff_id' => $data['transfer_data_to'],
        ]);
}

function goals_global_search_result_output($output, $data)
{
    if ($data['type'] == 'goals') {
        $output = '<a href="' . admin_url('goals/goal/' . $data['result']['id']) . '">' . $data['result']['subject'] . '</a>';
    }

    return $output;
}

function goals_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('goals', '', 'view')) {
        // Goals
        $CI->db->select()->from(db_prefix() . 'goals')->like('description', $q)->or_like('subject', $q)->limit($limit);

        $CI->db->order_by('subject', 'ASC');

        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'goals',
                'search_heading' => _l('goals'),
            ];
    }

    return $result;
}

function goals_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix() . 'goals',
                'field' => 'description',
            ];

    return $tables;
}

function goals_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('goals', $capabilities, _l('goals'));
}

function goals_notification()
{
    $CI = &get_instance();
    $CI->load->model('goals/goals_model');
    $goals = $CI->goals_model->get('', true);
    foreach ($goals as $goal) {
        $achievement = $CI->goals_model->calculate_goal_achievement($goal['id']);
        if ($achievement['percent'] >= 100) {
            if ($goal['notify_when_achieve'] == 1) {
                if (date('Y-m-d') >= $goal['end_date']) {
                    $CI->goals_model->notify_staff_members($goal['id'], 'success', $achievement);
                }
            }
        } else {
            // not yet achieved, check for end date
            if ($goal['notify_when_fail'] == 1) {
                if (date('Y-m-d') > $goal['end_date']) {
                    $CI->goals_model->notify_staff_members($goal['id'], 'failed', $achievement);
                }
            }
        }
    }
}

/**
* Register activation module hook
*/
register_activation_hook(GOALS_MODULE_NAME, 'goals_module_activation_hook');

function goals_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(GOALS_MODULE_NAME, [GOALS_MODULE_NAME]);

/**
 * Init goals module menu items in setup in admin_init hook
 * @return null
 */
function goals_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
            'name'       => _l('goal'),
            'url'        => 'goals/goal',
            'permission' => 'goals',
            'position'   => 56,
            ]);

    if (has_permission('goals', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('utilities', [
                'slug'     => 'goals-tracking',
                'name'     => _l('goals'),
                'href'     => admin_url('goals'),
                'position' => 24,
        ]);
    }
}


/**
 * Get goal types for the goals feature
 * @return array
 */
function get_goal_types()
{
    $types = [
        [
            'key'      => 1,
            'lang_key' => 'goal_type_total_income',
            'subtext'  => 'goal_type_income_subtext',
        ],
        [
            'key'      => 2,
            'lang_key' => 'goal_type_convert_leads',
        ],
        [
            'key'      => 3,
            'lang_key' => 'goal_type_increase_customers_without_leads_conversions',
            'subtext'  => 'goal_type_increase_customers_without_leads_conversions_subtext',
        ],
        [
            'key'      => 4,
            'lang_key' => 'goal_type_increase_customers_with_leads_conversions',
            'subtext'  => 'goal_type_increase_customers_with_leads_conversions_subtext',
        ],
        [
            'key'      => 5,
            'lang_key' => 'goal_type_make_contracts_by_type_calc_database',
            'subtext'  => 'goal_type_make_contracts_by_type_calc_database_subtext',
        ],
        [
            'key'      => 7,
            'lang_key' => 'goal_type_make_contracts_by_type_calc_date',
            'subtext'  => 'goal_type_make_contracts_by_type_calc_date_subtext',
        ],
        [
            'key'      => 6,
            'lang_key' => 'goal_type_total_estimates_converted',
            'subtext'  => 'goal_type_total_estimates_converted_subtext',
        ],
    ];

    return hooks()->apply_filters('get_goal_types', $types);
}
/**
 * Translate goal type based on passed key
 * @param  mixed $key
 * @return string
 */
function format_goal_type($key)
{
    foreach (get_goal_types() as $type) {
        if ($type['key'] == $key) {
            return _l($type['lang_key']);
        }
    }

    return $type;
}
