<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Menu Setup
Description: Default module to apply changes to the menus
Version: 2.3.0
Requires at least: 2.3.*
*/

define('MENU_SETUP_MODULE_NAME', 'menu_setup');

$CI = &get_instance();

hooks()->add_filter('sidebar_menu_items', 'app_admin_sidebar_custom_options', 999);
hooks()->add_filter('sidebar_menu_items', 'app_admin_sidebar_custom_positions', 998);

hooks()->add_filter('setup_menu_items', 'app_admin_setup_menu_custom_options', 999);
hooks()->add_filter('setup_menu_items', 'app_admin_setup_menu_custom_positions', 998);
hooks()->add_filter('module_menu_setup_action_links', 'module_menu_setup_action_links');
hooks()->add_action('admin_init', 'menu_setup_init_menu_items');

/**
* Add additional settings for this module in the module list area
* @param  array $actions current actions
* @return array
*/
function module_menu_setup_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('menu_setup/main_menu') . '">' . _l('main_menu') . '</a>';
    $actions[] = '<a href="' . admin_url('menu_setup/setup_menu') . '">' . _l('setup_menu') . '</a>';

    return $actions;
}
/**
* Load the module helper
*/
$CI->load->helper(MENU_SETUP_MODULE_NAME . '/menu_setup');

/**
* Register activation module hook
*/
register_activation_hook(MENU_SETUP_MODULE_NAME, 'menu_setup_activation_hook');

function menu_setup_activation_hook()
{
    require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(MENU_SETUP_MODULE_NAME, [MENU_SETUP_MODULE_NAME]);

/**
 * Init menu setup module menu items in setup in admin_init hook
 * @return null
 */
function menu_setup_init_menu_items()
{
    /**
    * If the logged in user is administrator, add custom menu in Setup
    */
    if (is_admin()) {
        $CI = &get_instance();
        $CI->app_menu->add_setup_menu_item('menu-options', [
            'collapse' => true,
            'name'     => _l('menu_builder'),
            'position' => 60,
        ]);

        $CI->app_menu->add_setup_children_item('menu-options', [
            'slug'     => 'main-menu-options',
            'name'     => _l('main_menu'),
            'href'     => admin_url('menu_setup/main_menu'),
            'position' => 5,
        ]);

        $CI->app_menu->add_setup_children_item('menu-options', [
            'slug'     => 'setup-menu-options',
            'name'     => _l('setup_menu'),
            'href'     => admin_url('menu_setup/setup_menu'),
            'position' => 10,
        ]);
    }
}
