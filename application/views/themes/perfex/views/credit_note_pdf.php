<?php defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . _l('credit_note_pdf_heading') . '</span><br />';
$info_right_column .= '<b style="color:#4e4e4e;"># ' . $credit_note_number . '</b>';

if (get_option('show_status_on_pdf_ei') == 1) {
    $info_right_column .= '<br /><span style="color:rgb(' . credit_note_status_color_pdf($credit_note->status) . ');text-transform:uppercase;">' . format_credit_note_status($credit_note->status, '', false) . '</span>';
}
// Add logo
$info_left_column .= pdf_logo_url();
// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(10);

$organization_info = '<div style="color:#424242;">';

$organization_info .= format_organization_info();

$organization_info .= '</div>';

// Bill to
$credit_note_info = '<b>' . _l('credit_note_bill_to') . '</b>';
$credit_note_info .= '<div style="color:#424242;">';
    $credit_note_info .= format_customer_info($credit_note, 'credit_note', 'billing');
$credit_note_info .= '</div>';

// ship to to
if ($credit_note->include_shipping == 1 && $credit_note->show_shipping_on_credit_note == 1) {
    $credit_note_info .= '<br /><b>' . _l('ship_to') . '</b>';
    $credit_note_info .= '<div style="color:#424242;">';
    $credit_note_info .= format_customer_info($credit_note, 'credit_note', 'shipping');
    $credit_note_info .= '</div>';
}

$credit_note_info .= '<br />' . _l('credit_note_date') . ': ' . _d($credit_note->date) . '<br />';

if (!empty($credit_note->reference_no)) {
    $credit_note_info .= _l('reference_no') . ': ' . $credit_note->reference_no . '<br />';
}

if ($credit_note->project_id != 0 && get_option('show_project_on_credit_note') == 1) {
    $credit_note_info .= _l('project') . ': ' . get_project_name_by_id($credit_note->project_id) . '<br />';
}

foreach ($pdf_custom_fields as $field) {
    $value = get_custom_field_value($credit_note->id, $field['id'], 'credit_note');
    if ($value == '') {
        continue;
    }
    $credit_note_info .= $field['name'] . ': ' . $value . '<br />';
}

$left_info  = $swap == '1' ? $credit_note_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $credit_note_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
$items = get_items_table_data($credit_note, 'credit_note', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->Ln(8);
$tbltotal = '';

$tbltotal .= '<table cellpadding="6" style="font-size:' . ($font_size + 4) . 'px">';
$tbltotal .= '
<tr>
    <td align="right" width="85%"><strong>' . _l('credit_note_subtotal') . '</strong></td>
    <td align="right" width="15%">' . app_format_money($credit_note->subtotal, $credit_note->currency_name) . '</td>
</tr>';

if (is_sale_discount_applied($credit_note)) {
    $tbltotal .= '
    <tr>
        <td align="right" width="85%"><strong>' . _l('credit_note_discount');
    if (is_sale_discount($credit_note, 'percent')) {
        $tbltotal .= '(' . app_format_number($credit_note->discount_percent, true) . '%)';
    }
    $tbltotal .= '</strong>';
    $tbltotal .= '</td>';
    $tbltotal .= '<td align="right" width="15%">-' . app_format_money($credit_note->discount_total, $credit_note->currency_name) . '</td>
    </tr>';
}

foreach ($items->taxes() as $tax) {
    $tbltotal .= '<tr>
    <td align="right" width="85%"><strong>' . $tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)' . '</strong></td>
    <td align="right" width="15%">' . app_format_money($tax['total_tax'], $credit_note->currency_name) . '</td>
</tr>';
}

if ((int) $credit_note->adjustment != 0) {
    $tbltotal .= '<tr>
    <td align="right" width="85%"><strong>' . _l('credit_note_adjustment') . '</strong></td>
    <td align="right" width="15%">' . app_format_money($credit_note->adjustment, $credit_note->currency_name) . '</td>
</tr>';
}

$tbltotal .= '
<tr style="background-color:#f0f0f0;">
    <td align="right" width="85%"><strong>' . _l('credit_note_total') . '</strong></td>
    <td align="right" width="15%">' . app_format_money($credit_note->total, $credit_note->currency_name) . '</td>
</tr>';

if ($credit_note->credits_used) {
    $tbltotal .= '
    <tr>
        <td align="right" width="85%"><strong>' . _l('credits_used') . '</strong></td>
        <td align="right" width="15%">' . '-' . app_format_money($credit_note->credits_used, $credit_note->currency_name) . '</td>
    </tr>';
}

if ($credit_note->total_refunds) {
    $tbltotal .= '
    <tr>
        <td align="right" width="85%"><strong>' . _l('refund') . '</strong></td>
        <td align="right" width="15%">' . '-' . app_format_money($credit_note->total_refunds, $credit_note->currency_name) . '</td>
    </tr>';
}

$tbltotal .= '
    <tr>
        <td align="right" width="85%"><strong>' . _l('credits_remaining') . '</strong></td>
        <td align="right" width="15%">' . app_format_money($credit_note->remaining_credits, $credit_note->currency_name) . '</td>
   </tr>';

$tbltotal .= '</table>';

$pdf->writeHTML($tbltotal, true, false, false, false, '');

if (get_option('total_to_words_enabled') == 1) {
    // Set the font bold
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('num_word') . ': ' . $CI->numberword->convert($credit_note->total, $credit_note->currency_name), 0, 1, 'C', 0, '', 0);
    // Set the font again to normal like the rest of the pdf
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(4);
}

if (!empty($credit_note->clientnote)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('credit_note_client_note'), 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $credit_note->clientnote, 0, 1, false, true, 'L', true);
}

if (!empty($credit_note->terms)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('terms_and_conditions'), 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $credit_note->terms, 0, 1, false, true, 'L', true);
}
