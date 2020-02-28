<?php

defined('BASEPATH') or exit('No direct script access allowed');

hooks()->add_action('admin_auth_init', 'init_admin_auth_assets');
hooks()->add_action('app_admin_assets', '_init_admin_assets');

function init_admin_assets()
{
    hooks()->do_action('app_admin_assets');
}

function init_customers_area_assets()
{
    // Used by themes to add assets
    hooks()->do_action('app_client_assets');

    hooks()->do_action('app_client_assets_added');
}

function init_admin_auth_assets()
{
    $CI        = &get_instance();
    $groupName = 'admin-auth';
    add_favicon_link_asset($groupName);
    $CI->app_css->add('reset-css', 'assets/css/reset.min.css', $groupName);
    $CI->app_css->add('bootstrap-css', 'assets/plugins/bootstrap/css/bootstrap.min.css', $groupName);
    if (is_rtl()) {
        $CI->app_css->add('bootstrap-rtl-css', 'assets/plugins/bootstrap-arabic/css/bootstrap-arabic.min.css', $groupName);
    }
    $CI->app_css->add('roboto-css', 'assets/plugins/roboto/roboto.css', $groupName);
    $CI->app_css->add('bootstrap-overrides', 'assets/css/bs-overides.min.css', $groupName);
}

function _init_admin_assets()
{
    $CI          = &get_instance();
    $locale      = $GLOBALS['locale'];
    $localeUpper = strtoupper($locale);

    // Javascript
    $CI->app_scripts->add('vendor-js', 'assets/builds/vendor-admin.js');

    $CI->app_scripts->add('jquery-migrate-js', 'assets/plugins/jquery/jquery-migrate.' . (ENVIRONMENT === 'production' ? 'min.' : '') . 'js');

    add_datatables_js_assets();
    add_moment_js_assets();
    add_bootstrap_select_js_assets();

    $CI->app_scripts->add('tinymce-js', 'assets/plugins/tinymce/tinymce.min.js');

    add_jquery_validation_js_assets();

    if (get_option('pusher_realtime_notifications') == 1) {
        $CI->app_scripts->add('pusher-js', 'https://js.pusher.com/5.0.1/pusher.min.js');
    }

    add_dropbox_js_assets();
    add_google_api_js_assets();

    $CI->app_scripts->add('common-js', 'assets/builds/common.js');

    $CI->app_scripts->add(
        'app-js',
        base_url($CI->app_scripts->core_file('assets/js', 'main.js')) . '?v=' . $CI->app_css->core_version(),
        'admin',
        ['vendor-js', 'datatables-js', 'bootstrap-select-js', 'tinymce-js', 'jquery-migrate-js', 'jquery-validation-js', 'moment-js', 'common-js']
    );

    // CSS
    add_favicon_link_asset();

    $CI->app_css->add('reset-css', 'assets/css/reset.min.css');
    $CI->app_css->add('roboto-css', 'assets/plugins/roboto/roboto.css');
    $CI->app_css->add('vendor-css', 'assets/builds/vendor-admin.css');

    if (is_rtl()) {
        $CI->app_css->add('bootstrap-rtl-css', 'assets/plugins/bootstrap-arabic/css/bootstrap-arabic.min.css');
    }

    $CI->app_css->add('app-css', base_url($CI->app_css->core_file('assets/css', 'style.css')) . '?v=' . $CI->app_css->core_version());

    if (file_exists(FCPATH . 'assets/css/custom.css')) {
        $CI->app_css->add('custom-css', base_url('assets/css/custom.css'), 'admin', ['app-css']);
    }

    hooks()->do_action('app_admin_assets_added');
}


function add_calendar_assets($group = 'admin', $tryGcal = true)
{

    $locale = $GLOBALS['locale'];
    $CI     = &get_instance();

    $CI->app_scripts->add('full-calendar-js', 'assets/plugins/fullcalendar/fullcalendar.min.js', $group);

    if ($tryGcal && get_option('google_api_key') != '') {
        $CI->app_scripts->add('full-calendar-gcal-js', 'assets/plugins/fullcalendar/gcal.min.js', $group);
    }

    if ($locale != 'en' && file_exists(FCPATH . 'assets/plugins/fullcalendar/locale/' . $locale . '.js')) {
        $CI->app_scripts->add('full-calendar-lang-js', 'assets/plugins/fullcalendar/locale/' . $locale . '.js', $group);
    }

    $CI->app_css->add('full-calendar-css', 'assets/plugins/fullcalendar/fullcalendar.min.css', $group);
}

function add_moment_js_assets($group = 'admin')
{
    get_instance()->app_scripts->add('moment-js', 'assets/builds/moment.min.js', $group);
}

function add_favicon_link_asset($group = 'admin')
{
    $favIcon = get_option('favicon');
    if ($favIcon != '') {
        get_instance()->app_css->add('favicon', [
        'path'       => 'uploads/company/' . $favIcon,
        'version'    => false,
        'attributes' => [
            'rel'  => 'shortcut icon',
            'type' => false,
        ],
        ], $group);
    }
}

function add_jquery_validation_js_assets($group = 'admin')
{
    $CI          = &get_instance();
    $locale      = $GLOBALS['locale'];
    $localeUpper = strtoupper($locale);

    $jqValidationBase = 'assets/plugins/jquery-validation/';
    $CI->app_scripts->add('jquery-validation-js', $jqValidationBase . 'jquery.validate.min.js', $group);

    if ($locale != 'en') {
        if (file_exists(FCPATH . $jqValidationBase . 'localization/messages_' . $locale . '.min.js')) {
            $CI->app_scripts->add('jquery-validation-lang-js', $jqValidationBase . 'localization/messages_' . $locale . '.min.js', $group);
        } elseif (file_exists(FCPATH . $jqValidationBase . 'localization/messages_' . $locale . '_' . $localeUpper . '.min.js')) {
            $CI->app_scripts->add('jquery-validation-lang-js', $jqValidationBase . 'localization/messages_' . $locale . '_' . $localeUpper . '.min.js', $group);
        }
    }
}

function add_bootstrap_select_js_assets($group = 'admin')
{
    $CI           = &get_instance();
    $locale       = $GLOBALS['locale'];
    $localeUpper  = strtoupper($locale);
    $bsSelectBase = 'assets/plugins/bootstrap-select/js/';
    $CI->app_scripts->add('bootstrap-select-js', 'assets/builds/bootstrap-select.min.js', $group);

    if ($locale != 'en') {
        if (file_exists(FCPATH . $bsSelectBase . 'i18n/defaults-' . $locale . '.min.js')) {
            $CI->app_scripts->add('bootstrap-select-lang-js', $bsSelectBase . 'i18n/defaults-' . $locale . '.min.js', $group);
        } elseif (file_exists(FCPATH . $bsSelectBase . 'i18n/defaults-' . $locale . '_' . $localeUpper . '.min.js')) {
            $CI->app_scripts->add('bootstrap-select-lang-js', $bsSelectBase . 'i18n/defaults-' . $locale . '_' . $localeUpper . '.min.js', $group);
        }
    }
}

function add_dropbox_js_assets($group = 'admin')
{
    if (get_option('dropbox_app_key') != '') {
        get_instance()->app_scripts->add('dropboxjs', [
            'path'       => 'https://www.dropbox.com/static/api/2/dropins.js',
            'attributes' => [
                'data-app-key' => get_option('dropbox_app_key'),
            ],
        ], $group);
    }
}

function add_google_api_js_assets($group = 'admin')
{
    if (get_option('enable_google_picker') == '1') {
        get_instance()->app_scripts->add('google-js', [
            'path'       => 'https://apis.google.com/js/api.js?onload=onGoogleApiLoad',
            'attributes' => [
                'defer',
            ],
        ], $group);
    }
}


function add_admin_tickets_js_assets()
{
    $CI = &get_instance();
    $CI->app_scripts->add(
        'tickets-js',
        base_url($CI->app_scripts->core_file('assets/js', 'tickets.js')) . '?v=' . $CI->app_scripts->core_version(),
        'admin',
        ['app-js']
    );
}

function add_datatables_js_assets($group = 'admin')
{
    get_instance()->app_scripts->add('datatables-js', 'assets/plugins/datatables/datatables.min.js', $group);
}

function app_compile_css($group = 'admin')
{
    return get_instance()->app_css->compile($group);
}

function app_compile_scripts($group = 'admin')
{
    return get_instance()->app_scripts->compile($group);
}
