<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Required app theme head hook
 */
hooks()->add_action('app_customers_head', 'app_theme_head_hook');

/**
 * Default theme menu items
 * In most cases you will want to add this hook because of all the features
 */
hooks()->add_action('clients_init', 'add_default_theme_menu_items');

register_theme_assets_hook('theme_assets');

function theme_assets()
{
    $CI = &get_instance();

    $groupName = $CI->app_scripts->default_theme_group();

    $CI->app_scripts->theme('bootstrap-js', 'assets/plugins/bootstrap/js/bootstrap.min.js');

    add_datatables_js_assets($groupName);
    add_jquery_validation_js_assets($groupName);
    add_bootstrap_select_js_assets($groupName);

    $CI->app_scripts->theme('datetimepicker-js', 'assets/plugins/datetimepicker/jquery.datetimepicker.full.min.js');
    $CI->app_scripts->theme('chart-js', 'assets/plugins/Chart.js/Chart.min.js');
    $CI->app_scripts->theme('colorpicker-js', 'assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js');
    $CI->app_scripts->theme('lightbox-js', 'assets/plugins/lightbox/js/lightbox.min.js');

    if (is_client_logged_in()) {

        $CI->app_scripts->theme('dropzone-js', 'assets/plugins/dropzone/min/dropzone.min.js');
        $CI->app_scripts->theme('circle-progress-js', 'assets/plugins/jquery-circle-progress/circle-progress.min.js');

        $CI->app_scripts->theme('jquery-comments-js', 'assets/plugins/jquery-comments/js/jquery-comments.min.js');
        $CI->app_scripts->theme('jquery-gantt-js', 'assets/plugins/gantt/js/jquery.fn.gantt.min.js');
        add_moment_js_assets($groupName);
        add_dropbox_js_assets($groupName);

        $CI->app_css->theme('jquery-comments-css', 'assets/plugins/jquery-comments/css/jquery-comments.css');
        $CI->app_css->theme('jquery-gantt-css', 'assets/plugins/gantt/css/style.css');
        add_calendar_assets($groupName, false);

        if (get_option('enable_google_picker') == '1') {
            add_google_api_js_assets($groupName);
        }
    }

    $CI->app_scripts->theme('common-js', 'assets/builds/common.js');

    $CI->app_scripts->theme(
        'theme-global-js',
        base_url($CI->app_scripts->core_file(theme_assets_path() . '/js', 'global.js')) . '?v=' . $CI->app_css->core_version(),
        ['common-js']
    );

    if (is_client_logged_in()) {
        $CI->app_scripts->theme(
            'theme-js',
            base_url($CI->app_scripts->core_file(theme_assets_path() . '/js', 'clients.js')) . '?v=' . $CI->app_css->core_version(),
             ['common-js']
        );
    }

    // CSS
    add_favicon_link_asset($groupName);

    $CI->app_css->theme(
        'reset-css',
        base_url($CI->app_css->core_file('assets/css', 'reset.css')) . '?v=' . $CI->app_css->core_version()
    );

    $CI->app_css->theme('bootstrap-css', 'assets/plugins/bootstrap/css/bootstrap.min.css');
    $CI->app_css->theme('roboto-css', 'assets/plugins/roboto/roboto.css');

    if (is_rtl()) {
        $CI->app_css->theme('bootstrap-rtl-css', 'assets/plugins/bootstrap-arabic/css/bootstrap-arabic.min.css');
    }

    $CI->app_css->theme('datatables-css', 'assets/plugins/datatables/datatables.min.css');
    $CI->app_css->theme('fontawesome-css', 'assets/plugins/font-awesome/css/font-awesome.min.css');
    $CI->app_css->theme('datetimepicker-css', 'assets/plugins/datetimepicker/jquery.datetimepicker.min.css');
    $CI->app_css->theme('bootstrap-select-css', 'assets/plugins/bootstrap-select/css/bootstrap-select.min.css');

    if (is_client_logged_in()) {
        $CI->app_css->theme('dropzone-basic-css', 'assets/plugins/dropzone/min/basic.min.css');
        $CI->app_css->theme('dropzone-css', 'assets/plugins/dropzone/min/dropzone.min.css');
    }

    $CI->app_css->theme('lightbox-css', 'assets/plugins/lightbox/css/lightbox.min.css');
    $CI->app_css->theme('colorpicker-css', 'assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css');

    $CI->app_css->theme(
        'bootstrap-overrides-css',
        base_url($CI->app_scripts->core_file('assets/css', 'bs-overides.css')) . '?v=' . $CI->app_css->core_version()
    );

    $CI->app_css->theme(
        'theme-css',
        base_url($CI->app_scripts->core_file(theme_assets_path() . '/css', 'style.css')) . '?v=' . $CI->app_css->core_version()
    );
}
