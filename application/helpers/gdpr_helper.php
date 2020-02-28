<?php

defined('BASEPATH') or exit('No direct script access allowed');

function send_gdpr_email_template($template, $user_id)
{
    $CI = &get_instance();
    $CI->load->model('staff_model');

    $staff = $CI->staff_model->get('', ['active' => 1, 'admin' => 1]);

    foreach ($staff as $member) {
        send_mail_template($template, $member, $user_id);
    }
}

function is_gdpr()
{
    return get_option('enable_gdpr') === '1';
}
