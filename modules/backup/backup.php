<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Database Backup
Description: Default module to perform database backup
Version: 2.3.0
Requires at least: 2.3.*
*/

require(__DIR__ . '/vendor/autoload.php');

define('BACKUP_MODULE_NAME', 'backup');

/**
 * Database backups folder
 */
define('BACKUPS_FOLDER', FCPATH . 'backups' . '/');

hooks()->add_action('after_cron_run', 'backup_perform');
hooks()->add_action('after_system_last_info_row', 'backup_set_info_manager');
hooks()->add_filter('module_backup_action_links', 'module_backup_action_links');
hooks()->add_action('admin_init', 'backup_module_init_menu_items');

hooks()->add_filter('numbers_of_features_using_cron_job', 'backup_numbers_of_features_using_cron_job');
hooks()->add_filter('used_cron_features', 'backup_used_cron_features');

function backup_numbers_of_features_using_cron_job($number)
{
    $feature = get_option('auto_backup_enabled');
    $number += $feature;

    return $number;
}

function backup_used_cron_features($features)
{
    $feature = get_option('auto_backup_enabled');
    if ($feature > 0) {
        array_push($features, 'Auto database backup');
    }

    return $features;
}

function backup_perform()
{
    $CI = &get_instance();
    $CI->load->library(BACKUP_MODULE_NAME . '/' . 'backup_module');
    $CI->backup_module->make_backup_db();
}

function backup_set_info_manager(){
    $CI = &get_instance();
    $CI->load->library(BACKUP_MODULE_NAME . '/' . 'backup_module');
    $manager = $CI->backup_module->get_backup_manager_name();
    echo '<tr>';
    echo '<td class="bold">Backup Manager</td>';
    echo '<td>'.$manager.'</td>';
    echo '</tr>';
}
/**
* Add additional settings for this module in the module list area
* @param  array $actions current actions
* @return array
*/
function module_backup_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('backup') . '">' . _l('utility_backup') . '</a>';

    return $actions;
}
/**
* Register activation module hook
*/
register_activation_hook(BACKUP_MODULE_NAME, 'backup_module_activation_hook');

function backup_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(BACKUP_MODULE_NAME, [BACKUP_MODULE_NAME]);

/**
 * Init backup module menu items in setup in admin_init hook
 * @return null
 */
function backup_module_init_menu_items()
{
    /**
    * If the logged in user is administrator, add custom menu in Setup
    */
    if (is_admin()) {
        $CI = &get_instance();

        $CI->app_menu->add_sidebar_children_item('utilities', [
                'slug'     => 'utility_backup',
                'name'     => _l('utility_backup'),
                'href'     => admin_url('backup'),
                'position' => 29,
        ]);
    }
}
