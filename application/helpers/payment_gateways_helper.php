<?php

defined('BASEPATH') or exit('No direct script access allowed');
define('PAYMENT_GATEWAYS_ASSETS_GROUP', 'payment-gateways');

/**
 * Payment gateway logo, user can apply hook to change the logo
 * @return string
 */
function payment_gateway_logo()
{
    $url    = payment_gateway_logo_url();
    $width  = hooks()->apply_filters('payment_gateway_logo_width', 'auto');
    $height = hooks()->apply_filters('payment_gateway_logo_height', '34px');

    return '<img src="' . $url . '" width="' . $width . '" height="' . $height . '">';
}

function payment_gateway_logo_url()
{
    $logoURL = '';

    $logoDark  = get_option('company_logo_dark');
    $logoLight = get_option('company_logo');

    if ($logoDark != '' && file_exists(get_upload_path_by_type('company') . $logoDark)) {
        $logoURL = base_url('uploads/company/' . $logoDark);
    } elseif ($logoLight != '' && file_exists(get_upload_path_by_type('company') . $logoLight)) {
        $logoURL = base_url('uploads/company/' . $logoLight);
    }

    $logoURL = hooks()->apply_filters('payment_gateway_logo_url', $logoURL);

    return $logoURL;
}

/**
 * Outputs payment gateway head, commonly used
 * @param  string $title Document title
 * @return mixed
 */
function payment_gateway_head($title = 'Payment for Invoice')
{
    $CI = &get_instance();

    add_favicon_link_asset(PAYMENT_GATEWAYS_ASSETS_GROUP);

    $CI->app_css->add(
        'reset-css',
        base_url($CI->app_css->core_file('assets/css', 'reset.css')) . '?v=' . $CI->app_css->core_version(),
        PAYMENT_GATEWAYS_ASSETS_GROUP
    );

    $CI->app_css->add('bootstrap-css', 'assets/plugins/bootstrap/css/bootstrap.min.css', PAYMENT_GATEWAYS_ASSETS_GROUP);
    $CI->app_css->add('roboto-css', 'assets/plugins/roboto/roboto.css', PAYMENT_GATEWAYS_ASSETS_GROUP);

    $CI->app_css->add(
        'bootstrap-overrides-css',
        base_url($CI->app_scripts->core_file('assets/css', 'bs-overides.css')) . '?v=' . $CI->app_css->core_version(),
        PAYMENT_GATEWAYS_ASSETS_GROUP
    );

    $CI->app_css->add(
        'theme-css',
        base_url($CI->app_scripts->core_file(theme_assets_path() . '/css', 'style.css')) . '?v=' . $CI->app_css->core_version(),
        PAYMENT_GATEWAYS_ASSETS_GROUP
    );

    $html = '<!DOCTYPE html>' . PHP_EOL;
    $html .= '<html lang="en">' . PHP_EOL;
    $html .= '<head>' . PHP_EOL;
    $html .= '<meta charset="utf-8">' . PHP_EOL;
    $html .= '<meta http-equiv="X-UA-Compatible" content="IE=edge" />' . PHP_EOL;
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1">' . PHP_EOL;
    $html .= '<title>' . PHP_EOL;
    $html .= $title . PHP_EOL;
    $html .= '</title>' . PHP_EOL;

    $html .= app_compile_css(PAYMENT_GATEWAYS_ASSETS_GROUP) . PHP_EOL;

    $html .= '<style>' . PHP_EOL;
    $html .= '.text-danger { color: #fc2d42; }' . PHP_EOL;
    $html .= '</style>' . PHP_EOL;
    $html .= hooks()->apply_filters('payment_gateway_head', '') . PHP_EOL;
    $html .= '</head>' . PHP_EOL;

    return $html;
}

/**
 * Used in payment gateways head, commonly used sscripts
 * @return html
 */
function payment_gateway_scripts()
{
    $html = '<script src="' . base_url() . 'assets/plugins/jquery/jquery.min.js"></script>' . PHP_EOL;
    $html .= '<script src="' . base_url() . 'assets/plugins/bootstrap/js/bootstrap.min.js"></script>' . PHP_EOL;
    $html .= '<script src="' . base_url() . 'assets/plugins/jquery-validation/jquery.validate.min.js"></script>' . PHP_EOL;

    $html .= '<script>
        $.validator.setDefaults({
            errorElement: \'span\',
            errorClass: \'text-danger\',
        });
        </script>' . PHP_EOL;

    $html .= hooks()->apply_filters('payment_gateway_scripts', '') . PHP_EOL;

    return $html;
}
/**
 * Used in payment gateways document footer
 * @return html
 */
function payment_gateway_footer()
{
    $html = hooks()->apply_filters('payment_gateway_footer', '') . PHP_EOL;
    $html .= '</body>' . PHP_EOL;
    $html .= '</html>' . PHP_EOL;

    return $html;
}
