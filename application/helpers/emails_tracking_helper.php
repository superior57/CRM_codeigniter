<?php

defined('BASEPATH') or exit('No direct script access allowed');

hooks()->add_filter('after_parse_email_template_message', 'email_tracking_inject_in_body');

function email_tracking_inject_in_body($template)
{
    $CI = &get_instance();
    if (in_array($template->slug, get_available_tracking_templates_slugs())) {
        $template->message .= '<img src="' . site_url('check_emails/track/' . $template->tmp_id) . '" alt="" width="1" height="1" border="0">';
        $template->has_tracking = true;
    }

    return $template;
}

hooks()->add_action('email_template_sent', 'add_email_tracking');

function add_email_tracking($data)
{
    $CI = &get_instance();

    if (in_array($data['template']->slug, get_available_tracking_templates_slugs())
        && isset($data['template']->has_tracking)
        && $data['template']->has_tracking
    ) {
        $CI->db->insert(db_prefix().'tracked_mails', [
            'uid'      => $data['template']->tmp_id,
            'subject'  => $data['template']->subject,
            'rel_id'   => $GLOBALS['SENDING_EMAIL_TEMPLATE_CLASS']->get_rel_id(),
            'rel_type' => $GLOBALS['SENDING_EMAIL_TEMPLATE_CLASS']->get_rel_type(),
            'date'     => date('Y-m-d H:i:s'),
            'email'    => $data['email'],
        ]);
    }
}

function get_tracked_emails($rel_id, $rel_type)
{
    $CI = &get_instance();
    $CI->db->where('rel_id', $rel_id);
    $CI->db->where('rel_type', $rel_type);
    $CI->db->order_by('date', 'desc');

    return $CI->db->get(db_prefix().'tracked_mails')->result_array();
}

function delete_tracked_emails($rel_id, $rel_type)
{
    $CI = &get_instance();
    $CI->db->where('rel_id', $rel_id);
    $CI->db->where('rel_type', $rel_type);
    $CI->db->delete(db_prefix().'tracked_mails');
}

function get_available_tracking_templates_slugs()
{
    $slugs = [
        'invoice-send-to-client',
        'invoice-already-send',
        'invoice-overdue-notice',
        'estimate-send-to-client',
        'estimate-already-send',
        'estimate-expiry-reminder',
        'proposal-send-to-customer',
        'proposal-expiry-reminder',
        'proposal-comment-to-client',
        'credit-note-send-to-client',
        'send-contract',
        'send-subscription',
        'subscription-payment-failed',
    ];

    return hooks()->apply_filters('available_tracking_templates', $slugs);
}
