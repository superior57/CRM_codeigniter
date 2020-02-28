<?php

defined('BASEPATH') or exit('No direct script access allowed');

function app_init_admin_sidebar_menu_items()
{
    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('dashboard', [
        'name'     => _l('als_dashboard'),
        'href'     => admin_url(),
        'position' => 1,
        'icon'     => 'fa fa-home',
    ]);

    if (has_permission('customers', '', 'view')
        || (have_assigned_customers()
        || (!have_assigned_customers() && has_permission('customers', '', 'create')))) {
        $CI->app_menu->add_sidebar_menu_item('customers', [
            'name'     => _l('als_clients'),
            'href'     => admin_url('clients'),
            'position' => 5,
            'icon'     => 'fa fa-user-o',
        ]);
    }

    $CI->app_menu->add_sidebar_menu_item('sales', [
            'collapse' => true,
            'name'     => _l('als_sales'),
            'position' => 10,
            'icon'     => 'fa fa-balance-scale',
        ]);

    if ((has_permission('proposals', '', 'view') || has_permission('proposals', '', 'view_own'))
        || (staff_has_assigned_proposals() && get_option('allow_staff_view_proposals_assigned') == 1)) {
        $CI->app_menu->add_sidebar_children_item('sales', [
                'slug'     => 'proposals',
                'name'     => _l('proposals'),
                'href'     => admin_url('proposals'),
                'position' => 5,
        ]);
    }

    if ((has_permission('estimates', '', 'view') || has_permission('estimates', '', 'view_own'))
        || (staff_has_assigned_estimates() && get_option('allow_staff_view_estimates_assigned') == 1)) {
        $CI->app_menu->add_sidebar_children_item('sales', [
                'slug'     => 'estimates',
                'name'     => _l('estimates'),
                'href'     => admin_url('estimates'),
                'position' => 10,
        ]);
    }

    if ((has_permission('invoices', '', 'view') || has_permission('invoices', '', 'view_own'))
         || (staff_has_assigned_invoices() && get_option('allow_staff_view_invoices_assigned') == 1)) {
        $CI->app_menu->add_sidebar_children_item('sales', [
                'slug'     => 'invoices',
                'name'     => _l('invoices'),
                'href'     => admin_url('invoices'),
                'position' => 15,
        ]);
    }

    if (has_permission('payments', '', 'view') || has_permission('invoices', '', 'view_own')
           || (get_option('allow_staff_view_invoices_assigned') == 1 && staff_has_assigned_invoices())) {
        $CI->app_menu->add_sidebar_children_item('sales', [
                'slug'     => 'payments',
                'name'     => _l('payments'),
                'href'     => admin_url('payments'),
                'position' => 20,
        ]);
    }

    if (has_permission('credit_notes', '', 'view') || has_permission('credit_notes', '', 'view_own')) {
        $CI->app_menu->add_sidebar_children_item('sales', [
                'slug'     => 'credit_notes',
                'name'     => _l('credit_notes'),
                'href'     => admin_url('credit_notes'),
                'position' => 25,
        ]);
    }

    if (has_permission('items', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('sales', [
                'slug'     => 'items',
                'name'     => _l('items'),
                'href'     => admin_url('invoice_items'),
                'position' => 30,
        ]);
    }

    if (has_permission('subscriptions', '', 'view') || has_permission('subscriptions', '', 'view_own')) {
        $CI->app_menu->add_sidebar_menu_item('subscriptions', [
                'name'     => _l('subscriptions'),
                'href'     => admin_url('subscriptions'),
                'icon'     => 'fa fa-repeat',
                'position' => 15,
        ]);
    }

    if (has_permission('expenses', '', 'view') || has_permission('expenses', '', 'view_own')) {
        $CI->app_menu->add_sidebar_menu_item('expenses', [
                'name'     => _l('expenses'),
                'href'     => admin_url('expenses'),
                'icon'     => 'fa fa-file-text-o',
                'position' => 20,
        ]);
    }

    if (has_permission('contracts', '', 'view') || has_permission('contracts', '', 'view_own')) {
        $CI->app_menu->add_sidebar_menu_item('contracts', [
                'name'     => _l('contracts'),
                'href'     => admin_url('contracts'),
                'icon'     => 'fa fa-file',
                'position' => 25,
        ]);
    }

    $CI->app_menu->add_sidebar_menu_item('projects', [
                'name'     => _l('projects'),
                'href'     => admin_url('projects'),
                'icon'     => 'fa fa-bars',
                'position' => 30,
        ]);

    $CI->app_menu->add_sidebar_menu_item('tasks', [
                'name'     => _l('als_tasks'),
                'href'     => admin_url('tasks'),
                'icon'     => 'fa fa-tasks',
                'position' => 35,
        ]);

    if ((!is_staff_member() && get_option('access_tickets_to_none_staff_members') == 1) || is_staff_member()) {
        $CI->app_menu->add_sidebar_menu_item('support', [
                'name'     => _l('support'),
                'href'     => admin_url('tickets'),
                'icon'     => 'fa fa-ticket',
                'position' => 40,
        ]);
    }

    if (is_staff_member()) {
        $CI->app_menu->add_sidebar_menu_item('leads', [
                'name'     => _l('als_leads'),
                'href'     => admin_url('leads'),
                'icon'     => 'fa fa-tty',
                'position' => 45,
        ]);
    }

    if (has_permission('knowledge_base', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('knowledge-base', [
                'name'     => _l('als_kb'),
                'href'     => admin_url('knowledge_base'),
                'icon'     => 'fa fa-folder-open-o',
                'position' => 50,
        ]);
    }

    // Utilities
    $CI->app_menu->add_sidebar_menu_item('utilities', [
            'collapse' => true,
            'name'     => _l('als_utilities'),
            'position' => 55,
            'icon'     => 'fa fa-cogs',
        ]);

    $CI->app_menu->add_sidebar_children_item('utilities', [
                'slug'     => 'media',
                'name'     => _l('als_media'),
                'href'     => admin_url('utilities/media'),
                'position' => 5,
        ]);

    if (has_permission('bulk_pdf_exporter', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('utilities', [
                'slug'     => 'bulk-pdf-exporter',
                'name'     => _l('bulk_pdf_exporter'),
                'href'     => admin_url('utilities/bulk_pdf_exporter'),
                'position' => 10,
        ]);
    }

    $CI->app_menu->add_sidebar_children_item('utilities', [
                'slug'     => 'calendar',
                'name'     => _l('als_calendar_submenu'),
                'href'     => admin_url('utilities/calendar'),
                'position' => 15,
        ]);


    if (is_admin()) {
        $CI->app_menu->add_sidebar_children_item('utilities', [
                'slug'     => 'announcements',
                'name'     => _l('als_announcements_submenu'),
                'href'     => admin_url('announcements'),
                'position' => 20,
        ]);

        $CI->app_menu->add_sidebar_children_item('utilities', [
                'slug'     => 'activity-log',
                'name'     => _l('als_activity_log_submenu'),
                'href'     => admin_url('utilities/activity_log'),
                'position' => 25,
        ]);

        $CI->app_menu->add_sidebar_children_item('utilities', [
                'slug'     => 'ticket-pipe-log',
                'name'     => _l('ticket_pipe_log'),
                'href'     => admin_url('utilities/pipe_log'),
                'position' => 30,
        ]);
    }

    if (has_permission('reports', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('reports', [
                'collapse' => true,
                'name'     => _l('als_reports'),
                'href'     => admin_url('reports'),
                'icon'     => 'fa fa-area-chart',
                'position' => 60,
        ]);
        $CI->app_menu->add_sidebar_children_item('reports', [
                'slug'     => 'sales-reports',
                'name'     => _l('als_reports_sales_submenu'),
                'href'     => admin_url('reports/sales'),
                'position' => 5,
        ]);
        $CI->app_menu->add_sidebar_children_item('reports', [
                'slug'     => 'expenses-reports',
                'name'     => _l('als_reports_expenses'),
                'href'     => admin_url('reports/expenses'),
                'position' => 10,
        ]);
        $CI->app_menu->add_sidebar_children_item('reports', [
                'slug'     => 'expenses-vs-income-reports',
                'name'     => _l('als_expenses_vs_income'),
                'href'     => admin_url('reports/expenses_vs_income'),
                'position' => 15,
        ]);
        $CI->app_menu->add_sidebar_children_item('reports', [
                'slug'     => 'leads-reports',
                'name'     => _l('als_reports_leads_submenu'),
                'href'     => admin_url('reports/leads'),
                'position' => 20,
        ]);

        if (is_admin()) {
            $CI->app_menu->add_sidebar_children_item('reports', [
                    'slug'     => 'timesheets-reports',
                    'name'     => _l('timesheets_overview'),
                    'href'     => admin_url('staff/timesheets?view=all'),
                    'position' => 25,
            ]);
        }

        $CI->app_menu->add_sidebar_children_item('reports', [
                    'slug'     => 'knowledge-base-reports',
                    'name'     => _l('als_kb_articles_submenu'),
                    'href'     => admin_url('reports/knowledge_base_articles'),
                    'position' => 30,
            ]);
    }

    // Setup menu
    if (has_permission('staff', '', 'view')) {
        $CI->app_menu->add_setup_menu_item('staff', [
                    'name'     => _l('als_staff'),
                    'href'     => admin_url('staff'),
                    'position' => 5,
            ]);
    }

    if (is_admin()) {
        $CI->app_menu->add_setup_menu_item('customers', [
                    'collapse' => true,
                    'name'     => _l('clients'),
                    'position' => 10,
            ]);

        $CI->app_menu->add_setup_children_item('customers', [
                    'slug'     => 'customer-groups',
                    'name'     => _l('customer_groups'),
                    'href'     => admin_url('clients/groups'),
                    'position' => 5,
            ]);
        $CI->app_menu->add_setup_menu_item('support', [
                    'collapse' => true,
                    'name'     => _l('support'),
                    'position' => 15,
            ]);

        $CI->app_menu->add_setup_children_item('support', [
                    'slug'     => 'departments',
                    'name'     => _l('acs_departments'),
                    'href'     => admin_url('departments'),
                    'position' => 5,
            ]);
        $CI->app_menu->add_setup_children_item('support', [
                    'slug'     => 'tickets-predefined-replies',
                    'name'     => _l('acs_ticket_predefined_replies_submenu'),
                    'href'     => admin_url('tickets/predefined_replies'),
                    'position' => 10,
            ]);
        $CI->app_menu->add_setup_children_item('support', [
                    'slug'     => 'tickets-priorities',
                    'name'     => _l('acs_ticket_priority_submenu'),
                    'href'     => admin_url('tickets/priorities'),
                    'position' => 15,
            ]);
        $CI->app_menu->add_setup_children_item('support', [
                    'slug'     => 'tickets-statuses',
                    'name'     => _l('acs_ticket_statuses_submenu'),
                    'href'     => admin_url('tickets/statuses'),
                    'position' => 20,
            ]);

        $CI->app_menu->add_setup_children_item('support', [
                    'slug'     => 'tickets-services',
                    'name'     => _l('acs_ticket_services_submenu'),
                    'href'     => admin_url('tickets/services'),
                    'position' => 25,
            ]);
        $CI->app_menu->add_setup_children_item('support', [
                    'slug'     => 'tickets-spam-filters',
                    'name'     => _l('spam_filters'),
                    'href'     => admin_url('spam_filters/view/tickets'),
                    'position' => 30,
            ]);

        $CI->app_menu->add_setup_menu_item('leads', [
                    'collapse' => true,
                    'name'     => _l('acs_leads'),
                    'position' => 20,
            ]);
        $CI->app_menu->add_setup_children_item('leads', [
                    'slug'     => 'leads-sources',
                    'name'     => _l('acs_leads_sources_submenu'),
                    'href'     => admin_url('leads/sources'),
                    'position' => 5,
            ]);
        $CI->app_menu->add_setup_children_item('leads', [
                    'slug'     => 'leads-statuses',
                    'name'     => _l('acs_leads_statuses_submenu'),
                    'href'     => admin_url('leads/statuses'),
                    'position' => 10,
            ]);
        $CI->app_menu->add_setup_children_item('leads', [
                    'slug'     => 'leads-email-integration',
                    'name'     => _l('leads_email_integration'),
                    'href'     => admin_url('leads/email_integration'),
                    'position' => 15,
            ]);
        $CI->app_menu->add_setup_children_item('leads', [
                    'slug'     => 'web-to-lead',
                    'name'     => _l('web_to_lead'),
                    'href'     => admin_url('leads/forms'),
                    'position' => 20,
            ]);

        $CI->app_menu->add_setup_menu_item('finance', [
                    'collapse' => true,
                    'name'     => _l('acs_finance'),
                    'position' => 25,
            ]);
        $CI->app_menu->add_setup_children_item('finance', [
                    'slug'     => 'taxes',
                    'name'     => _l('acs_sales_taxes_submenu'),
                    'href'     => admin_url('taxes'),
                    'position' => 5,
            ]);
        $CI->app_menu->add_setup_children_item('finance', [
                    'slug'     => 'currencies',
                    'name'     => _l('acs_sales_currencies_submenu'),
                    'href'     => admin_url('currencies'),
                    'position' => 10,
            ]);
        $CI->app_menu->add_setup_children_item('finance', [
                    'slug'     => 'payment-modes',
                    'name'     => _l('acs_sales_payment_modes_submenu'),
                    'href'     => admin_url('paymentmodes'),
                    'position' => 15,
            ]);
        $CI->app_menu->add_setup_children_item('finance', [
                    'slug'     => 'expenses-categories',
                    'name'     => _l('acs_expense_categories'),
                    'href'     => admin_url('expenses/categories'),
                    'position' => 20,
            ]);

        $CI->app_menu->add_setup_menu_item('contracts', [
                    'collapse' => true,
                    'name'     => _l('acs_contracts'),
                    'position' => 30,
            ]);
        $CI->app_menu->add_setup_children_item('contracts', [
                    'slug'     => 'contracts-types',
                    'name'     => _l('acs_contract_types'),
                    'href'     => admin_url('contracts/types'),
                    'position' => 5,
            ]);

        $modules_name = _l('modules');

        if ($modulesNeedsUpgrade = $CI->app_modules->number_of_modules_that_require_database_upgrade()) {
            $modules_name .= '<span class="badge menu-badge bg-warning">' . $modulesNeedsUpgrade . '</span>';
        }

        $CI->app_menu->add_setup_menu_item('modules', [
                    'href'     => admin_url('modules'),
                    'name'     => $modules_name,
                    'position' => 35,
            ]);

        $CI->app_menu->add_setup_menu_item('custom-fields', [
                    'href'     => admin_url('custom_fields'),
                    'name'     => _l('asc_custom_fields'),
                    'position' => 45,
            ]);

        $CI->app_menu->add_setup_menu_item('gdpr', [
                    'href'     => admin_url('gdpr'),
                    'name'     => _l('gdpr_short'),
                    'position' => 50,
            ]);

        $CI->app_menu->add_setup_menu_item('roles', [
                    'href'     => admin_url('roles'),
                    'name'     => _l('acs_roles'),
                    'position' => 55,
            ]);

/*             $CI->app_menu->add_setup_menu_item('api', [
                          'href'     => admin_url('api'),
                          'name'     => 'API',
                          'position' => 65,
                  ]);*/

    }

    if (has_permission('settings', '', 'view')) {
        $CI->app_menu->add_setup_menu_item('settings', [
                    'href'     => admin_url('settings'),
                    'name'     => _l('acs_settings'),
                    'position' => 200,
            ]);
    }

    if (has_permission('email_templates', '', 'view')) {
        $CI->app_menu->add_setup_menu_item('email-templates', [
                    'href'     => admin_url('emails'),
                    'name'     => _l('acs_email_templates'),
                    'position' => 40,
            ]);
    }
}
