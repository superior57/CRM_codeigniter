<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Filters
 */
hooks()->add_filter('check_vault_entries_visibility', '_check_vault_entries_visibility');
hooks()->add_filter('register_merge_fields', 'core_merge_fields');

/*
Actions
 */

hooks()->add_action('non_existent_user_login_attempt', '_maybe_user_is_trying_to_login_into_the_clients_area_as_staff');
hooks()->add_action('clients_login_form_start', '_maybe_mistaken_login_area_check_performed');

hooks()->add_action('new_ticket_admin_page_loaded', 'ticket_message_save_as_predefined_reply_javascript');
hooks()->add_action('ticket_admin_single_page_loaded', 'ticket_message_save_as_predefined_reply_javascript');

hooks()->add_action('database_updated', 'app_set_update_message_info');
hooks()->add_action('before_update_database', 'app_set_pipe_php_permissions');
hooks()->add_action('admin_init', 'app_init_admin_sidebar_menu_items');
hooks()->add_action('admin_init', 'app_init_customer_profile_tabs');
hooks()->add_action('admin_init', 'app_init_project_tabs');
hooks()->add_action('admin_init', 'app_init_settings_tabs');

if (defined('APP_CSRF_PROTECTION') && APP_CSRF_PROTECTION) {
    hooks()->add_action('app_admin_head', 'csrf_jquery_token');
    hooks()->add_action('app_customers_head', 'csrf_jquery_token');
    hooks()->add_action('app_external_form_head', 'csrf_jquery_token');
    hooks()->add_action('elfinder_tinymce_head', 'csrf_jquery_token');
}

/**
 * Register core merge fields builder classes
 * This function is used by filter in core_hooks_helper.php
 * @param  array $fields current registered fields
 * @return array
 */
function core_merge_fields($fields)
{
    $fields[] = 'merge_fields/staff_merge_fields';
    $fields[] = 'merge_fields/client_merge_fields';
    $fields[] = 'merge_fields/credit_note_merge_fields';
    $fields[] = 'merge_fields/subscriptions_merge_fields';
    $fields[] = 'merge_fields/ticket_merge_fields';
    $fields[] = 'merge_fields/contract_merge_fields';
    $fields[] = 'merge_fields/invoice_merge_fields';
    $fields[] = 'merge_fields/estimate_merge_fields';
    $fields[] = 'merge_fields/tasks_merge_fields';
    $fields[] = 'merge_fields/proposals_merge_fields';
    $fields[] = 'merge_fields/leads_merge_fields';
    $fields[] = 'merge_fields/projects_merge_fields';
    $fields[] = 'merge_fields/event_merge_fields';
    $fields[] = 'merge_fields/other_merge_fields';

    return $fields;
}
