<?php

defined('BASEPATH') or exit('No direct script access allowed');

hooks()->add_filter('before_invoice_updated', '_format_data_sales_feature');
hooks()->add_filter('before_invoice_added', '_format_data_sales_feature');

hooks()->add_filter('before_estimate_updated', '_format_data_sales_feature');
hooks()->add_filter('before_estimate_added', '_format_data_sales_feature');

hooks()->add_filter('before_create_credit_note', '_format_data_sales_feature');
hooks()->add_filter('before_update_credit_note', '_format_data_sales_feature');

hooks()->add_filter('before_create_proposal', '_format_data_sales_feature');
hooks()->add_filter('before_proposal_updated', '_format_data_sales_feature');

hooks()->add_filter('before_client_added', '_format_data_client');
hooks()->add_filter('before_client_updated', '_format_data_client', 10, 2);
hooks()->add_filter('before_update_contact', '_format_data_client', 10, 2);
hooks()->add_filter('before_create_contact', '_format_data_client');

/**
 * Remove and format some common used data for the sales feature eq invoice,estimates etc..
 * @param  array $data $_POST data
 * @return array
 */
function _format_data_sales_feature($data)
{
    foreach (_get_sales_feature_unused_names() as $u) {
        if (isset($data['data'][$u])) {
            unset($data['data'][$u]);
        }
    }

    if (isset($data['data']['date'])) {
        $data['data']['date'] = to_sql_date($data['data']['date']);
    }

    if (isset($data['data']['open_till'])) {
        $data['data']['open_till'] = to_sql_date($data['data']['open_till']);
    }

    if (isset($data['data']['expirydate'])) {
        $data['data']['expirydate'] = to_sql_date($data['data']['expirydate']);
    }

    if (isset($data['data']['duedate'])) {
        $data['data']['duedate'] = to_sql_date($data['data']['duedate']);
    }

    if (isset($data['data']['clientnote'])) {
        $data['data']['clientnote'] = nl2br_save_html($data['data']['clientnote']);
    }

    if (isset($data['data']['terms'])) {
        $data['data']['terms'] = nl2br_save_html($data['data']['terms']);
    }

    if (isset($data['data']['adminnote'])) {
        $data['data']['adminnote'] = nl2br($data['data']['adminnote']);
    }

    if ((isset($data['data']['adjustment']) && !is_numeric($data['data']['adjustment'])) || !isset($data['data']['adjustment'])) {
        $data['data']['adjustment'] = 0;
    } elseif (isset($data['data']['adjustment']) && is_numeric($data['data']['adjustment'])) {
        $data['data']['adjustment'] = number_format($data['data']['adjustment'], get_decimal_places(), '.', '');
    }

    if (isset($data['data']['discount_total']) && $data['data']['discount_total'] == 0) {
        $data['data']['discount_type'] = '';
    }

    foreach (['country', 'billing_country', 'shipping_country', 'project_id', 'sale_agent'] as $should_be_zero) {
        if (isset($data['data'][$should_be_zero]) && $data['data'][$should_be_zero] == '') {
            $data['data'][$should_be_zero] = 0;
        }
    }

    return $data;
}

function _format_data_client($data, $id = null)
{
    foreach (_get_client_unused_names() as $u) {
        if (isset($data[$u])) {
            unset($data[$u]);
        }
    }

    if (isset($data['address'])) {
        $data['address'] = trim($data['address']);
        $data['address'] = nl2br($data['address']);
    }

    if (isset($data['billing_street'])) {
        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);
    }

    if (isset($data['shipping_street'])) {
        $data['shipping_street'] = trim($data['shipping_street']);
        $data['shipping_street'] = nl2br($data['shipping_street']);
    }

    return $data;
}
/**
 * Unsed $_POST request names, mostly they are used as helper inputs in the form
 * The top function will check all of them and unset from the $data
 * @return array
 */
function _get_sales_feature_unused_names()
{
    return [
        'taxname', 'description',
        'currency_symbol', 'price',
        'isedit', 'taxid',
        'long_description', 'unit',
        'rate', 'quantity',
        'item_select', 'tax',
        'billed_tasks', 'billed_expenses',
        'task_select', 'task_id',
        'expense_id', 'repeat_every_custom',
        'repeat_type_custom', 'bill_expenses',
        'save_and_send', 'merge_current_invoice',
        'cancel_merged_invoices', 'invoices_to_merge',
        'tags', 's_prefix', 'save_and_record_payment',
    ];
}

function _get_client_unused_names()
{
    return [
        'fakeusernameremembered', 'fakepasswordremembered',
        'DataTables_Table_0_length', 'DataTables_Table_1_length',
        'onoffswitch', 'passwordr', 'permissions', 'send_set_password_email',
        'donotsendwelcomeemail',
    ];
}
