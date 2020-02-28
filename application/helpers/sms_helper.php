<?php

defined('BASEPATH') or exit('No direct script access allowed');

hooks()->add_action('admin_init', 'maybe_test_sms_gateway');

function maybe_test_sms_gateway()
{
    $CI = &get_instance();
    if (is_staff_logged_in() && $CI->input->post('sms_gateway_test')) {

        $retval = $CI->{'sms_' . $CI->input->post('id')}->send(
            $CI->input->post('number'),
            clear_textarea_breaks(nl2br($CI->input->post('message')))
        );

        $response = ['success' => false];

        if (isset($GLOBALS['sms_error'])) {
            $response['error'] = $GLOBALS['sms_error'];
        } else {
            $response['success'] = true;
        }

        echo json_encode($response);
        die;
    }
}

hooks()->add_action('admin_init', '_maybe_sms_gateways_settings_group');

function _maybe_sms_gateways_settings_group($groups)
{
    $CI = &get_instance();

    $gateways = $CI->app_sms->get_gateways();

    if (count($gateways) > 0) {
        $CI->app_tabs->add_settings_tab('sms', [
            'name'     => 'SMS',
            'view'     => 'admin/settings/includes/sms',
            'position' => 60,
        ]);
    }
}

hooks()->add_action('app_init', 'app_init_sms_gateways');

function app_init_sms_gateways()
{
    $CI = &get_instance();

    $gateways = [
        'sms/sms_clickatell',
        'sms/sms_msg91',
        'sms/sms_twilio',
    ];

    $gateways = hooks()->apply_filters('sms_gateways', $gateways);

    foreach ($gateways as $gateway) {
        $CI->load->library($gateway);
    }
}

function is_sms_trigger_active($trigger = '')
{
    $CI     = &get_instance();
    $active = $CI->app_sms->get_active_gateway();

    if (!$active) {
        return false;
    }

    return $CI->app_sms->is_trigger_active($trigger);
}

function can_send_sms_based_on_creation_date($data_date_created)
{
    $now       = time();
    $your_date = strtotime($data_date_created);
    $datediff  = $now - $your_date;

    $days_diff = floor($datediff / (60 * 60 * 24));

    return $days_diff < DO_NOT_SEND_SMS_ON_DATA_OLDER_THEN || $days_diff == DO_NOT_SEND_SMS_ON_DATA_OLDER_THEN;
}
