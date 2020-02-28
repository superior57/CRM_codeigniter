<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Check whether the contact email is verified
 * @since  2.2.0
 * @param  mixed  $id contact id
 * @return boolean
 */
function is_contact_email_verified($id = null)
{
    $id = !$id ? get_contact_user_id() : $id;

    if (isset($GLOBALS['contact']) && $GLOBALS['contact']->id == $id) {
        return !is_null($GLOBALS['contact']->email_verified_at);
    }

    $CI = &get_instance();

    $CI->db->select('email_verified_at');
    $CI->db->where('id', $id);
    $contact = $CI->db->get(db_prefix() . 'contacts')->row();

    if (!$contact) {
        return false;
    }

    return !is_null($contact->email_verified_at);
}

/**
 * Check whether the user disabled verification emails for contacts
 * @return boolean
 */
function is_email_verification_enabled()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'contact-verification-email', 'active' => 0]) == 0;
}
/**
 * Check if client id is used in the system
 * @param  mixed  $id client id
 * @return boolean
 */
function is_client_id_used($id)
{
    $total = 0;

    $checkCommonTables = [db_prefix() . 'subscriptions', db_prefix() . 'creditnotes', db_prefix() . 'projects', db_prefix() . 'invoices', db_prefix() . 'expenses', db_prefix() . 'estimates'];

    foreach ($checkCommonTables as $table) {
        $total += total_rows($table, [
            'client' => $id,
        ]);
    }

    $total += total_rows(db_prefix() . 'contracts', [
        'client' => $id,
    ]);

    $total += total_rows(db_prefix() . 'proposals', [
        'rel_id'   => $id,
        'rel_type' => 'customer',
    ]);

    $total += total_rows(db_prefix() . 'tickets', [
        'userid' => $id,
    ]);

    $total += total_rows(db_prefix() . 'tasks', [
        'rel_id'   => $id,
        'rel_type' => 'customer',
    ]);

    return hooks()->apply_filters('is_client_id_used', $total > 0 ? true : false, $id);
}
/**
 * Check if customer has subscriptions
 * @param  mixed $id customer id
 * @return boolean
 */
function customer_has_subscriptions($id)
{
    return hooks()->apply_filters('customer_has_subscriptions', total_rows(db_prefix() . 'subscriptions', ['clientid' => $id]) > 0);
}
/**
 * Get client by ID or current queried client
 * @param  mixed $id client id
 * @return mixed
 */
function get_client($id = null)
{
    if (empty($id) && isset($GLOBALS['client'])) {
        return $GLOBALS['client'];
    }

    // Client global object not set
    if (empty($id)) {
        return null;
    }

    $client = get_instance()->clients_model->get($id);

    return $client;
}
/**
 * Get predefined tabs array, used in customer profile
 * @return array
 */
function get_customer_profile_tabs()
{
    return get_instance()->app_tabs->get_customer_profile_tabs();
}

/**
 * Filter only visible tabs selected from the profile
 * @param  array $tabs available tabs
 * @return array
 */
function filter_client_visible_tabs($tabs)
{
    $newTabs = [];

    $visible = get_option('visible_customer_profile_tabs');
    if ($visible != 'all') {
        $visible = unserialize($visible);
    }

    $appliedSettings = is_array($visible);
    foreach ($tabs as $key => $tab) {

        // Check visibility from settings too
        if ($key != 'profile' && $key != 'contacts' && $appliedSettings) {
            if (array_key_exists($key, $visible) && $visible[$key] == false) {
                continue;
            }
        }

        $newTabs[$key] = $tab;
    }

    return hooks()->apply_filters('client_filtered_visible_tabs', $newTabs);
}
/**
 * @todo
 * Find a way to get the customer_id inside this function or refactor the hook
 * @param  string $group the tabs groups
 * @return null
 */
function app_init_customer_profile_tabs()
{
    $client_id = null;

    $remindersText = _l('client_reminders_tab');

    if ($client = get_client()) {
        $client_id = $client->userid;

        $total_reminders = total_rows(
              db_prefix() . 'reminders',
            [
             'isnotified' => 0,
             'staff'      => get_staff_user_id(),
             'rel_type'   => 'customer',
             'rel_id'     => $client_id,
           ]
          );

        if ($total_reminders > 0) {
            $remindersText .= ' <span class="badge">' . $total_reminders . '</span>';
        }
    }

    $CI = &get_instance();

    $CI->app_tabs->add_customer_profile_tab('profile', [
        'name'     => _l('client_add_edit_profile'),
        'icon'     => 'fa fa-user-circle',
        'view'     => 'admin/clients/groups/profile',
        'position' => 5,
    ]);

    $CI->app_tabs->add_customer_profile_tab('contacts', [
        'name'     => !is_empty_customer_company($client_id) || empty($client_id) ? _l('customer_contacts') : _l('contact'),
        'icon'     => 'fa fa-users',
        'view'     => 'admin/clients/groups/contacts',
        'position' => 10,
    ]);

    $CI->app_tabs->add_customer_profile_tab('notes', [
        'name'     => _l('contracts_notes_tab'),
        'icon'     => 'fa fa-sticky-note-o',
        'view'     => 'admin/clients/groups/notes',
        'position' => 15,
    ]);

    $CI->app_tabs->add_customer_profile_tab('statement', [
        'name'     => _l('customer_statement'),
        'icon'     => 'fa fa-area-chart',
        'view'     => 'admin/clients/groups/statement',
        'visible'  => (has_permission('invoices', '', 'view') && has_permission('payments', '', 'view')),
        'position' => 20,
    ]);

    $CI->app_tabs->add_customer_profile_tab('invoices', [
        'name'     => _l('client_invoices_tab'),
        'icon'     => 'fa fa-file-text',
        'view'     => 'admin/clients/groups/invoices',
        'visible'  => (has_permission('invoices', '', 'view') || has_permission('invoices', '', 'view_own') || (get_option('allow_staff_view_invoices_assigned') == 1 && staff_has_assigned_invoices())),
        'position' => 25,
    ]);

    $CI->app_tabs->add_customer_profile_tab('payments', [
        'name'     => _l('client_payments_tab'),
        'icon'     => 'fa fa-line-chart',
        'view'     => 'admin/clients/groups/payments',
        'visible'  => (has_permission('payments', '', 'view') || has_permission('invoices', '', 'view_own') || (get_option('allow_staff_view_invoices_assigned') == 1 && staff_has_assigned_invoices())),
        'position' => 30,
    ]);

    $CI->app_tabs->add_customer_profile_tab('proposals', [
        'name'     => _l('proposals'),
        'icon'     => 'fa fa-file-powerpoint-o',
        'view'     => 'admin/clients/groups/proposals',
        'visible'  => (has_permission('proposals', '', 'view') || has_permission('proposals', '', 'view_own') || (get_option('allow_staff_view_proposals_assigned') == 1 && staff_has_assigned_proposals())),
        'position' => 35,
    ]);

    $CI->app_tabs->add_customer_profile_tab('credit_notes', [
        'name'     => _l('credit_notes'),
        'icon'     => 'fa fa-sticky-note-o',
        'view'     => 'admin/clients/groups/credit_notes',
        'visible'  => (has_permission('credit_notes', '', 'view') || has_permission('credit_notes', '', 'view_own')),
        'position' => 40,
    ]);

    $CI->app_tabs->add_customer_profile_tab('estimates', [
        'name'     => _l('estimates'),
        'icon'     => 'fa fa-clipboard',
        'view'     => 'admin/clients/groups/estimates',
        'visible'  => (has_permission('estimates', '', 'view') || has_permission('estimates', '', 'view_own') || (get_option('allow_staff_view_estimates_assigned') == 1 && staff_has_assigned_estimates())),
        'position' => 45,
    ]);

    $CI->app_tabs->add_customer_profile_tab('subscriptions', [
        'name'     => _l('subscriptions'),
        'icon'     => 'fa fa-repeat',
        'view'     => 'admin/clients/groups/subscriptions',
        'visible'  => (has_permission('subscriptions', '', 'view') || has_permission('subscriptions', '', 'view_own')),
        'position' => 50,
    ]);

    $CI->app_tabs->add_customer_profile_tab('expenses', [
        'name'     => _l('expenses'),
        'icon'     => 'fa fa-file-text-o',
        'view'     => 'admin/clients/groups/expenses',
        'visible'  => (has_permission('expenses', '', 'view') || has_permission('expenses', '', 'view_own')),
        'position' => 55,
    ]);

    $CI->app_tabs->add_customer_profile_tab('contracts', [
        'name'     => _l('contracts'),
        'icon'     => 'fa fa-file',
        'view'     => 'admin/clients/groups/contracts',
        'visible'  => (has_permission('contracts', '', 'view') || has_permission('contracts', '', 'view_own')),
        'position' => 60,
    ]);

    $CI->app_tabs->add_customer_profile_tab('projects', [
        'name'     => _l('projects'),
        'icon'     => 'fa fa-bars',
        'view'     => 'admin/clients/groups/projects',
        'position' => 65,
    ]);

    $CI->app_tabs->add_customer_profile_tab('tasks', [
        'name'     => _l('tasks'),
        'icon'     => 'fa fa-tasks',
        'view'     => 'admin/clients/groups/tasks',
        'position' => 70,
    ]);

    $CI->app_tabs->add_customer_profile_tab('tickets', [
        'name'     => _l('tickets'),
        'icon'     => 'fa fa-ticket',
        'view'     => 'admin/clients/groups/tickets',
        'visible'  => ((get_option('access_tickets_to_none_staff_members') == 1 && !is_staff_member()) || is_staff_member()),
        'position' => 75,
    ]);

    $CI->app_tabs->add_customer_profile_tab('attachments', [
        'name'     => _l('customer_attachments'),
        'icon'     => 'fa fa-paperclip',
        'view'     => 'admin/clients/groups/attachments',
        'position' => 80,
    ]);

    $CI->app_tabs->add_customer_profile_tab('vault', [
        'name'     => _l('vault'),
        'icon'     => 'fa fa-lock',
        'view'     => 'admin/clients/groups/vault',
        'position' => 85,
    ]);

    $CI->app_tabs->add_customer_profile_tab('reminders', [
        'name'     => $remindersText,
        'icon'     => 'fa fa-clock-o',
        'view'     => 'admin/clients/groups/reminders',
        'position' => 90,
    ]);

    $CI->app_tabs->add_customer_profile_tab('map', [
        'name'     => _l('customer_map'),
        'icon'     => 'fa fa-map-marker',
        'view'     => 'admin/clients/groups/map',
        'position' => 95,
    ]);
}

/**
 * Get client id by lead id
 * @since  Version 1.0.1
 * @param  mixed $id lead id
 * @return mixed     client id
 */
function get_client_id_by_lead_id($id)
{
    $CI = & get_instance();
    $CI->db->select('userid')->from(db_prefix() . 'clients')->where('leadid', $id);

    return $CI->db->get()->row()->userid;
}

/**
 * Check if contact id passed is primary contact
 * If you dont pass $contact_id the current logged in contact will be checked
 * @param  string  $contact_id
 * @return boolean
 */
function is_primary_contact($contact_id = '')
{
    if (!is_numeric($contact_id)) {
        $contact_id = get_contact_user_id();
    }

    if (total_rows(db_prefix() . 'contacts', ['id' => $contact_id, 'is_primary' => 1]) > 0) {
        return true;
    }

    return false;
}

/**
 * Check if client have invoices with multiple currencies
 * @return booelan
 */
function is_client_using_multiple_currencies($clientid = '', $table = null)
{
    if (!$table) {
        $table = db_prefix() . 'invoices';
    }

    $CI = & get_instance();

    $clientid = $clientid == '' ? get_client_user_id() : $clientid;
    $CI->load->model('currencies_model');
    $currencies            = $CI->currencies_model->get();
    $total_currencies_used = 0;
    foreach ($currencies as $currency) {
        $CI->db->where('currency', $currency['id']);
        $CI->db->where('clientid', $clientid);
        $total = $CI->db->count_all_results($table);
        if ($total > 0) {
            $total_currencies_used++;
        }
    }

    $retVal = true;
    if ($total_currencies_used > 1) {
        $retVal = true;
    } elseif ($total_currencies_used == 0 || $total_currencies_used == 1) {
        $retVal = false;
    }

    return hooks()->apply_filters('is_client_using_multiple_currencies', $retVal, [
        'client_id' => $clientid,
        'table'     => $table,
    ]);
}


/**
 * Function used to check if is really empty customer company
 * Can happen user to have selected that the company field is not required and the primary contact name is auto added in the company field
 * @param  mixed  $id
 * @return boolean
 */
function is_empty_customer_company($id)
{
    $CI = & get_instance();
    $CI->db->select('company');
    $CI->db->from(db_prefix() . 'clients');
    $CI->db->where('userid', $id);
    $row = $CI->db->get()->row();
    if ($row) {
        if ($row->company == '') {
            return true;
        }

        return false;
    }

    return true;
}

/**
 * Get ids to check what files with contacts are shared
 * @param  array  $where
 * @return array
 */
function get_customer_profile_file_sharing($where = [])
{
    $CI = & get_instance();
    $CI->db->where($where);

    return $CI->db->get(db_prefix() . 'shared_customer_files')->result_array();
}

/**
 * Get customer id by passed contact id
 * @param  mixed $id
 * @return mixed
 */
function get_user_id_by_contact_id($id)
{
    $CI = & get_instance();

    $userid = $CI->app_object_cache->get('user-id-by-contact-id-' . $id);
    if (!$userid) {
        $CI->db->select('userid')
        ->where('id', $id);
        $client = $CI->db->get(db_prefix() . 'contacts')->row();

        if ($client) {
            $userid = $client->userid;
            $CI->app_object_cache->add('user-id-by-contact-id-' . $id, $userid);
        }
    }

    return $userid;
}

/**
 * Get primary contact user id for specific customer
 * @param  mixed $userid
 * @return mixed
 */
function get_primary_contact_user_id($userid)
{
    $CI = & get_instance();
    $CI->db->where('userid', $userid);
    $CI->db->where('is_primary', 1);
    $row = $CI->db->get(db_prefix() . 'contacts')->row();

    if ($row) {
        return $row->id;
    }

    return false;
}

/**
 * Get client full name
 * @param  string $contact_id Optional
 * @return string Firstname and Lastname
 */
function get_contact_full_name($contact_id = '')
{
    $contact_id == '' ? get_contact_user_id() : $contact_id;

    $CI = & get_instance();

    $contact = $CI->app_object_cache->get('contact-full-name-data-' . $contact_id);

    if (!$contact) {
        $CI->db->where('id', $contact_id);
        $contact = $CI->db->select('firstname,lastname')->from(db_prefix() . 'contacts')->get()->row();
        $CI->app_object_cache->add('contact-full-name-data-' . $contact_id, $contact);
    }

    if ($contact) {
        return $contact->firstname . ' ' . $contact->lastname;
    }

    return '';
}
/**
 * Return contact profile image url
 * @param  mixed $contact_id
 * @param  string $type
 * @return string
 */
function contact_profile_image_url($contact_id, $type = 'small')
{
    $url  = base_url('assets/images/user-placeholder.jpg');
    $CI   = & get_instance();
    $path = $CI->app_object_cache->get('contact-profile-image-path-' . $contact_id);

    if (!$path) {
        $CI->app_object_cache->add('contact-profile-image-path-' . $contact_id, $url);

        $CI->db->select('profile_image');
        $CI->db->from(db_prefix() . 'contacts');
        $CI->db->where('id', $contact_id);
        $contact = $CI->db->get()->row();

        if ($contact && !empty($contact->profile_image)) {
            $path = 'uploads/client_profile_images/' . $contact_id . '/' . $type . '_' . $contact->profile_image;
            $CI->app_object_cache->set('contact-profile-image-path-' . $contact_id, $path);
        }
    }

    if ($path && file_exists($path)) {
        $url = base_url($path);
    }

    return $url;
}
/**
 * Used in:
 * Search contact tickets
 * Project dropdown quick switch
 * Calendar tooltips
 * @param  [type] $userid [description]
 * @return [type]         [description]
 */
function get_company_name($userid, $prevent_empty_company = false)
{
    $_userid = get_client_user_id();
    if ($userid !== '') {
        $_userid = $userid;
    }
    $CI = & get_instance();

    $select = ($prevent_empty_company == false ? get_sql_select_client_company() : 'company');

    $client = $CI->db->select($select)
    ->where('userid', $_userid)
    ->from(db_prefix() . 'clients')
    ->get()
    ->row();
    if ($client) {
        return $client->company;
    }

    return '';
}


/**
 * Get client default language
 * @param  mixed $clientid
 * @return mixed
 */
function get_client_default_language($clientid = '')
{
    if (!is_numeric($clientid)) {
        $clientid = get_client_user_id();
    }

    $CI = & get_instance();
    $CI->db->select('default_language');
    $CI->db->from(db_prefix() . 'clients');
    $CI->db->where('userid', $clientid);
    $client = $CI->db->get()->row();
    if ($client) {
        return $client->default_language;
    }

    return '';
}

/**
 * Function is customer admin
 * @param  mixed  $id       customer id
 * @param  staff_id  $staff_id staff id to check
 * @return boolean
 */
function is_customer_admin($id, $staff_id = '')
{
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $CI       = &get_instance();
    $cache    = $CI->app_object_cache->get($id . '-is-customer-admin-' . $staff_id);

    if ($cache) {
        return $cache['retval'];
    }

    $total = total_rows(db_prefix() . 'customer_admins', [
        'customer_id' => $id,
        'staff_id'    => $staff_id,
    ]);

    $retval = $total > 0 ? true : false;
    $CI->app_object_cache->add($id . '-is-customer-admin-' . $staff_id, ['retval' => $retval]);

    return $retval;
}
/**
 * Check if staff member have assigned customers
 * @param  mixed $staff_id staff id
 * @return boolean
 */
function have_assigned_customers($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-customers-' . $staff_id);

    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix() . 'customer_admins', [
            'staff_id' => $staff_id,
        ]);
        $CI->app_object_cache->add('staff-total-assigned-customers-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}
/**
 * Check if contact has permission
 * @param  string  $permission permission name
 * @param  string  $contact_id     contact id
 * @return boolean
 */
function has_contact_permission($permission, $contact_id = '')
{
    $CI = & get_instance();

    if (!class_exists('app')) {
        $CI->load->library('app');
    }

    $permissions = get_contact_permissions();

    if (empty($contact_id)) {
        $contact_id = get_contact_user_id();
    }

    foreach ($permissions as $_permission) {
        if ($_permission['short_name'] == $permission) {
            return total_rows(db_prefix() . 'contact_permissions', [
                'permission_id' => $_permission['id'],
                'userid'        => $contact_id,
            ]) > 0;
        }
    }

    return false;
}
/**
 * Load customers area language
 * @param  string $customer_id
 * @return string return loaded language
 */
function load_client_language($customer_id = '')
{
    $CI       = & get_instance();
    $language = get_option('active_language');

    if (is_client_logged_in() || $customer_id != '') {

        $client_language = get_client_default_language($customer_id);

        if (!empty($client_language)
            && file_exists(APPPATH . 'language/' . $client_language)) {
            $language = $client_language;
        }
    }

    $CI->lang->is_loaded = [];
    $CI->lang->language  = [];

    $CI->lang->load($language . '_lang', $language);
    if (file_exists(APPPATH . 'language/' . $language . '/custom_lang.php')) {
        $CI->lang->load('custom_lang', $language);
    }

    $GLOBALS['language'] = $language;
    $GLOBALS['locale']   = get_locale_key($language);

    hooks()->do_action('after_load_client_language', $language);

    return $language;
}
/**
 * Check if client have transactions recorded
 * @param  mixed $id clientid
 * @return boolean
 */
function client_have_transactions($id)
{
    $total = 0;

    foreach ([db_prefix() . 'invoices', db_prefix() . 'creditnotes', db_prefix() . 'estimates'] as $table) {
        $total += total_rows($table, [
            'clientid' => $id,
        ]);
    }

    $total += total_rows(db_prefix() . 'expenses', [
        'clientid' => $id,
        'billable' => 1,
    ]);

    $total += total_rows(db_prefix() . 'proposals', [
        'rel_id'   => $id,
        'rel_type' => 'customer',
    ]);

    return hooks()->apply_filters('customer_have_transactions', $total > 0, $id);
}


/**
* Predefined contact permission
* @return array
*/
function get_contact_permissions()
{
    $permissions = [
        [
            'id'         => 1,
            'name'       => _l('customer_permission_invoice'),
            'short_name' => 'invoices',
        ],
        [
            'id'         => 2,
            'name'       => _l('customer_permission_estimate'),
            'short_name' => 'estimates',
        ],
        [
            'id'         => 3,
            'name'       => _l('customer_permission_contract'),
            'short_name' => 'contracts',
        ],
        [
            'id'         => 4,
            'name'       => _l('customer_permission_proposal'),
            'short_name' => 'proposals',
        ],
        [
            'id'         => 5,
            'name'       => _l('customer_permission_support'),
            'short_name' => 'support',
        ],
        [
            'id'         => 6,
            'name'       => _l('customer_permission_projects'),
            'short_name' => 'projects',
        ],
    ];

    return hooks()->apply_filters('get_contact_permissions', $permissions);
}

function get_contact_permission($name)
{
    $permissions = get_contact_permissions();

    foreach ($permissions as $permission) {
        if ($permission['short_name'] == $name) {
            return $permission;
        }
    }

    return false;
}

/**
 * Additional checking for customers area, when contact edit his profile
 * This function will check if the checkboxes for email notifications should be shown
 * @return boolean
 */
function can_contact_view_email_notifications_options()
{
    if (has_contact_permission('invoices')
        || has_contact_permission('estimates')
        || has_contact_permission('projects')
        || has_contact_permission('contracts')) {
        return true;
    }

    return false;
}

/**
* With this function staff can login as client in the clients area
* @param  mixed $id client id
*/
function login_as_client($id)
{
    $CI = &get_instance();

    $CI->db->select(db_prefix() . 'contacts.id, active')
    ->where('userid', $id)
    ->where('is_primary', 1);

    $primary = $CI->db->get(db_prefix() . 'contacts')->row();

    if (!$primary) {
        set_alert('danger', _l('no_primary_contact'));
        redirect($_SERVER['HTTP_REFERER']);
    } else if($primary->active == '0') {
        set_alert('danger', 'Customer primary contact is not active, please set the primary contact as active in order to login as client');
        redirect($_SERVER['HTTP_REFERER']);
    }

    $CI->load->model('announcements_model');
    $CI->announcements_model->set_announcements_as_read_except_last_one($primary->id);

    $user_data = [
        'client_user_id'      => $id,
        'contact_user_id'     => $primary->id,
        'client_logged_in'    => true,
        'logged_in_as_client' => true,
    ];

    $CI->session->set_userdata($user_data);
}

function send_customer_registered_email_to_administrators($client_id)
{
    $CI = &get_instance();
    $CI->load->model('staff_model');
    $admins = $CI->staff_model->get('', ['active' => 1, 'admin' => 1]);

    foreach ($admins as $admin) {
        send_mail_template('customer_new_registration_to_admins', $admin['email'], $client_id, $admin['staffid']);
    }
}

/**
 * Return and perform additional checkings for contact consent url
 * @param  mixed $contact_id contact id
 * @return string
 */
function contact_consent_url($contact_id)
{
    $CI = &get_instance();

    $consent_key = get_contact_meta($contact_id, 'consent_key');

    if (empty($consent_key)) {
        $consent_key = app_generate_hash() . '-' . app_generate_hash();
        $meta_id     = false;
        if (total_rows(db_prefix() . 'contacts', ['id' => $contact_id]) > 0) {
            $meta_id = add_contact_meta($contact_id, 'consent_key', $consent_key);
        }
        if (!$meta_id) {
            return '';
        }
    }

    return site_url('consent/contact/' . $consent_key);
}

/**
*  Get customer attachment
* @param   mixed $id   customer id
* @return  array
*/
function get_all_customer_attachments($id)
{
    $CI = &get_instance();

    $attachments                = [];
    $attachments['invoice']     = [];
    $attachments['estimate']    = [];
    $attachments['credit_note'] = [];
    $attachments['proposal']    = [];
    $attachments['contract']    = [];
    $attachments['lead']        = [];
    $attachments['task']        = [];
    $attachments['customer']    = [];
    $attachments['ticket']      = [];
    $attachments['expense']     = [];

    $has_permission_expenses_view = has_permission('expenses', '', 'view');
    $has_permission_expenses_own  = has_permission('expenses', '', 'view_own');
    if ($has_permission_expenses_view || $has_permission_expenses_own) {
        // Expenses
        $CI->db->select('clientid,id');
        $CI->db->where('clientid', $id);
        if (!$has_permission_expenses_view) {
            $CI->db->where('addedfrom', get_staff_user_id());
        }

        $CI->db->from(db_prefix() . 'expenses');
        $expenses = $CI->db->get()->result_array();
        $ids      = array_column($expenses, 'id');
        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'expense');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();
            foreach ($_attachments as $_att) {
                array_push($attachments['expense'], $_att);
            }
        }
    }


    $has_permission_invoices_view = has_permission('invoices', '', 'view');
    $has_permission_invoices_own  = has_permission('invoices', '', 'view_own');
    if ($has_permission_invoices_view || $has_permission_invoices_own || get_option('allow_staff_view_invoices_assigned') == 1) {
        $noPermissionQuery = get_invoices_where_sql_for_staff(get_staff_user_id());
        // Invoices
        $CI->db->select('clientid,id');
        $CI->db->where('clientid', $id);

        if (!$has_permission_invoices_view) {
            $CI->db->where($noPermissionQuery);
        }

        $CI->db->from(db_prefix() . 'invoices');
        $invoices = $CI->db->get()->result_array();

        $ids = array_column($invoices, 'id');
        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'invoice');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();
            foreach ($_attachments as $_att) {
                array_push($attachments['invoice'], $_att);
            }
        }
    }

    $has_permission_credit_notes_view = has_permission('credit_notes', '', 'view');
    $has_permission_credit_notes_own  = has_permission('credit_notes', '', 'view_own');

    if ($has_permission_credit_notes_view || $has_permission_credit_notes_own) {
        // credit_notes
        $CI->db->select('clientid,id');
        $CI->db->where('clientid', $id);

        if (!$has_permission_credit_notes_view) {
            $CI->db->where('addedfrom', get_staff_user_id());
        }

        $CI->db->from(db_prefix() . 'creditnotes');
        $credit_notes = $CI->db->get()->result_array();

        $ids = array_column($credit_notes, 'id');
        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'credit_note');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();
            foreach ($_attachments as $_att) {
                array_push($attachments['credit_note'], $_att);
            }
        }
    }

    $permission_estimates_view = has_permission('estimates', '', 'view');
    $permission_estimates_own  = has_permission('estimates', '', 'view_own');

    if ($permission_estimates_view || $permission_estimates_own || get_option('allow_staff_view_proposals_assigned') == 1) {
        $noPermissionQuery = get_estimates_where_sql_for_staff(get_staff_user_id());
        // Estimates
        $CI->db->select('clientid,id');
        $CI->db->where('clientid', $id);
        if (!$permission_estimates_view) {
            $CI->db->where($noPermissionQuery);
        }
        $CI->db->from(db_prefix() . 'estimates');
        $estimates = $CI->db->get()->result_array();

        $ids = array_column($estimates, 'id');
        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'estimate');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();

            foreach ($_attachments as $_att) {
                array_push($attachments['estimate'], $_att);
            }
        }
    }

    $has_permission_proposals_view = has_permission('proposals', '', 'view');
    $has_permission_proposals_own  = has_permission('proposals', '', 'view_own');

    if ($has_permission_proposals_view || $has_permission_proposals_own || get_option('allow_staff_view_proposals_assigned') == 1) {
        $noPermissionQuery = get_proposals_sql_where_staff(get_staff_user_id());
        // Proposals
        $CI->db->select('rel_id,id');
        $CI->db->where('rel_id', $id);
        $CI->db->where('rel_type', 'customer');
        if (!$has_permission_proposals_view) {
            $CI->db->where($noPermissionQuery);
        }
        $CI->db->from(db_prefix() . 'proposals');
        $proposals = $CI->db->get()->result_array();

        $ids = array_column($proposals, 'id');

        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'proposal');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();

            foreach ($_attachments as $_att) {
                array_push($attachments['proposal'], $_att);
            }
        }
    }

    $permission_contracts_view = has_permission('contracts', '', 'view');
    $permission_contracts_own  = has_permission('contracts', '', 'view_own');
    if ($permission_contracts_view || $permission_contracts_own) {
        // Contracts
        $CI->db->select('client,id');
        $CI->db->where('client', $id);
        if (!$permission_contracts_view) {
            $CI->db->where('addedfrom', get_staff_user_id());
        }
        $CI->db->from(db_prefix() . 'contracts');
        $contracts = $CI->db->get()->result_array();

        $ids = array_column($contracts, 'id');

        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'contract');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();

            foreach ($_attachments as $_att) {
                array_push($attachments['contract'], $_att);
            }
        }
    }

    $CI->db->select('leadid')
    ->where('userid', $id);
    $customer = $CI->db->get(db_prefix() . 'clients')->row();

    if ($customer->leadid != null) {
        $CI->db->where('rel_id', $customer->leadid);
        $CI->db->where('rel_type', 'lead');
        $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();
        foreach ($_attachments as $_att) {
            array_push($attachments['lead'], $_att);
        }
    }

    $CI->db->select('ticketid,userid');
    $CI->db->where('userid', $id);
    $CI->db->from(db_prefix() . 'tickets');
    $tickets = $CI->db->get()->result_array();

    $ids = array_column($tickets, 'ticketid');

    if (count($ids) > 0) {
        $CI->db->where_in('ticketid', $ids);
        $_attachments = $CI->db->get(db_prefix() . 'ticket_attachments')->result_array();

        foreach ($_attachments as $_att) {
            array_push($attachments['ticket'], $_att);
        }
    }

    $has_permission_tasks_view = has_permission('tasks', '', 'view');
    $noPermissionQuery         = get_tasks_where_string(false);
    $CI->db->select('rel_id, id');
    $CI->db->where('rel_id', $id);
    $CI->db->where('rel_type', 'customer');

    if (!$has_permission_tasks_view) {
        $CI->db->where($noPermissionQuery);
    }

    $CI->db->from(db_prefix() . 'tasks');
    $tasks = $CI->db->get()->result_array();

    $ids = array_column($tasks, 'ticketid');
    if (count($ids) > 0) {
        $CI->db->where_in('rel_id', $ids);
        $CI->db->where('rel_type', 'task');

        $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();

        foreach ($_attachments as $_att) {
            array_push($attachments['task'], $_att);
        }
    }

    $CI->db->where('rel_id', $id);
    $CI->db->where('rel_type', 'customer');
    $client_main_attachments = $CI->db->get(db_prefix() . 'files')->result_array();

    $attachments['customer'] = $client_main_attachments;

    return hooks()->apply_filters('all_client_attachments', $attachments, $id);
}

/**
 * Used in customer profile vaults feature to determine if the vault should be shown for staff
 * @param  array $entries vault entries from database
 * @return array
 */
function _check_vault_entries_visibility($entries)
{
    $new = [];
    foreach ($entries as $entry) {
        if ($entry['visibility'] != 1) {
            if ($entry['visibility'] == 2 && !is_admin() && $entry['creator'] != get_staff_user_id()) {
                continue;
            } elseif ($entry['visibility'] == 3 && $entry['creator'] != get_staff_user_id() && !is_admin()) {
                continue;
            }
        }
        $new[] = $entry;
    }

    if (count($new) == 0) {
        $new = -1;
    }

    return $new;
}
/**
 * Default SQL select for selecting the company
 * @return string
 */
function get_sql_select_client_company()
{
    return 'CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM ' . db_prefix() . 'contacts WHERE userid = ' . db_prefix() . 'clients.userid and is_primary = 1) ELSE company END as company';
}

function can_logged_in_contact_change_language()
{
    if (!isset($GLOBALS['contact'])) {
        return false;
    }

    return $GLOBALS['contact']->is_primary == '1' && get_option('disable_language') == 0;
}
