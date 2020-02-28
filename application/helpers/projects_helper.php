<?php

defined('BASEPATH') or exit('No direct script access allowed');

hooks()->add_action('app_admin_assets', '_maybe_init_admin_project_assets', 5);

function _maybe_init_admin_project_assets()
{
    $CI = &get_instance();
    if (strpos($_SERVER['REQUEST_URI'], get_admin_uri() . '/projects/view') !== false
        || strpos($_SERVER['REQUEST_URI'], get_admin_uri() . '/projects/gantt') !== false) {
        $CI = &get_instance();

        $CI->app_scripts->add('jquery-comments-js', 'assets/plugins/jquery-comments/js/jquery-comments.min.js', 'admin', ['vendor-js']);
        $CI->app_scripts->add('jquery-gantt-js', 'assets/plugins/gantt/js/jquery.fn.gantt.min.js', 'admin', ['vendor-js']);

        $CI->app_css->add('jquery-comments-css', 'assets/plugins/jquery-comments/css/jquery-comments.css', 'admin', ['reset-css']);
        $CI->app_css->add('jquery-gantt-css', 'assets/plugins/gantt/css/style.css', 'admin', ['reset-css']);
    }
}

/**
 * Default project tabs
 * @return array
 */

function get_project_tabs_admin()
{
    return get_instance()->app_tabs->get_project_tabs();
}

/**
 * Init the default project tabs
 * @return null
 */
function app_init_project_tabs()
{
    $CI = &get_instance();

    $CI->app_tabs->add_project_tab('project_overview', [
        'name'     => _l('project_overview'),
        'icon'     => 'fa fa-th',
        'view'     => 'admin/projects/project_overview',
        'position' => 5,
    ]);

    $CI->app_tabs->add_project_tab('project_tasks', [
        'name'                      => _l('tasks'),
        'icon'                      => 'fa fa-check-circle',
        'view'                      => 'admin/projects/project_tasks',
        'position'                  => 10,
        'linked_to_customer_option' => ['view_tasks'],
    ]);

    $CI->app_tabs->add_project_tab('project_timesheets', [
        'name'                      => _l('project_timesheets'),
        'icon'                      => 'fa fa-clock-o',
        'view'                      => 'admin/projects/project_timesheets',
        'position'                  => 15,
        'linked_to_customer_option' => ['view_timesheets'],
    ]);

    $CI->app_tabs->add_project_tab('project_milestones', [
        'name'                      => _l('project_milestones'),
        'icon'                      => 'fa fa-rocket',
        'view'                      => 'admin/projects/project_milestones',
        'position'                  => 20,
        'linked_to_customer_option' => ['view_milestones'],
    ]);

    $CI->app_tabs->add_project_tab('project_files', [
        'name'                      => _l('project_files'),
        'icon'                      => 'fa fa-files-o',
        'view'                      => 'admin/projects/project_files',
        'position'                  => 25,
        'linked_to_customer_option' => ['upload_files'],
    ]);

    $CI->app_tabs->add_project_tab('project_discussions', [
        'name'                      => _l('project_discussions'),
        'icon'                      => 'fa fa-commenting',
        'view'                      => 'admin/projects/project_discussions',
        'position'                  => 30,
        'linked_to_customer_option' => ['open_discussions'],
    ]);

    $CI->app_tabs->add_project_tab('project_gantt', [
        'name'                      => _l('project_gant'),
        'icon'                      => 'fa fa-align-left',
        'view'                      => 'admin/projects/project_gantt',
        'position'                  => 35,
        'linked_to_customer_option' => ['view_gantt'],
    ]);

    $CI->app_tabs->add_project_tab('project_tickets', [
        'name'     => _l('project_tickets'),
        'icon'     => 'fa fa-life-ring',
        'view'     => 'admin/projects/project_tickets',
        'position' => 40,
        'visible'  => (get_option('access_tickets_to_none_staff_members') == 1 && !is_staff_member()) || is_staff_member(),
    ]);

    $CI->app_tabs->add_project_tab('sales', [
        'name'     => _l('sales_string'),
        'position' => 45,
        'collapse' => true,
        'visible'  => (has_permission('estimates', '', 'view') || has_permission('estimates', '', 'view_own') || (get_option('allow_staff_view_estimates_assigned') == 1 && staff_has_assigned_estimates()))
            || (has_permission('invoices', '', 'view') || has_permission('invoices', '', 'view_own') || (get_option('allow_staff_view_invoices_assigned') == 1 && staff_has_assigned_invoices()))
            || (has_permission('expenses', '', 'view') || has_permission('expenses', '', 'view_own')),
    ]);

    $CI->app_tabs->add_project_tab_children_item('sales', [
        'slug'     => 'project_invoices',
        'name'     => _l('project_invoices'),
        'view'     => 'admin/projects/project_invoices',
        'position' => 5,
        'visible'  => (has_permission('invoices', '', 'view') || has_permission('invoices', '', 'view_own') || (get_option('allow_staff_view_invoices_assigned') == 1 && staff_has_assigned_invoices())),
    ]);

    $CI->app_tabs->add_project_tab_children_item('sales', [
        'slug'     => 'project_estimates',
        'name'     => _l('estimates'),
        'view'     => 'admin/projects/project_estimates',
        'position' => 10,
        'visible'  => (has_permission('estimates', '', 'view') || has_permission('estimates', '', 'view_own') || (get_option('allow_staff_view_estimates_assigned') == 1 && staff_has_assigned_estimates())),
    ]);

    $CI->app_tabs->add_project_tab_children_item('sales', [
        'slug'     => 'project_expenses',
        'name'     => _l('project_expenses'),
        'view'     => 'admin/projects/project_expenses',
        'position' => 15,
       'visible'   => has_permission('expenses', '', 'view') || has_permission('expenses', '', 'view_own'),
    ]);

    $CI->app_tabs->add_project_tab_children_item('sales', [
        'slug'     => 'project_credit_notes',
        'name'     => _l('credit_notes'),
        'view'     => 'admin/projects/project_credit_notes',
        'position' => 20,
        'visible'  => has_permission('credit_notes', '', 'view') || has_permission('credit_notes', '', 'view_own'),
    ]);

    $CI->app_tabs->add_project_tab_children_item('sales', [
        'slug'     => 'project_subscriptions',
        'name'     => _l('subscriptions'),
        'view'     => 'admin/projects/project_subscriptions',
        'position' => 25,
        'visible'  => has_permission('subscriptions', '', 'view') || has_permission('subscriptions', '', 'view_own'),
    ]);

    $CI->app_tabs->add_project_tab('project_notes', [
        'name'     => _l('project_notes'),
        'icon'     => 'fa fa-file-o',
        'view'     => 'admin/projects/project_notes',
        'position' => 50,
    ]);

    $CI->app_tabs->add_project_tab('project_activity', [
        'name'                      => _l('project_activity'),
        'icon'                      => 'fa fa-exclamation',
        'view'                      => 'admin/projects/project_activity',
        'position'                  => 55,
        'linked_to_customer_option' => ['view_activity_log'],
    ]);
}

/**
 * Filter only visible tabs selected from project settings
 * @param  array $tabs available tabs
 * @param  array $applied_settings current applied project visible tabs
 * @return array
 */
function filter_project_visible_tabs($tabs, $applied_settings)
{
    $newTabs = [];
    foreach ($tabs as $key => $tab) {
        $dropdown = isset($tab['collapse']) ? true : false;

        if ($dropdown) {
            $totalChildTabsHidden = 0;
            $newChild             = [];

            foreach ($tab['children'] as $d) {
                if ((isset($applied_settings[$d['slug']]) && $applied_settings[$d['slug']] == 0)) {
                    $totalChildTabsHidden++;
                } else {
                    $newChild[] = $d;
                }
            }

            if ($totalChildTabsHidden == count($tab['children'])) {
                continue;
            }

            if (count($newChild) > 0) {
                $tab['children'] = $newChild;
            }

            $newTabs[$tab['slug']] = $tab;
        } else {
            if (isset($applied_settings[$key]) && $applied_settings[$key] == 0) {
                continue;
            }

            $newTabs[$tab['slug']] = $tab;
        }
    }

    return hooks()->apply_filters('project_filtered_visible_tabs', $newTabs);
}

/**
 * Get project by ID or current queried project
 * @param  mixed $id project id
 * @return mixed
 */
function get_project($id = null)
{
    if (empty($id) && isset($GLOBALS['project'])) {
        return $GLOBALS['project'];
    }

    // Client global object not set
    if (empty($id)) {
        return null;
    }

    if (!class_exists('projects_model', false)) {
        get_instance()->load->model('projects_model');
    }

    $project = get_instance()->projects_model->get($id);

    return $project;
}

/**
 * Get project status by passed project id
 * @param  mixed $id project id
 * @return array
 */
function get_project_status_by_id($id)
{
    $CI = &get_instance();
    if (!class_exists('projects_model')) {
        $CI->load->model('projects_model');
    }

    $statuses = $CI->projects_model->get_project_statuses();

    $status = [
          'id'    => 0,
          'color' => '#333',
          'name'  => '[Status Not Found]',
          'order' => 1,
      ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * Return logged in user pinned projects
 * @return array
 */
function get_user_pinned_projects()
{
    $CI = &get_instance();
    $CI->db->select(db_prefix() . 'projects.id, ' . db_prefix() . 'projects.name, ' . db_prefix() . 'projects.clientid, ' . get_sql_select_client_company());
    $CI->db->join(db_prefix() . 'projects', db_prefix() . 'projects.id=' . db_prefix() . 'pinned_projects.project_id');
    $CI->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid=' . db_prefix() . 'projects.clientid');
    $CI->db->where(db_prefix() . 'pinned_projects.staff_id', get_staff_user_id());
    $projects = $CI->db->get(db_prefix() . 'pinned_projects')->result_array();
    $CI->load->model('projects_model');

    foreach ($projects as $key => $project) {
        $projects[$key]['progress'] = $CI->projects_model->calc_progress($project['id']);
    }

    return $projects;
}


/**
 * Get project name by passed id
 * @param  mixed $id
 * @return string
 */
function get_project_name_by_id($id)
{
    $CI      = & get_instance();
    $project = $CI->app_object_cache->get('project-name-data-' . $id);

    if (!$project) {
        $CI->db->select('name');
        $CI->db->where('id', $id);
        $project = $CI->db->get(db_prefix() . 'projects')->row();
        $CI->app_object_cache->add('project-name-data-' . $id, $project);
    }

    if ($project) {
        return $project->name;
    }

    return '';
}

/**
 * Return project milestones
 * @param  mixed $project_id project id
 * @return array
 */
function get_project_milestones($project_id)
{
    $CI = &get_instance();
    $CI->db->where('project_id', $project_id);
    $CI->db->order_by('milestone_order', 'ASC');

    return $CI->db->get(db_prefix() . 'milestones')->result_array();
}

/**
 * Get project client id by passed project id
 * @param  mixed $id project id
 * @return mixed
 */
function get_client_id_by_project_id($id)
{
    $CI = & get_instance();
    $CI->db->select('clientid');
    $CI->db->where('id', $id);
    $project = $CI->db->get(db_prefix() . 'projects')->row();
    if ($project) {
        return $project->clientid;
    }

    return false;
}

/**
 * Check if customer has project assigned
 * @param  mixed $customer_id customer id to check
 * @return boolean
 */
function customer_has_projects($customer_id)
{
    $totalCustomerProjects = total_rows(db_prefix() . 'projects', 'clientid=' . $customer_id);

    return ($totalCustomerProjects > 0 ? true : false);
}

/**
 * Get project billing type
 * @param  mixed $project_id
 * @return mixed
 */
function get_project_billing_type($project_id)
{
    $CI = & get_instance();
    $CI->db->select('billing_type');
    $CI->db->where('id', $project_id);
    $project = $CI->db->get(db_prefix() . 'projects')->row();
    if ($project) {
        return $project->billing_type;
    }

    return false;
}
/**
 * Get project deadline
 * @param  mixed $project_id
 * @return mixed
 */
function get_project_deadline($project_id)
{
    $CI = & get_instance();
    $CI->db->select('deadline');
    $CI->db->where('id', $project_id);
    $project = $CI->db->get(db_prefix() . 'projects')->row();
    if ($project) {
        return $project->deadline;
    }

    return false;
}

/**
 * Translated jquery-comment language based on app languages
 * This feature is used on both admin and customer area
 * @return array
 */
function get_project_discussions_language_array()
{
    $lang = [
        'discussion_add_comment'      => _l('discussion_add_comment'),
        'discussion_newest'           => _l('discussion_newest'),
        'discussion_oldest'           => _l('discussion_oldest'),
        'discussion_attachments'      => _l('discussion_attachments'),
        'discussion_send'             => _l('discussion_send'),
        'discussion_reply'            => _l('discussion_reply'),
        'discussion_edit'             => _l('discussion_edit'),
        'discussion_edited'           => _l('discussion_edited'),
        'discussion_you'              => _l('discussion_you'),
        'discussion_save'             => _l('discussion_save'),
        'discussion_delete'           => _l('discussion_delete'),
        'discussion_view_all_replies' => _l('discussion_view_all_replies'),
        'discussion_hide_replies'     => _l('discussion_hide_replies'),
        'discussion_no_comments'      => _l('discussion_no_comments'),
        'discussion_no_attachments'   => _l('discussion_no_attachments'),
        'discussion_attachments_drop' => _l('discussion_attachments_drop'),
    ];

    return $lang;
}

/**
 * Check if project has recurring tasks
 * @param  mixed $id project id
 * @return boolean
 */
function project_has_recurring_tasks($id)
{
    return total_rows(db_prefix() . 'tasks', 'recurring=1 AND rel_id="' . $id . '" AND rel_type="project"') > 0;
}

function total_project_tasks_by_milestone($milestone_id, $project_id)
{
    return total_rows(db_prefix() . 'tasks', [
              'rel_type'  => 'project',
              'rel_id'    => $project_id,
              'milestone' => $milestone_id,
             ]);
}

function total_project_finished_tasks_by_milestone($milestone_id, $project_id)
{
    return total_rows(db_prefix() . 'tasks', [
             'rel_type'  => 'project',
             'rel_id'    => $project_id,
             'status'    => 5,
             'milestone' => $milestone_id,
             ]);
}
