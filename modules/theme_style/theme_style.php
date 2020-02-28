<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Theme Style
Description: Default module to apply additional CSS styles
Version: 2.3.0
Requires at least: 2.3.*
*/

define('THEME_STYLE_MODULE_NAME', 'theme_style');

$CI = &get_instance();

/**
 * Load the module helper
 */
$CI->load->helper(THEME_STYLE_MODULE_NAME . '/theme_style');

/**
 * Register activation module hook
 */
register_activation_hook(THEME_STYLE_MODULE_NAME, 'theme_style_activation_hook');

function theme_style_activation_hook()
{
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(THEME_STYLE_MODULE_NAME, [THEME_STYLE_MODULE_NAME]);

/**
 * Actions for inject the custom styles
 */
hooks()->add_action('app_admin_head', 'theme_style_admin_head');
hooks()->add_action('app_admin_authentication_head', 'theme_style_admin_head');
hooks()->add_action('app_customers_head', 'theme_style_clients_area_head');
hooks()->add_action('app_admin_authentication_head', 'theme_style_general_and_buttons');
hooks()->add_action('app_external_form_head', 'theme_style_general_and_buttons');
hooks()->add_filter('module_theme_style_action_links', 'module_theme_style_action_links');
hooks()->add_action('admin_init', 'theme_style_init_menu_items');
/**
 * Add additional settings for this module in the module list area
 * @param  array $actions current actions
 * @return array
 */
function module_theme_style_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('theme_style') . '">' . _l('settings') . '</a>';

    return $actions;
}
/**
 * Admin area applied styles
 * @return null
 */
function theme_style_admin_head()
{
    theme_style_render(['general', 'tabs', 'buttons', 'admin', 'modals', 'tags']);
    theme_style_custom_css('theme_style_custom_admin_area');
}

/**
 * Clients area theme applied styles
 * @return null
 */
function theme_style_clients_area_head()
{
    theme_style_render(['general', 'tabs', 'buttons', 'customers', 'modals']);
    theme_style_custom_css('theme_style_custom_clients_area');
}

/**
 * Custom CSS
 * @param  string $main_area clients or admin area options
 * @return null
 */
function theme_style_custom_css($main_area)
{
    $clients_or_admin_area             = get_option($main_area);
    $custom_css_admin_and_clients_area = get_option('theme_style_custom_clients_and_admin_area');
    if (!empty($clients_or_admin_area) || !empty($custom_css_admin_and_clients_area)) {
        echo '<style id="theme_style_custom_css">' . PHP_EOL;
        if (!empty($clients_or_admin_area)) {
            $clients_or_admin_area = clear_textarea_breaks($clients_or_admin_area);
            echo $clients_or_admin_area . PHP_EOL;
        }
        if (!empty($custom_css_admin_and_clients_area)) {
            $custom_css_admin_and_clients_area = clear_textarea_breaks($custom_css_admin_and_clients_area);
            echo $custom_css_admin_and_clients_area . PHP_EOL;
        }
        echo '</style>' . PHP_EOL;
    }
}
/**
 * General and buttons styles
 * @return null
 */
function theme_style_general_and_buttons()
{
    theme_style_render(['general', 'buttons']);
}

/**
 * Init theme style module menu items in setup in admin_init hook
 * @return null
 */
function theme_style_init_menu_items()
{
    if (is_admin()) {
        $CI = &get_instance();
        /**
         * If the logged in user is administrator, add custom menu in Setup
         */
        $CI->app_menu->add_setup_menu_item('theme-style', [
            'href'     => admin_url('theme_style'),
            'name'     => _l('theme_style'),
            'position' => 65,
        ]);
    }
}
