<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get invoice total left for paying if not payments found the original total from the invoice will be returned
 * @since  Version 1.0.1
 * @param  mixed $id     invoice id
 * @param  mixed $invoice_total
 * @return mixed  total left
 */
function get_invoice_total_left_to_pay($id, $invoice_total = null)
{
    $CI = & get_instance();

    if ($invoice_total === null) {
        $CI->db->select('total')
        ->where('id', $id);
        $invoice_total = $CI->db->get(db_prefix() . 'invoices')->row()->total;
    }

    if (!class_exists('payments_model')) {
        $CI->load->model('payments_model');
    }

    if (!class_exists('credit_notes_model')) {
        $CI->load->model('credit_notes_model');
    }

    $payments = $CI->payments_model->get_invoice_payments($id);
    $credits  = $CI->credit_notes_model->get_applied_invoice_credits($id);

    $payments = array_merge($payments, $credits);

    $totalPayments = 0;

    $bcadd = function_exists('bcadd');

    foreach ($payments as $payment) {
        if ($bcadd) {
            $totalPayments = bcadd($totalPayments, $payment['amount'], get_decimal_places());
        } else {
            $totalPayments += $payment['amount'];
        }
    }

    if (function_exists('bcsub')) {
        return bcsub($invoice_total, $totalPayments, get_decimal_places());
    }

    return number_format($invoice_total - $totalPayments, get_decimal_places(), '.', '');
}

/**
 * Check if invoice email template for overdue notices is enabled
 * @return boolean
 */
function is_invoices_email_overdue_notice_enabled()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'invoice-overdue-notice', 'active' => 1]) > 0;
}

/**
 * Check if there are sources for sending invoice overdue notices
 * Will be either email or SMS
 * @return boolean
 */
function is_invoices_overdue_reminders_enabled()
{
    return is_invoices_email_overdue_notice_enabled() || is_sms_trigger_active(SMS_TRIGGER_INVOICE_OVERDUE);
}

/**
 * Check invoice restrictions - hash, clientid
 * @since  Version 1.0.1
 * @param  mixed $id   invoice id
 * @param  string $hash invoice hash
 */
function check_invoice_restrictions($id, $hash)
{
    $CI = & get_instance();
    $CI->load->model('invoices_model');
    if (!$hash || !$id) {
        show_404();
    }
    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_invoice_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }
    $invoice = $CI->invoices_model->get($id);
    if (!$invoice || ($invoice->hash != $hash)) {
        show_404();
    }

    // Do one more check
    if (!is_staff_logged_in()) {
        if (get_option('view_invoice_only_logged_in') == 1) {
            if ($invoice->clientid != get_client_user_id()) {
                show_404();
            }
        }
    }
}

/**
 * Format invoice status
 * @param  integer  $status
 * @param  string  $classes additional classes
 * @param  boolean $label   To include in html label or not
 * @return mixed
 */
function format_invoice_status($status, $classes = '', $label = true)
{
    if (!class_exists('Invoices_model', false)) {
        get_instance()->load->model('invoices_model');
    }

    $id          = $status;
    $label_class = get_invoice_status_label($status);
    if ($status == Invoices_model::STATUS_UNPAID) {
        $status = _l('invoice_status_unpaid');
    } elseif ($status == Invoices_model::STATUS_PAID) {
        $status = _l('invoice_status_paid');
    } elseif ($status == Invoices_model::STATUS_PARTIALLY) {
        $status = _l('invoice_status_not_paid_completely');
    } elseif ($status == Invoices_model::STATUS_OVERDUE) {
        $status = _l('invoice_status_overdue');
    } elseif ($status == Invoices_model::STATUS_CANCELLED) {
        $status = _l('invoice_status_cancelled');
    } else {
        // status 6
        $status = _l('invoice_status_draft');
    }
    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-status invoice-status-' . $id . '">' . $status . '</span>';
    }

    return $status;
}
/**
 * Return invoice status label class baed on twitter bootstrap classses
 * @param  mixed $status invoice status id
 * @return string
 */
function get_invoice_status_label($status)
{
    if (!class_exists('Invoices_model', false)) {
        get_instance()->load->model('invoices_model');
    }

    $label_class = '';
    if ($status == Invoices_model::STATUS_UNPAID) {
        $label_class = 'danger';
    } elseif ($status == Invoices_model::STATUS_PAID) {
        $label_class = 'success';
    } elseif ($status == Invoices_model::STATUS_PARTIALLY) {
        $label_class = 'warning';
    } elseif ($status == Invoices_model::STATUS_OVERDUE) {
        $label_class = 'warning';
    } elseif ($status == Invoices_model::STATUS_CANCELLED || $status == Invoices_model::STATUS_DRAFT) {
        $label_class = 'default';
    } else {
        if (!is_numeric($status)) {
            if ($status == 'not_sent') {
                $label_class = 'default';
            }
        }
    }

    return $label_class;
}

/**
 * Function used in invoice PDF, this function will return RGBa color for PDF dcouments
 * @param  mixed $status_id current invoice status id
 * @return string
 */
function invoice_status_color_pdf($status_id)
{
    $statusColor = '';

    if (!class_exists('Invoices_model', false)) {
        get_instance()->load->model('invoices_model');
    }

    if ($status_id == Invoices_model::STATUS_UNPAID) {
        $statusColor = '252, 45, 66';
    } elseif ($status_id == Invoices_model::STATUS_PAID) {
        $statusColor = '0, 191, 54';
    } elseif ($status_id == Invoices_model::STATUS_PARTIALLY) {
        $statusColor = '255, 111, 0';
    } elseif ($status_id == Invoices_model::STATUS_OVERDUE) {
        $statusColor = '255, 111, 0';
    } elseif ($status_id == Invoices_model::STATUS_CANCELLED || $status_id == Invoices_model::STATUS_DRAFT) {
        $statusColor = '114, 123, 144';
    }

    return $statusColor;
}

/**
 * Update invoice status
 * @param  mixed $id invoice id
 * @return mixed invoice updates status / if no update return false
 * @return boolean $prevent_logging do not log changes if the status is updated for the invoice activity log
 */
function update_invoice_status($id, $force_update = false, $prevent_logging = false)
{
    $CI = & get_instance();

    $CI->load->model('invoices_model');
    $invoice = $CI->invoices_model->get($id);

    $original_status = $invoice->status;

    if (($original_status == Invoices_model::STATUS_DRAFT && $force_update == false)
        || ($original_status == Invoices_model::STATUS_CANCELLED && $force_update == false)) {
        return false;
    }

    $CI->db->select('amount')
    ->where('invoiceid', $id)
    ->order_by(db_prefix() . 'invoicepaymentrecords.id', 'asc');
    $payments = $CI->db->get(db_prefix() . 'invoicepaymentrecords')->result_array();

    if (!class_exists('credit_notes_model')) {
        $CI->load->model('credit_notes_model');
    }

    $credits = $CI->credit_notes_model->get_applied_invoice_credits($id);
    // Merge credits applied with payments, credits in this function are casted as payments directly to invoice
    // This merge will help to update the status
    $payments = array_merge($payments, $credits);

    $totalPayments = [];
    $status        = Invoices_model::STATUS_UNPAID;

    // Check if the first payments is equal to invoice total
    if (isset($payments[0])) {
        if ($payments[0]['amount'] == $invoice->total) {
            // Paid status
            $status = Invoices_model::STATUS_PAID;
        } else {
            foreach ($payments as $payment) {
                array_push($totalPayments, $payment['amount']);
            }

            $totalPayments = array_sum($totalPayments);

            if ((function_exists('bccomp')
                ?  bccomp($invoice->total, $totalPayments, get_decimal_places()) === 0
                || bccomp($invoice->total, $totalPayments, get_decimal_places()) === -1
                : number_format(($invoice->total - $totalPayments), get_decimal_places(), '.', '') == '0')
                || $totalPayments > $invoice->total) {
                // Paid status
                $status = Invoices_model::STATUS_PAID;
            } elseif ($totalPayments == 0) {
                // Unpaid status
                $status = Invoices_model::STATUS_UNPAID;
            } else {
                if ($invoice->duedate != null) {
                    if ($totalPayments > 0) {
                        // Not paid completely status
                        $status = Invoices_model::STATUS_PARTIALLY;
                    } elseif (date('Y-m-d', strtotime($invoice->duedate)) < date('Y-m-d')) {
                        $status = Invoices_model::STATUS_OVERDUE;
                    }
                } else {
                    // Not paid completely status
                    $status = Invoices_model::STATUS_PARTIALLY;
                }
            }
        }
    } else {
        if ($invoice->total == 0) {
            $status = Invoices_model::STATUS_PAID;
        } else {
            if ($invoice->duedate != null) {
                if (date('Y-m-d', strtotime($invoice->duedate)) < date('Y-m-d')) {
                    // Overdue status
                    $status = Invoices_model::STATUS_OVERDUE;
                }
            }
        }
    }

    $CI->db->where('id', $id);
    $CI->db->update(db_prefix() . 'invoices', [
        'status' => $status,
    ]);

    if ($CI->db->affected_rows() > 0) {
        hooks()->do_action('invoice_status_changed', ['invoice_id' => $id, 'status' => $status]);
        if ($prevent_logging == true) {
            return $status;
        }

        $log = 'Invoice Status Updated [Invoice Number: ' . format_invoice_number($invoice->id) . ', From: ' . format_invoice_status($original_status, '', false) . ' To: ' . format_invoice_status($status, '', false) . ']';

        log_activity($log, null);

        $additional_activity = serialize([
            '<original_status>' . $original_status . '</original_status>',
            '<new_status>' . $status . '</new_status>',
        ]);

        $CI->invoices_model->log_invoice_activity($invoice->id, 'invoice_activity_status_updated', false, $additional_activity);

        return $status;
    }

    return false;
}


/**
 * Check if the invoice id is last invoice
 * @param  mixed  $id invoice id
 * @return boolean
 */
function is_last_invoice($id)
{
    $CI = & get_instance();
    $CI->db->select('id')->from(db_prefix() . 'invoices')->order_by('id', 'desc')->limit(1);
    $query           = $CI->db->get();
    $last_invoice_id = $query->row()->id;
    if ($last_invoice_id == $id) {
        return true;
    }

    return false;
}

/**
 * Format invoice number based on description
 * @param  mixed $id
 * @return string
 */
function format_invoice_number($id)
{
    $CI = & get_instance();
    $CI->db->select('date,number,prefix,number_format')->from(db_prefix() . 'invoices')->where('id', $id);
    $invoice = $CI->db->get()->row();

    if (!$invoice) {
        return '';
    }

    $number = sales_number_format($invoice->number, $invoice->number_format, $invoice->prefix, $invoice->date);

    return hooks()->apply_filters('format_invoice_number', $number, [
        'id'      => $id,
        'invoice' => $invoice,
    ]);
}

/**
 * Function that return invoice item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_invoice_item_taxes($itemid)
{
    $CI = & get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'invoice');
    $taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
    $i     = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}

/**
 * Check if payment mode is allowed for specific invoice
 * @param  mixed  $id payment mode id
 * @param  mixed  $invoiceid invoice id
 * @return boolean
 */
function is_payment_mode_allowed_for_invoice($id, $invoiceid)
{
    $CI = & get_instance();
    $CI->db->select('' . db_prefix() . 'currencies.name as currency_name,allowed_payment_modes')->from(db_prefix() . 'invoices')->join(db_prefix() . 'currencies', '' . db_prefix() . 'currencies.id = ' . db_prefix() . 'invoices.currency', 'left')->where(db_prefix() . 'invoices.id', $invoiceid);
    $invoice       = $CI->db->get()->row();
    $allowed_modes = $invoice->allowed_payment_modes;
    if (!is_null($allowed_modes)) {
        $allowed_modes = unserialize($allowed_modes);
        if (count($allowed_modes) == 0) {
            return false;
        }
        foreach ($allowed_modes as $mode) {
            if ($mode == $id) {
                // is offline payment mode
                if (is_numeric($id)) {
                    return true;
                }
                // check currencies
                $currencies = explode(',', get_option('paymentmethod_' . $id . '_currencies'));
                foreach ($currencies as $currency) {
                    $currency = trim($currency);
                    if (mb_strtoupper($currency) == mb_strtoupper($invoice->currency_name)) {
                        return true;
                    }
                }

                return false;
            }
        }
    } else {
        return false;
    }

    return false;
}
/**
 * Check if invoice mode exists in invoice
 * @since  Version 1.0.1
 * @param  array  $modes     all invoice modes
 * @param  mixed  $invoiceid invoice id
 * @param  boolean $offline   should check offline or online modes
 * @return boolean
 */
function found_invoice_mode($modes, $invoiceid, $offline = true, $show_on_pdf = false)
{
    $CI = & get_instance();
    $CI->db->select('' . db_prefix() . 'currencies.name as currency_name,allowed_payment_modes')->from(db_prefix() . 'invoices')->join(db_prefix() . 'currencies', '' . db_prefix() . 'currencies.id = ' . db_prefix() . 'invoices.currency', 'left')->where(db_prefix() . 'invoices.id', $invoiceid);
    $invoice = $CI->db->get()->row();
    if (!is_null($invoice->allowed_payment_modes)) {
        $invoice->allowed_payment_modes = unserialize($invoice->allowed_payment_modes);
        if (count($invoice->allowed_payment_modes) == 0) {
            return false;
        }
        foreach ($modes as $mode) {
            if ($offline == true) {
                if (is_numeric($mode['id']) && is_array($invoice->allowed_payment_modes)) {
                    foreach ($invoice->allowed_payment_modes as $allowed_mode) {
                        if ($allowed_mode == $mode['id']) {
                            if ($show_on_pdf == false) {
                                return true;
                            }
                            if ($mode['show_on_pdf'] == 1) {
                                return true;
                            }

                            return false;
                        }
                    }
                }
            } else {
                if (!is_numeric($mode['id']) && !empty($mode['id'])) {
                    foreach ($invoice->allowed_payment_modes as $allowed_mode) {
                        if ($allowed_mode == $mode['id']) {
                            // Check for currencies
                            $currencies = explode(',', get_option('paymentmethod_' . $mode['id'] . '_currencies'));
                            foreach ($currencies as $currency) {
                                $currency = trim($currency);
                                if (strtoupper($currency) == strtoupper($invoice->currency_name)) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return false;
}

/**
 * This function do not work with cancelled status
 * Calculate invoices percent by status
 * @param  mixed $status          estimate status
 * @param  mixed $total_invoices in case the total is calculated in other place
 * @return array
 */
function get_invoices_percent_by_status($status)
{
    $has_permission_view = has_permission('invoices', '', 'view');
    $total_invoices      = total_rows(db_prefix() . 'invoices', 'status NOT IN(5)' . (!$has_permission_view ? ' AND (' . get_invoices_where_sql_for_staff(get_staff_user_id()) . ')' : ''));

    $data            = [];
    $total_by_status = 0;
    if (!is_numeric($status)) {
        if ($status == 'not_sent') {
            $total_by_status = total_rows(db_prefix() . 'invoices', 'sent=0 AND status NOT IN(2,5)' . (!$has_permission_view ? ' AND (' . get_invoices_where_sql_for_staff(get_staff_user_id()) . ')' : ''));
        }
    } else {
        $total_by_status = total_rows(db_prefix() . 'invoices', 'status = ' . $status . ' AND status NOT IN(5)' . (!$has_permission_view ? ' AND (' . get_invoices_where_sql_for_staff(get_staff_user_id()) . ')' : ''));
    }
    $percent                 = ($total_invoices > 0 ? number_format(($total_by_status * 100) / $total_invoices, 2) : 0);
    $data['total_by_status'] = $total_by_status;
    $data['percent']         = $percent;
    $data['total']           = $total_invoices;

    return $data;
}
/**
 * Check if staff member have assigned invoices / added as sale agent
 * @param  mixed $staff_id staff id to check
 * @return boolean
 */
function staff_has_assigned_invoices($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-invoices-' . $staff_id);

    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix() . 'invoices', ['sale_agent' => $staff_id]);
        $CI->app_object_cache->add('staff-total-assigned-invoices-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}

/**
 * Load invoices total templates
 * This is the template where is showing the panels Outstanding Invoices, Paid Invoices and Past Due invoices
 * @return string
 */
function load_invoices_total_template()
{
    $CI = &get_instance();
    $CI->load->model('invoices_model');
    $_data = $CI->input->post();
    if (!$CI->input->post('customer_id')) {
        $multiple_currencies = call_user_func('is_using_multiple_currencies');
    } else {
        $_data['customer_id'] = $CI->input->post('customer_id');
        $multiple_currencies  = call_user_func('is_client_using_multiple_currencies', $CI->input->post('customer_id'));
    }

    if ($CI->input->post('project_id')) {
        $_data['project_id'] = $CI->input->post('project_id');
    }

    if ($multiple_currencies) {
        $CI->load->model('currencies_model');
        $data['invoices_total_currencies'] = $CI->currencies_model->get();
    }

    $data['invoices_years'] = $CI->invoices_model->get_invoices_years();

    if (count($data['invoices_years']) >= 1 && $data['invoices_years'][0]['year'] != date('Y')) {
        array_unshift($data['invoices_years'], ['year' => date('Y')]);
    }

    $data['total_result'] = $CI->invoices_model->get_invoices_total($_data);
    $data['_currency']    = $data['total_result']['currencyid'];

    $CI->load->view('admin/invoices/invoices_total_template', $data);
}

function get_invoices_where_sql_for_staff($staff_id)
{
    $has_permission_view_own            = has_permission('invoices', '', 'view_own');
    $allow_staff_view_invoices_assigned = get_option('allow_staff_view_invoices_assigned');
    $whereUser                          = '';
    if ($has_permission_view_own) {
        $whereUser = '((' . db_prefix() . 'invoices.addedfrom=' . $staff_id . ' AND ' . db_prefix() . 'invoices.addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature = "invoices" AND capability="view_own"))';
        if ($allow_staff_view_invoices_assigned == 1) {
            $whereUser .= ' OR sale_agent=' . $staff_id;
        }
        $whereUser .= ')';
    } else {
        $whereUser .= 'sale_agent=' . $staff_id;
    }

    return $whereUser;
}

/**
 * Check if staff member can view invoice
 * @param  mixed $id invoice id
 * @param  mixed $staff_id
 * @return boolean
 */
function user_can_view_invoice($id, $staff_id = false)
{
    $CI = &get_instance();

    $staff_id = $staff_id ? $staff_id : get_staff_user_id();

    if (has_permission('invoices', $staff_id, 'view')) {
        return true;
    }

    $CI->db->select('id, addedfrom, sale_agent');
    $CI->db->from(db_prefix() . 'invoices');
    $CI->db->where('id', $id);
    $invoice = $CI->db->get()->row();

    if ((has_permission('invoices', $staff_id, 'view_own') && $invoice->addedfrom == $staff_id)
            || ($invoice->sale_agent == $staff_id && get_option('allow_staff_view_invoices_assigned') == '1')) {
        return true;
    }

    return false;
}
