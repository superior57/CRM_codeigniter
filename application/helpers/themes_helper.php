<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Customers area dedicated theme functions
 */

function add_theme_menu_item($slug, $item)
{
    return get_instance()->app_menu->add_theme_item($slug, $item);
}

function no_index_customers_area()
{
    hooks()->add_action('app_customers_head', '_inject_no_index');
}

function add_default_theme_menu_items()
{
    if (is_knowledge_base_viewable(true)) {
        add_theme_menu_item('knowledge-base', [
            'name'     => _l('clients_nav_kb'),
            'href'     => site_url('knowledge-base'),
            'position' => 5,
        ]);
    }

    if (!is_client_logged_in() && get_option('allow_registration') == 1) {
        add_theme_menu_item('register', [
                'name'     => _l('clients_nav_register'),
                'href'     => site_url('authentication/register'),
                'position' => 99,
            ]);
    }

    if (!is_client_logged_in()) {
        add_theme_menu_item('login', [
                    'name'     => _l('clients_nav_login'),
                    'href'     => site_url('authentication/login'),
                    'position' => 100,
                ]);
    } else {
        if (has_contact_permission('projects')) {
            add_theme_menu_item('projects', [
                    'name'     => _l('clients_nav_projects'),
                    'href'     => site_url('clients/projects'),
                    'position' => 10,
                ]);
        }
        if (has_contact_permission('invoices')) {
            add_theme_menu_item('invoices', [
                    'name'     => _l('clients_nav_invoices'),
                    'href'     => site_url('clients/invoices'),
                    'position' => 15,
                ]);
        }
        if (has_contact_permission('contracts')) {
            add_theme_menu_item('contracts', [
                    'name'     => _l('clients_nav_contracts'),
                    'href'     => site_url('clients/contracts'),
                    'position' => 20,
                ]);
        }
        if (has_contact_permission('estimates')) {
            add_theme_menu_item('estimates', [
                    'name'     => _l('clients_nav_estimates'),
                    'href'     => site_url('clients/estimates'),
                    'position' => 25,
                ]);
        }
        if (has_contact_permission('proposals')) {
            add_theme_menu_item('proposals', [
                    'name'     => _l('clients_nav_proposals'),
                    'href'     => site_url('clients/proposals'),
                    'position' => 30,
                ]);
        }
        if (can_logged_in_contact_view_subscriptions()) {
            add_theme_menu_item('subscriptions', [
                    'name'     => _l('subscriptions'),
                    'href'     => site_url('clients/subscriptions'),
                    'position' => 40,
                ]);
        }
        if (has_contact_permission('support')) {
            add_theme_menu_item('support', [
                    'name'     => _l('clients_nav_support'),
                    'href'     => site_url('clients/tickets'),
                    'position' => 45,
                ]);
        }

        if (is_gdpr() && is_client_logged_in() && get_option('show_gdpr_in_customers_menu') == '1') {
            add_theme_menu_item('gdpr', [
                    'name'     => _l('gdpr_short'),
                    'href'     => site_url('clients/gdpr'),
                    'position' => 50,
                ]);
        }
    }
}

function compile_theme_css()
{
    return app_compile_css(get_instance()->app_css->default_theme_group());
}

function compile_theme_scripts()
{
    return app_compile_scripts(get_instance()->app_scripts->default_theme_group());
}

function theme_head_view()
{
    return isset($GLOBALS['customers_head']) ? $GLOBALS['customers_head'] : '';
}

function theme_footer_view()
{
    return isset($GLOBALS['customers_footer']) ? $GLOBALS['customers_footer'] : '';
}

function theme_template_view()
{
    return isset($GLOBALS['customers_view']) ? $GLOBALS['customers_view'] : '';
}

function app_customers_footer()
{
    /**
     * Registered scripts
     */
    echo compile_theme_scripts();

    /**
     * @deprecated 2.3.0
     * Moved from themes/[THEME]/views/scripts.php
     * Use app_customers_footer hook instead
     */
    do_action_deprecated('customers_after_js_scripts_load', [], '2.3.0', 'app_customers_footer');

    hooks()->do_action('app_customers_footer');
}
/**
 * Customers area head
 * @param  string $language @deprecated 2.3.0
 * @return null
 */
function app_customers_head($language = null)
{
    // $language param is deprecated
    if (is_null($language)) {
        $language = $GLOBALS['language'];
    }

    if (file_exists(FCPATH . 'assets/css/custom.css')) {
        echo '<link href="' . base_url('assets/css/custom.css') . '" rel="stylesheet" type="text/css" id="custom-css">' . PHP_EOL;
    }

    hooks()->do_action('app_customers_head');
}
/**
 * Get current theme assets url
 * @return string Assets url
 */
function theme_assets_url()
{
    return hooks()->apply_filters('customers_theme_assets_url', base_url('assets/themes/' . get_option('clients_default_theme'))) . '/';
}

/**
 * Return active theme asset path
 * @return string
 */
function theme_assets_path()
{
    return hooks()->apply_filters('customers_theme_assets_path', 'assets/themes/' . get_option('clients_default_theme'));
}

/**
 * Terms and conditions URL
 * @return string
 */
function terms_url()
{
    return hooks()->apply_filters('terms_and_condition_url', site_url('terms-and-conditions'));
}
/**
 * Privacy policy URL
 * @return string
 */
function privacy_policy_url()
{
    return hooks()->apply_filters('privacy_policy_url', site_url('privacy-policy'));
}

/**
 * Current theme view part
 * @param  string $name file name
 * @param  array  $data variables passed to view
 */
function get_template_part($name, $data = [], $return = false)
{
    if ($name === '') {
        return '';
    }

    $CI   = & get_instance();
    $path = 'themes/' . get_option('clients_default_theme') . '/' . 'template_parts/';

    if ($return == true) {
        return $CI->load->view($path . $name, $data, true);
    }

    $CI->load->view($path . $name, $data);
}

/**
 * Get all client themes in themes folder
 * @return array
 */
function get_all_client_themes()
{
    return list_folders(APPPATH . 'views/themes/');
}

/**
 * Get active client theme
 * @return mixed
 */
function active_clients_theme()
{
    $CI = & get_instance();

    $theme = get_option('clients_default_theme');

    if ($theme == '') {
        if (is_dir(VIEWPATH . 'themes/perfex')) {
            // In case the default theme still exists, just add it as default to prevent errors on clients area.
            update_option('clients_default_theme', 'perfex');
            $theme = 'perfex';
        } else {
            show_error('Default clients area theme not configured in settings. Access the <a href="' . admin_url('settings?group=clients  ') . '">settings area</a> and set default clients theme.');
        }
    }

    if (!is_dir(VIEWPATH . 'themes/' . $theme)) {
        show_error('Clients area default theme (' . $theme . ') folder does not exists.');
    }

    return $theme;
}
/**
 * Function used in the customers are in head and hook all the necessary data for full app usage
 * @return null
 */
function app_theme_head_hook()
{
    $CI = &get_instance();
    ob_start();
    echo get_custom_fields_hyperlink_js_function();

    if (get_option('use_recaptcha_customers_area') == 1
        && get_option('recaptcha_secret_key') != ''
        && get_option('recaptcha_site_key') != '') {
        echo "<script src='https://www.google.com/recaptcha/api.js'></script>";
    }

    $isRTL = (is_rtl(true) ? 'true' : 'false');

    $locale = get_locale_key($GLOBALS['language']);

    $maxUploadSize = file_upload_max_size();

    $date_format = get_option('dateformat');
    $date_format = explode('|', $date_format);
    $date_format = $date_format[0]; ?>
    <script>
        <?php if (is_staff_logged_in()) {
        ?>
        var admin_url = '<?php echo admin_url(); ?>';
        <?php
    } ?>

        var site_url = '<?php echo site_url(''); ?>',
        app = {},
        cfh_popover_templates  = {};

        app.isRTL = '<?php echo $isRTL; ?>';
        app.is_mobile = '<?php echo is_mobile(); ?>';
        app.months_json = '<?php echo json_encode([_l('January'), _l('February'), _l('March'), _l('April'), _l('May'), _l('June'), _l('July'), _l('August'), _l('September'), _l('October'), _l('November'), _l('December')]); ?>';

        app.browser = "<?php echo strtolower($CI->agent->browser()); ?>";
        app.max_php_ini_upload_size_bytes = "<?php echo $maxUploadSize; ?>";
        app.locale = "<?php echo $locale; ?>";

        app.options = {
            calendar_events_limit: "<?php echo get_option('calendar_events_limit'); ?>",
            calendar_first_day: "<?php echo get_option('calendar_first_day'); ?>",
            tables_pagination_limit: "<?php echo get_option('tables_pagination_limit'); ?>",
            enable_google_picker: "<?php echo get_option('enable_google_picker'); ?>",
            google_client_id: "<?php echo get_option('google_client_id'); ?>",
            google_api: "<?php echo get_option('google_api_key'); ?>",
            default_view_calendar: "<?php echo get_option('default_view_calendar'); ?>",
            timezone: "<?php echo get_option('default_timezone'); ?>",
            allowed_files: "<?php echo get_option('allowed_files'); ?>",
            date_format: "<?php echo $date_format; ?>",
            time_format: "<?php echo get_option('time_format'); ?>",
        };

        app.lang = {
            file_exceeds_maxfile_size_in_form: "<?php echo _l('file_exceeds_maxfile_size_in_form'); ?>" + ' (<?php echo bytesToSize('', $maxUploadSize); ?>)',
            file_exceeds_max_filesize: "<?php echo _l('file_exceeds_max_filesize'); ?>" + ' (<?php echo bytesToSize('', $maxUploadSize); ?>)',
            validation_extension_not_allowed: "<?php echo _l('validation_extension_not_allowed'); ?>",
            sign_document_validation: "<?php echo _l('sign_document_validation'); ?>",
            dt_length_menu_all: "<?php echo _l('dt_length_menu_all'); ?>",
            drop_files_here_to_upload: "<?php echo _l('drop_files_here_to_upload'); ?>",
            browser_not_support_drag_and_drop: "<?php echo _l('browser_not_support_drag_and_drop'); ?>",
            confirm_action_prompt: "<?php echo _l('confirm_action_prompt'); ?>",
            datatables: <?php echo json_encode(get_datatables_language_array()); ?>,
            discussions_lang: <?php echo json_encode(get_project_discussions_language_array()); ?>,
        };
        window.addEventListener('load',function(){
            custom_fields_hyperlink();
        });
    </script>
    <?php

    _do_clients_area_deprecated_js_vars($date_format, $locale, $maxUploadSize, $isRTL);

    $contents = ob_get_contents();
    ob_end_clean();
    echo $contents;
}

function _do_clients_area_deprecated_js_vars($date_format, $locale, $maxUploadSize, $isRTL)
{
    ?>
    <script>
        /**
         * @deprecated 2.3.2
         * Do not use any of these below as will be removed in future updates.
         */
        var isRTL = '<?php echo $isRTL; ?>';

        var calendar_events_limit = "<?php echo get_option('calendar_events_limit'); ?>";
        var maximum_allowed_ticket_attachments = "<?php echo get_option('maximum_allowed_ticket_attachments'); ?>";

        var max_php_ini_upload_size_bytes  = "<?php echo $maxUploadSize; ?>";

        var file_exceeds_maxfile_size_in_form = "<?php echo _l('file_exceeds_maxfile_size_in_form'); ?>" + ' (<?php echo bytesToSize('', $maxUploadSize); ?>)';
        var file_exceeds_max_filesize = "<?php echo _l('file_exceeds_max_filesize'); ?>" + ' (<?php echo bytesToSize('', $maxUploadSize); ?>)';

        var validation_extension_not_allowed = "<?php echo _l('validation_extension_not_allowed'); ?>";
        var sign_document_validation = "<?php echo _l('sign_document_validation'); ?>";
        var dt_length_menu_all = "<?php echo _l('dt_length_menu_all'); ?>";

        var drop_files_here_to_upload = "<?php echo _l('drop_files_here_to_upload'); ?>";
        var browser_not_support_drag_and_drop = "<?php echo _l('browser_not_support_drag_and_drop'); ?>";
        var remove_file = "<?php echo _l('remove_file'); ?>";
        var tables_pagination_limit = "<?php echo get_option('tables_pagination_limit'); ?>";
        var enable_google_picker = "<?php echo get_option('enable_google_picker'); ?>";
        var google_client_id = "<?php echo get_option('google_client_id'); ?>";
        var google_api = "<?php echo get_option('google_api_key'); ?>";
        var acceptable_mimes = "<?php echo get_form_accepted_mimes(); ?>";
        var date_format = "<?php echo $date_format; ?>";
        var time_format = "<?php echo get_option('time_format'); ?>";
        var default_view_calendar = "<?php echo get_option('default_view_calendar'); ?>";
        var dt_lang = <?php echo json_encode(get_datatables_language_array()); ?>;
        var discussions_lang = <?php echo json_encode(get_project_discussions_language_array()); ?>;
        var confirm_action_prompt = "<?php echo _l('confirm_action_prompt'); ?>";
        var cf_translate_input_link_tip = "<?php echo _l('cf_translate_input_link_tip'); ?>";

        var locale = '<?php echo $locale; ?>';
        var timezone = "<?php echo get_option('default_timezone'); ?>";
        var allowed_files = "<?php echo get_option('allowed_files'); ?>";
        var calendar_first_day = '<?php echo get_option('calendar_first_day'); ?>';
        var months_json = '<?php echo json_encode([_l('January'), _l('February'), _l('March'), _l('April'), _l('May'), _l('June'), _l('July'), _l('August'), _l('September'), _l('October'), _l('November'), _l('December')]); ?>';
    </script>
        <?php
}
