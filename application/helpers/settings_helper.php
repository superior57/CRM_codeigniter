<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Add option
 *
 * @since  Version 1.0.1
 *
 * @param string  $name      Option name (required|unique)
 * @param string  $value     Option value
 * @param integer $autoload  Whether to autoload this option
 *
 */
function add_option($name, $value = '', $autoload = 1)
{
    if (!option_exists($name)) {
        $CI = & get_instance();

        $newData = [
                'name'  => $name,
                'value' => $value,
            ];

        if ($CI->db->field_exists('autoload', db_prefix() . 'options')) {
            $newData['autoload'] = $autoload;
        }

        $CI->db->insert(db_prefix() . 'options', $newData);

        $insert_id = $CI->db->insert_id();

        if ($insert_id) {
            return true;
        }

        return false;
    }

    return false;
}

/**
 * Get option value
 * @param  string $name Option name
 * @return mixed
 */
function get_option($name)
{
    $CI = & get_instance();
    if (!class_exists('app', false)) {
        $CI->load->library('app');
    }

    return $CI->app->get_option($name);
}

/**
 * Updates option by name
 *
 * @param  string $name     Option name
 * @param  string $value    Option Value
 * @param  mixed $autoload  Whether to update the autoload
 *
 * @return boolean
 */
function update_option($name, $value, $autoload = null)
{
    /**
     * Create the option if not exists
     * @since  2.3.3
     */
    if (!option_exists($name)) {
        return add_option($name, $value, $autoload === null ? 1 : 0);
    }

    $CI = & get_instance();

    $CI->db->where('name', $name);
    $data = ['value' => $value];

    if ($autoload) {
        $data['autoload'] = $autoload;
    }

    $CI->db->update(db_prefix() . 'options', $data);

    if ($CI->db->affected_rows() > 0) {
        return true;
    }

    return false;
}

/**
 * Delete option
 * @since  Version 1.0.4
 * @param  mixed $name option name
 * @return boolean
 */
function delete_option($name)
{
    $CI = &get_instance();
    $CI->db->where('name', $name);
    $CI->db->delete(db_prefix() . 'options');

    return (bool) $CI->db->affected_rows();
}

/**
 * @since  2.3.3
 * Check whether an option exists
 *
 * @param  string $name option name
 *
 * @return boolean
 */
function option_exists($name)
{
    return total_rows(db_prefix() . 'options', [
        'name' => $name,
    ]) > 0;
}

function app_init_settings_tabs()
{
    $CI = &get_instance();

    $CI->app_tabs->add_settings_tab('general', [
        'name'     => _l('settings_group_general'),
        'view'     => 'admin/settings/includes/general',
        'position' => 5,
    ]);

    $CI->app_tabs->add_settings_tab('company', [
        'name'     => _l('company_information'),
        'view'     => 'admin/settings/includes/company',
        'position' => 10,
    ]);

    $CI->app_tabs->add_settings_tab('localization', [
        'name'     => _l('settings_group_localization'),
        'view'     => 'admin/settings/includes/localization',
        'position' => 15,
    ]);

    $CI->app_tabs->add_settings_tab('email', [
        'name'     => _l('settings_group_email'),
        'view'     => 'admin/settings/includes/email',
        'position' => 20,
    ]);

    $CI->app_tabs->add_settings_tab('sales', [
        'name'     => _l('settings_group_sales'),
        'view'     => 'admin/settings/includes/sales',
        'position' => 25,
    ]);

    $CI->app_tabs->add_settings_tab('subscriptions', [
        'name'     => _l('subscriptions'),
        'view'     => 'admin/settings/includes/subscriptions',
        'position' => 30,
    ]);

    $CI->app_tabs->add_settings_tab('payment_gateways', [
        'name'     => _l('settings_group_online_payment_modes'),
        'view'     => 'admin/settings/includes/payment_gateways',
        'position' => 35,
    ]);

    $CI->app_tabs->add_settings_tab('clients', [
        'name'     => _l('settings_group_clients'),
        'view'     => 'admin/settings/includes/clients',
        'position' => 40,
    ]);

    $CI->app_tabs->add_settings_tab('tasks', [
        'name'     => _l('tasks'),
        'view'     => 'admin/settings/includes/tasks',
        'position' => 45,
    ]);

    $CI->app_tabs->add_settings_tab('tickets', [
        'name'     => _l('support'),
        'view'     => 'admin/settings/includes/tickets',
        'position' => 50,
    ]);

    $CI->app_tabs->add_settings_tab('leads', [
        'name'     => _l('leads'),
        'view'     => 'admin/settings/includes/leads',
        'position' => 55,
    ]);

    $CI->app_tabs->add_settings_tab('calendar', [
        'name'     => _l('settings_calendar'),
        'view'     => 'admin/settings/includes/calendar',
        'position' => 60,
    ]);

    $CI->app_tabs->add_settings_tab('pdf', [
        'name'     => _l('settings_pdf'),
        'view'     => 'admin/settings/includes/pdf',
        'position' => 65,
    ]);

    $CI->app_tabs->add_settings_tab('e_sign', [
        'name'     => 'E-Sign',
        'view'     => 'admin/settings/includes/e_sign',
        'position' => 70,
    ]);

    $CI->app_tabs->add_settings_tab('cronjob', [
        'name'     => _l('settings_group_cronjob'),
        'view'     => 'admin/settings/includes/cronjob',
        'position' => 75,
    ]);

    $CI->app_tabs->add_settings_tab('tags', [
        'name'     => _l('tags'),
        'view'     => 'admin/settings/includes/tags',
        'position' => 80,
    ]);

    $CI->app_tabs->add_settings_tab('pusher', [
        'name'     => 'Pusher.com',
        'view'     => 'admin/settings/includes/pusher',
        'position' => 85,
    ]);

    $CI->app_tabs->add_settings_tab('google', [
        'name'     => 'Google',
        'view'     => 'admin/settings/includes/google',
        'position' => 90,
    ]);

    $CI->app_tabs->add_settings_tab('misc', [
        'name'     => _l('settings_group_misc'),
        'view'     => 'admin/settings/includes/misc',
        'position' => 95,
    ]);
}
