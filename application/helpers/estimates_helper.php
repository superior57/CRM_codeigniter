<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Check estimate restrictions - hash, clientid
 * @param  mixed $id   estimate id
 * @param  string $hash estimate hash
 */
function check_estimate_restrictions($id, $hash)
{
    $CI = & get_instance();
    $CI->load->model('estimates_model');
    if (!$hash || !$id) {
        show_404();
    }
    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_estimate_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }
    $estimate = $CI->estimates_model->get($id);
    if (!$estimate || ($estimate->hash != $hash)) {
        show_404();
    }
    // Do one more check
    if (!is_staff_logged_in()) {
        if (get_option('view_estimate_only_logged_in') == 1) {
            if ($estimate->clientid != get_client_user_id()) {
                show_404();
            }
        }
    }
}

/**
 * Check if estimate email template for expiry reminders is enabled
 * @return boolean
 */
function is_estimates_email_expiry_reminder_enabled()
{
    return total_rows(db_prefix().'emailtemplates', ['slug' => 'estimate-expiry-reminder', 'active' => 1]) > 0;
}

/**
 * Check if there are sources for sending estimate expiry reminders
 * Will be either email or SMS
 * @return boolean
 */
function is_estimates_expiry_reminders_enabled()
{
    return is_estimates_email_expiry_reminder_enabled() || is_sms_trigger_active(SMS_TRIGGER_ESTIMATE_EXP_REMINDER);
}

/**
 * Return RGBa estimate status color for PDF documents
 * @param  mixed $status_id current estimate status
 * @return string
 */
function estimate_status_color_pdf($status_id)
{
    if ($status_id == 1) {
        $statusColor = '119, 119, 119';
    } elseif ($status_id == 2) {
        // Sent
        $statusColor = '3, 169, 244';
    } elseif ($status_id == 3) {
        //Declines
        $statusColor = '252, 45, 66';
    } elseif ($status_id == 4) {
        //Accepted
        $statusColor = '0, 191, 54';
    } else {
        // Expired
        $statusColor = '255, 111, 0';
    }

    return $statusColor;
}

/**
 * Format estimate status
 * @param  integer  $status
 * @param  string  $classes additional classes
 * @param  boolean $label   To include in html label or not
 * @return mixed
 */
function format_estimate_status($status, $classes = '', $label = true)
{
    $id          = $status;
    $label_class = estimate_status_color_class($status);
    $status      = estimate_status_by_id($status);
    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-status estimate-status-' . $id . ' estimate-status-' . $label_class . '">' . $status . '</span>';
    }

    return $status;
}

/**
 * Return estimate status translated by passed status id
 * @param  mixed $id estimate status id
 * @return string
 */
function estimate_status_by_id($id)
{
    $status = '';
    if ($id == 1) {
        $status = _l('estimate_status_draft');
    } elseif ($id == 2) {
        $status = _l('estimate_status_sent');
    } elseif ($id == 3) {
        $status = _l('estimate_status_declined');
    } elseif ($id == 4) {
        $status = _l('estimate_status_accepted');
    } elseif ($id == 5) {
        // status 5
        $status = _l('estimate_status_expired');
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $status = _l('not_sent_indicator');
            }
        }
    }

    return hooks()->apply_filters('estimate_status_label', $status, $id);
}

/**
 * Return estimate status color class based on twitter bootstrap
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function estimate_status_color_class($id, $replace_default_by_muted = false)
{
    $class = '';
    if ($id == 1) {
        $class = 'default';
        if ($replace_default_by_muted == true) {
            $class = 'muted';
        }
    } elseif ($id == 2) {
        $class = 'info';
    } elseif ($id == 3) {
        $class = 'danger';
    } elseif ($id == 4) {
        $class = 'success';
    } elseif ($id == 5) {
        // status 5
        $class = 'warning';
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $class = 'default';
                if ($replace_default_by_muted == true) {
                    $class = 'muted';
                }
            }
        }
    }

    return hooks()->apply_filters('estimate_status_color_class', $class, $id);
}

/**
 * Check if the estimate id is last invoice
 * @param  mixed  $id estimateid
 * @return boolean
 */
function is_last_estimate($id)
{
    $CI = & get_instance();
    $CI->db->select('id')->from(db_prefix().'estimates')->order_by('id', 'desc')->limit(1);
    $query            = $CI->db->get();
    $last_estimate_id = $query->row()->id;
    if ($last_estimate_id == $id) {
        return true;
    }

    return false;
}

/**
 * Format estimate number based on description
 * @param  mixed $id
 * @return string
 */
function format_estimate_number($id)
{
    $CI = & get_instance();
    $CI->db->select('date,number,prefix,number_format')->from(db_prefix().'estimates')->where('id', $id);
    $estimate = $CI->db->get()->row();

    if (!$estimate) {
        return '';
    }

    $number = sales_number_format($estimate->number, $estimate->number_format, $estimate->prefix, $estimate->date);

    return hooks()->apply_filters('format_estimate_number', $number, [
        'id'       => $id,
        'estimate' => $estimate,
    ]);
}


/**
 * Function that return estimate item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_estimate_item_taxes($itemid)
{
    $CI = & get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'estimate');
    $taxes = $CI->db->get(db_prefix().'item_tax')->result_array();
    $i     = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}

/**
 * Calculate estimates percent by status
 * @param  mixed $status          estimate status
 * @return array
 */
function get_estimates_percent_by_status($status, $project_id = null)
{
    $has_permission_view = has_permission('estimates', '', 'view');
    $where               = '';

    if (isset($project_id)) {
        $where .= 'project_id=' . $project_id . ' AND ';
    }
    if (!$has_permission_view) {
        $where .= get_estimates_where_sql_for_staff(get_staff_user_id());
    }

    $where = trim($where);

    if (endsWith($where, ' AND')) {
        $where = substr_replace($where, '', -3);
    }

    $total_estimates = total_rows(db_prefix().'estimates', $where);

    $data            = [];
    $total_by_status = 0;

    if (!is_numeric($status)) {
        if ($status == 'not_sent') {
            $total_by_status = total_rows(db_prefix().'estimates', 'sent=0 AND status NOT IN(2,3,4)' . ($where != '' ? ' AND (' . $where . ')' : ''));
        }
    } else {
        $whereByStatus = 'status=' . $status;
        if ($where != '') {
            $whereByStatus .= ' AND (' . $where . ')';
        }
        $total_by_status = total_rows(db_prefix().'estimates', $whereByStatus);
    }

    $percent                 = ($total_estimates > 0 ? number_format(($total_by_status * 100) / $total_estimates, 2) : 0);
    $data['total_by_status'] = $total_by_status;
    $data['percent']         = $percent;
    $data['total']           = $total_estimates;

    return $data;
}

function get_estimates_where_sql_for_staff($staff_id)
{
    $has_permission_view_own             = has_permission('estimates', '', 'view_own');
    $allow_staff_view_estimates_assigned = get_option('allow_staff_view_estimates_assigned');
    $whereUser                           = '';
    if ($has_permission_view_own) {
        $whereUser = '(('.db_prefix().'estimates.addedfrom=' . $staff_id . ' AND '.db_prefix().'estimates.addedfrom IN (SELECT staff_id FROM '.db_prefix().'staff_permissions WHERE feature = "estimates" AND capability="view_own"))';
        if ($allow_staff_view_estimates_assigned == 1) {
            $whereUser .= ' OR sale_agent=' . $staff_id;
        }
        $whereUser .= ')';
    } else {
        $whereUser .= 'sale_agent=' . $staff_id;
    }

    return $whereUser;
}
/**
 * Check if staff member have assigned estimates / added as sale agent
 * @param  mixed $staff_id staff id to check
 * @return boolean
 */
function staff_has_assigned_estimates($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-estimates-' . $staff_id);

    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix().'estimates', ['sale_agent' => $staff_id]);
        $CI->app_object_cache->add('staff-total-assigned-estimates-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}
/**
 * Check if staff member can view estimate
 * @param  mixed $id estimate id
 * @param  mixed $staff_id
 * @return boolean
 */
function user_can_view_estimate($id, $staff_id = false)
{
    $CI = &get_instance();

    $staff_id = $staff_id ? $staff_id : get_staff_user_id();

    if (has_permission('estimates', $staff_id, 'view')) {
        return true;
    }

    $CI->db->select('id, addedfrom, sale_agent');
    $CI->db->from(db_prefix().'estimates');
    $CI->db->where('id', $id);
    $estimate = $CI->db->get()->row();

    if ((has_permission('estimates', $staff_id, 'view_own') && $estimate->addedfrom == $staff_id)
            || ($estimate->sale_agent == $staff_id && get_option('allow_staff_view_estimates_assigned') == '1')) {
        return true;
    }

    return false;
}
