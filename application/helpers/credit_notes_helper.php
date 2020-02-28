<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Sum total credits applied for invoice
 * @param  mixed $id invoice id
 * @return mixed
 */
function total_credits_applied_to_invoice($id)
{
    $total = sum_from_table(db_prefix() . 'credits', ['field' => 'amount', 'where' => ['invoice_id' => $id]]);

    if ($total == 0) {
        return false;
    }

    return $total;
}

/**
 * Return credit note status color RGBA for pdf
 * @param  mixed $status_id current credit note status id
 * @return string
 */
function credit_note_status_color_pdf($status_id)
{
    $statusColor = '';

    if ($status_id == 1) {
        $statusColor = '3, 169, 244';
    } elseif ($status_id == 2) {
        $statusColor = '132, 197, 41';
    } else {
        // Status VOID
        $statusColor = '119, 119, 119';
    }

    return $statusColor;
}

/**
 * Return array with invoices IDs statuses which can be applied credits
 * @return array
 */
function invoices_statuses_available_for_credits()
{
    if (!class_exists('Invoices_model', false)) {
        get_instance()->load->model('invoices_model');
    }

    return hooks()->apply_filters('invoices_statuses_available_for_credits', [
        Invoices_model::STATUS_UNPAID,
        Invoices_model::STATUS_PARTIALLY,
        Invoices_model::STATUS_DRAFT,
        Invoices_model::STATUS_OVERDUE,
    ]);
}

/**
 * Check if credits can be applied to invoice based on the invoice status
 * @param  mixed $status_id invoice status id
 * @return boolean
 */
function credits_can_be_applied_to_invoice($status_id)
{
    return in_array($status_id, invoices_statuses_available_for_credits());
}

/**
 * Check if is last credit note created
 * @param  mixed  $id credit note id
 * @return boolean
 */
function is_last_credit_note($id)
{
    $CI = & get_instance();
    $CI->db->select('id')->from(db_prefix() . 'creditnotes')->order_by('id', 'desc')->limit(1);
    $query            = $CI->db->get();
    $last_credit_note = $query->row();

    if ($last_credit_note && $last_credit_note->id == $id) {
        return true;
    }

    return false;
}

/**
 * Function that format credit note number based on the prefix option and the credit note number
 * @param  mixed $id credit note id
 * @return string
 */
function format_credit_note_number($id)
{
    $CI = & get_instance();
    $CI->db->select('date,number,prefix,number_format')
    ->from(db_prefix() . 'creditnotes')
    ->where('id', $id);
    $credit_note = $CI->db->get()->row();

    if (!$credit_note) {
        return '';
    }

    $number = sales_number_format($credit_note->number, $credit_note->number_format, $credit_note->prefix, $credit_note->date);

    return hooks()->apply_filters('format_credit_note_number', $number, [
        'id'          => $id,
        'credit_note' => $credit_note,
    ]);
}

/**
 * Format credit note status
 * @param  mixed  $status credit note current status
 * @param  boolean $text   to return text or with applied styles
 * @return string
 */
function format_credit_note_status($status, $text = false)
{
    $CI = &get_instance();
    if (!class_exists('credit_notes_model')) {
        $CI->load->model('credit_notes_model');
    }

    $statuses    = $CI->credit_notes_model->get_statuses();
    $statusArray = false;
    foreach ($statuses as $s) {
        if ($s['id'] == $status) {
            $statusArray = $s;

            break;
        }
    }

    if (!$statusArray) {
        return $status;
    }

    if ($text) {
        return $statusArray['name'];
    }

    $style = 'border: 1px solid ' . $statusArray['color'] . ';color:' . $statusArray['color'] . ';';
    $class = 'label s-status';

    return '<span class="' . $class . '" style="' . $style . '">' . $statusArray['name'] . '</span>';
}

/**
 * Function that return credit note item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_credit_note_item_taxes($itemid)
{
    $CI = & get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'credit_note');
    $taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
    $i     = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}
