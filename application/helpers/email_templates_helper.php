<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Prepares email template preview $data for the view
 * @param  string $template    template class name
 * @param  mixed $customer_id_or_email customer ID to fetch the primary contact email or email
 * @return array
 */
function prepare_mail_preview_data($template, $customer_id_or_email, $mailClassParams = [])
{
    $CI = &get_instance();

    if (is_numeric($customer_id_or_email)) {
        $contact = $CI->clients_model->get_contact(get_primary_contact_user_id($customer_id_or_email));
        $email   = $contact ? $contact->email : '';
    } else {
        $email = $customer_id_or_email;
    }

    $CI->load->model('emails_model');

    $data['template'] = $CI->app_mail_template->prepare($email, $template);
    $slug             = $CI->app_mail_template->get_default_property_value('slug', $template, $mailClassParams);

    $data['template_name'] = $slug;

    $template_result = $CI->emails_model->get(['slug' => $slug, 'language' => 'english'], 'row');

    $data['template_system_name'] = $template_result->name;
    $data['template_id']          = $template_result->emailtemplateid;

    $data['template_disabled'] = $template_result->active == 0;

    return $data;
}
/**
 * Parse email template with the merge fields
 * @param  mixed $template     template
 * @param  array  $merge_fields
 * @return object
 */
function parse_email_template($template, $merge_fields = [])
{
    $CI = & get_instance();
    if (!is_object($template) || $CI->input->post('template_name')) {
        $original_template = $template;

        if (!class_exists('emails_model', false)) {
            $CI->load->model('emails_model');
        }

        if ($CI->input->post('template_name')) {
            $template = $CI->input->post('template_name');
        }

        $template = $CI->emails_model->get(['slug' => $template], 'row');

        if ($CI->input->post('email_template_custom')) {
            $template->message = $CI->input->post('email_template_custom', false);
            // Replace the subject too
            $template->subject = $original_template->subject;
        }
    }

    $template = parse_email_template_merge_fields($template, $merge_fields);

    // Used in hooks eq for emails tracking
    $template->tmp_id = app_generate_hash();

    return hooks()->apply_filters('email_template_parsed', $template);
}

/**
 * This function will parse email template merge fields and replace with the corresponding merge fields passed before sending email
 * @param  object $template     template from database
 * @param  array $merge_fields available merge fields
 * @return object
 */
function parse_email_template_merge_fields($template, $merge_fields)
{
    $CI = &get_instance();

    if (!class_exists('other_merge_fields', false)) {
        $CI->load->library('merge_fields/other_merge_fields');
    }

    $merge_fields = array_merge($merge_fields, $CI->other_merge_fields->format());

    foreach ($merge_fields as $key => $val) {
        foreach (['message', 'fromname', 'subject'] as $replacer) {
            $template->{$replacer} = stripos($template->{$replacer}, $key) !== false
            ? str_ireplace($key, $val, $template->{$replacer})
            : str_ireplace($key, '', $template->{$replacer});
        }
    }

    return $template;
}

/**
 * Send mail template
 * @since  2.3.0
 * @return mixed
 */
function send_mail_template()
{
    $params = func_get_args();

    return mail_template(...$params)->send();
}

/**
 * Prepare mail template class
 * @param  string $class mail template class name
 * @return mixed
 */
function mail_template($class)
{
    $CI = &get_instance();

    $params = func_get_args();

    // First params is the $class param
    unset($params[0]);

    $params = array_values($params);

    $path = get_mail_template_path($class, $params);

    if (!file_exists($path)) {
        if (!defined('CRON')) {
            show_error('Mail Class Does Not Exists [' . $path . ']');
        } else {
            return false;
        }
    }

    // Include the mailable class
    if (!class_exists($class, false)) {
        include_once($path);
    }

    // Initialize the class and pass the params
    $instance = new $class(...$params);

    // Call the send method
    return $instance;
}

function get_mail_template_path($class, &$params)
{
    $CI  = &get_instance();
    $dir = APPPATH . 'libraries/mails/';

    // Check if second parameter is module and is activated so we can get the class from the module path
    if (isset($params[0]) && is_string($params[0]) && is_dir(module_dir_path($params[0]))) {
        $module = $CI->app_modules->get($params[0]);
        if ($module['activated'] === 1) {
            $dir = module_libs_path($params[0]) . 'mails/';
        }

        unset($params[0]);
        $params = array_values($params);
    }

    return $dir . ucfirst($class) . '.php';
}
/**
 * Create new email template
 * @param  string  $subject the predefined email template subject
 * @param  string  $message the predefined email template message
 * @param  string  $type    for what feature this email template is related e.q. invoice|ticket
 * @param  string  $name    the email template name which user see in Setup->Email Template, this is used for easier email template recognition
 * @param  string  $slug    unique email template slug
 * @param  integer $active  whether by default this email template is active
 * @return mixed
 */
function create_email_template($subject, $message, $type, $name, $slug, $active = 1)
{
    if(total_rows('emailtemplates', ['slug'=>$slug]) > 0) {
        return false;
    }

    $data['subject']   = $subject;
    $data['message']   = $message;
    $data['type']      = $type;
    $data['name']      = $name;
    $data['slug']      = $slug;
    $data['language']  = 'english';
    $data['active']    = $active;
    $data['plaintext'] = 0;
    $CI                = &get_instance();
    $CI->load->model('emails_model');

    return $CI->emails_model->add_template($data);
}
