<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Load PDF language for client
 * This is used eq if client have different language the system default language so in this case the PDF document
 * will be on client language not on system language
 * @param  mixed $clientid client id
 * @return null
 */
function load_pdf_language($clientid)
{
    $CI = & get_instance();

    $language = get_option('active_language');

    $clientLanguage = get_client_default_language($clientid);

    // When cron or email sending pdf document the pdfs need to be on the client language
    if (is_data_for_customer() || DEFINED('CRON')) {
        if (!empty($clientLanguage)) {
            $language = $clientLanguage;
        }
    } else {
        if (get_option('output_client_pdfs_from_admin_area_in_client_language') == 1) {
            if (!empty($clientLanguage)) {
                $language = $clientLanguage;
            }
        }
    }

    if (file_exists(APPPATH . 'language/' . $language)) {
        $CI->lang->load($language . '_lang', $language);
    }

    if (file_exists(APPPATH . 'language/' . $language . '/custom_lang.php')) {
        $CI->lang->load('custom_lang', $language);
    }

    hooks()->do_action('load_pdf_language', ['language' => $language, 'client_id' => $clientid]);
}

/**
 * Fetches custom pdf logo url for pdf or use the default logo uploaded for the company
 * Additional statements applied because this function wont work on all servers. All depends how the server is configured.
 * @return string
 */
function pdf_logo_url()
{
    $custom_pdf_logo_image_url = get_option('custom_pdf_logo_image_url');
    $width                     = get_option('pdf_logo_width');
    $logoUrl                   = '';

    if ($width == '') {
        $width = 120;
    }
    if ($custom_pdf_logo_image_url != '') {
        $logoUrl = $custom_pdf_logo_image_url;
    } else {
        if (get_option('company_logo_dark') != '' && file_exists(get_upload_path_by_type('company') . get_option('company_logo_dark'))) {
            $logoUrl = get_upload_path_by_type('company') . get_option('company_logo_dark');
        } elseif (get_option('company_logo') != '' && file_exists(get_upload_path_by_type('company') . get_option('company_logo'))) {
            $logoUrl = get_upload_path_by_type('company') . get_option('company_logo');
        }
    }

    $logoImage = '';
    if ($logoUrl != '') {
        $logoImage = '<img width="' . $width . 'px" src="' . $logoUrl . '">';
    }

    return hooks()->apply_filters('pdf_logo_url', $logoImage);
}

/**
 * Get available fonts for PDF
 * @return mixed
 */
function get_pdf_fonts_list()
{
    static $fontlist = null;
    if (!$fontlist) {
        $fontlist = [];
        if (($fontsdir = opendir(TCPDF_FONTS::_getfontpath())) !== false) {
            while (($file = readdir($fontsdir)) !== false) {
                if (substr($file, -4) == '.php') {
                    $name = strtolower(basename($file, '.php'));
                    // Exclude ITALIC Fonts because are causing issue when they are set directly.
                    // Not sure if they work fine if it's set manually.
                    if(!endsWith($name, 'i')) {
                        array_push($fontlist, $name);
                    }
                }
            }
            closedir($fontsdir);
        }
    }

    return hooks()->apply_filters('pdf_fonts_list', $fontlist);
}
/**
 * Set constant for sending mail template
 * Used to identify if the custom fields should be shown and loading the PDF language
 */
function set_mailing_constant()
{
    if (!defined('SEND_MAIL_TEMPLATE')) {
        define('SEND_MAIL_TEMPLATE', true);
    }
}
/**
 * Get PDF format page
 * Based on the options will return the formatted string that will be used in the PDF library
 * @param  string $option_name
 * @return array
 */
function get_pdf_format($option_name)
{
    $oFormat = strtoupper(get_option($option_name));
    $data    = [
        'orientation' => '',
        'format'      => '',
    ];

    if ($oFormat == 'A4-PORTRAIT') {
        $data['orientation'] = 'P';
        $data['format']      = 'A4';
    } elseif ($oFormat == 'A4-LANDSCAPE') {
        $data['orientation'] = 'L';
        $data['format']      = 'A4';
    } elseif ($oFormat == 'LETTER-PORTRAIT') {
        $data['orientation'] = 'P';
        $data['format']      = 'LETTER';
    } elseif ($oFormat == 'LETTER-LANDSCAPE') {
        $data['orientation'] = 'L';
        $data['format']      = 'LETTER';
    }

    return hooks()->apply_filters('pdf_format_array', $data);
}

/**
 * Prepare general invoice pdf
 * @param  object $invoice Invoice as object with all necessary fields
 * @param  string $tag     tag for bulk pdf exporter
 * @return mixed object
 */
function invoice_pdf($invoice, $tag = '')
{
    return app_pdf('invoice', LIBSPATH . 'pdf/Invoice_pdf', $invoice, $tag);
}
/**
 * Prepare general credit note pdf
 * @param  object $credit_note Credit note as object with all necessary fields
 * @param  string $tag tag for bulk pdf exported
 * @return mixed object
 */
function credit_note_pdf($credit_note, $tag = '')
{
    return app_pdf('credit_note', LIBSPATH . 'pdf/Credit_note_pdf', $credit_note, $tag);
}

/**
 * Prepare general estimate pdf
 * @since  Version 1.0.2
 * @param  object $estimate estimate as object with all necessary fields
 * @param  string $tag tag for bulk pdf exporter
 * @return mixed object
 */
function estimate_pdf($estimate, $tag = '')
{
    return app_pdf('estimate', LIBSPATH . 'pdf/Estimate_pdf', $estimate, $tag);
}

/**
 * Function that generates proposal pdf for admin and clients area
 * @param  object $proposal
 * @param  string $tag      tag for bulk pdf exporter
 * @return object
 */
function proposal_pdf($proposal, $tag = '')
{
    return app_pdf('proposal', LIBSPATH . 'pdf/Proposal_pdf', $proposal, $tag);
}

/**
 * Generate contract pdf
 * @param  object $contract object db
 * @return mixed object
 */
function contract_pdf($contract)
{
    return app_pdf('contract', LIBSPATH . 'pdf/Contract_pdf', $contract);
}
/**
 * Generate payment pdf
 * @param  mixed $payment payment from database
 * @param  string $tag     tag for bulk pdf exporter
 * @return object
 */
function payment_pdf($payment, $tag = '')
{
    return app_pdf('payment', LIBSPATH . 'pdf/Payment_pdf', $payment, $tag);
}

/**
 * Prepare customer statement pdf
 * @param  object $statement statement
 * @return mixed
 */
function statement_pdf($statement)
{
    return app_pdf('statement', LIBSPATH . 'pdf/Statement_pdf', $statement);
}

/**
 * General function for PDF documents logic
 * @param  string $type   document type e.q. payment, statement, invoice
 * @param  string $class  full class path
 * @param  mixed $params  params to pass in class constructor
 * @return object
 */
function app_pdf($type, $path, ...$params)
{
    $basename = ucfirst(basename(strbefore($path, EXT)));

    if (!endsWith($path, EXT)) {
        $path .= EXT;
    }

    $path = hooks()->apply_filters("{$type}_pdf_class_path", $path, ...$params);

    include_once($path);

    return (new $basename(...$params))->prepare();
}
/**
 * This will add tag to PDF at the top right side
 * Only used when bulk pdf exporter feature is used from admin area
 * @param  string $tag  tag to check
 * @param  object &$pdf pdf instance
 * @return null
 */
function _bulk_pdf_export_maybe_tag($tag, &$pdf)
{
    // Tag - used in BULK pdf exporter
    if ($tag != '') {
        $font_name = get_option('pdf_font');
        $font_size = get_option('pdf_font_size');

        if ($font_size == '') {
            $font_size = 10;
        }
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetDrawColor(245, 245, 245);
        $pdf->SetXY(0, 0);
        $pdf->SetFont($font_name, 'B', 15);
        $pdf->SetTextColor(0);
        $pdf->SetLineWidth(0.75);
        $pdf->StartTransform();
        $pdf->Rotate(-35, 109, 235);
        $pdf->Cell(100, 1, mb_strtoupper($tag, 'UTF-8'), 'TB', 0, 'C', '1');
        $pdf->StopTransform();
        $pdf->SetFont($font_name, '', $font_size);
        $pdf->setX(10);
        $pdf->setY(10);
    }
}

/**
 * Helper function for PDF multi row
 * @param  string  $left       the left row
 * @param  string  $right      the right row
 * @param  object  $pdf        the PDF class object
 * @param  integer $left_width left row width
 * @return null
 */
function pdf_multi_row($left, $right, $pdf, $left_width = 40)
{
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0)

    $page_start = $pdf->getPage();
    $y_start    = $pdf->GetY();

    // write the left cell
    $pdf->MultiCell($left_width, 0, $left, 0, 'L', 0, 2, '', '', true, 0, true);

    $page_end_1 = $pdf->getPage();
    $y_end_1    = $pdf->GetY();

    $pdf->setPage($page_start);

    // write the right cell
    $pdf->MultiCell(0, 0, $right, 0, 'R', 0, 1, $pdf->GetX(), $y_start, true, 0, true);

    $page_end_2 = $pdf->getPage();
    $y_end_2    = $pdf->GetY();

    // set the new row position by case
    if (max($page_end_1, $page_end_2) == $page_start) {
        $ynew = max($y_end_1, $y_end_2);
    } elseif ($page_end_1 == $page_end_2) {
        $ynew = max($y_end_1, $y_end_2);
    } elseif ($page_end_1 > $page_end_2) {
        $ynew = $y_end_1;
    } else {
        $ynew = $y_end_2;
    }

    $pdf->setPage(max($page_end_1, $page_end_2));
    $pdf->SetXY($pdf->GetX(), $ynew);
}
