<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Check if company using invoice with different currencies
 * @param  string  $table table to check
 * @return boolean
 */
function is_using_multiple_currencies($table = null)
{
    if (!$table) {
        $table = db_prefix() . 'invoices';
    }

    $CI = & get_instance();
    $CI->load->model('currencies_model');
    $currencies            = $CI->currencies_model->get();
    $total_currencies_used = 0;
    $other_then_base       = false;
    $base_found            = false;
    foreach ($currencies as $currency) {
        $CI->db->where('currency', $currency['id']);
        $total = $CI->db->count_all_results($table);
        if ($total > 0) {
            $total_currencies_used++;
            if ($currency['isdefault'] == 0) {
                $other_then_base = true;
            } else {
                $base_found = true;
            }
        }
    }

    if ($total_currencies_used > 1 && $base_found == true && $other_then_base == true) {
        return true;
    } elseif ($total_currencies_used == 1 && $base_found == false && $other_then_base == true) {
        return true;
    } elseif ($total_currencies_used == 0 || $total_currencies_used == 1) {
        return false;
    }

    return true;
}
/**
 * Custom format number function for the app
 * @param  mixed  $total
 * @param  boolean $foce_check_zero_decimals whether to force check
 * @return mixed
 */
function app_format_number($total, $foce_check_zero_decimals = false)
{
    if (!is_numeric($total)) {
        return $total;
    }

    $decimal_separator  = get_option('decimal_separator');
    $thousand_separator = get_option('thousand_separator');

    $d = get_decimal_places();
    if (get_option('remove_decimals_on_zero') == 1 || $foce_check_zero_decimals == true) {
        if (!is_decimal($total)) {
            $d = 0;
        }
    }

    $formatted = number_format($total, $d, $decimal_separator, $thousand_separator);

    return hooks()->apply_filters('number_after_format', $formatted, [
        'total'              => $total,
        'decimal_separator'  => $decimal_separator,
        'thousand_separator' => $thousand_separator,
        'decimal_places'     => $d,
    ]);
}

/**
 * Format money/amount based on currency settings
 * @since  2.3.2
 * @param  mixed   $amount          amount to format
 * @param  mixed   $currency        currency db object or currency name (ISO code)
 * @param  boolean $excludeSymbol   whether to exclude to symbol from the format
 * @return string
 */
function app_format_money($amount, $currency, $excludeSymbol = false)
{
    /**
     *  Check ewhether the amount is numeric and valid
     */
    if (!is_numeric($amount) && $amount != 0) {
        return $amount;
    }

    /**
     * Check if currency is passed as Object from database or just currency name e.q. USD
     */
    if (is_string($currency)) {

        $dbCurrency = get_currency($currency);

        // Check of currency found in case does not exists in database
        if ($dbCurrency) {
            $currency = $dbCurrency;
        } else {
            $currency = [
                'symbol'             => $currency,
                'name'               => $currency,
                'placement'          => 'before',
                'decimal_separator'  => get_option('decimal_separator'),
                'thousand_separator' => get_option('thousand_separator'),
            ];
            $currency = (object) $currency;
        }
    }

    /**
     * Determine the symbol
     * @var string
     */
    $symbol = !$excludeSymbol ? $currency->symbol : '';

    /**
     * Check decimal places
     * @var mixed
     */
    $d = get_option('remove_decimals_on_zero') == 1 && !is_decimal($amount) ? 0 : get_decimal_places();

    /**
     * Format the amount
     * @var string
     */
    $amountFormatted = number_format($amount, $d, $currency->decimal_separator, $currency->thousand_separator);

    /**
     * Maybe add the currency symbol
     * @var string
     */
    $formattedWithCurrency = $currency->placement === 'after' ? $amountFormatted . '' . $symbol : $symbol . '' . $amountFormatted;

    return hooks()->apply_filters('app_format_money', $formattedWithCurrency, [
        'amount'         => $amount,
        'currency'       => $currency,
        'exclude_symbol' => $excludeSymbol,
        'decimal_places' => $d,
    ]);
}

/**
 * Check if passed number is decimal
 * @param  mixed  $val
 * @return boolean
 */
function is_decimal($val)
{
    return is_numeric($val) && floor($val) != $val;
}
/**
 * Function that will loop through taxes and will check if there is 1 tax or multiple
 * @param  array $taxes
 * @return boolean
 */
function multiple_taxes_found_for_item($taxes)
{
    $names = [];
    foreach ($taxes as $t) {
        array_push($names, $t['taxname']);
    }
    $names = array_map('unserialize', array_unique(array_map('serialize', $names)));
    if (count($names) == 1) {
        return false;
    }

    return true;
}

/**
 * If there is more then 200 items in the script the search when creating eq invoice, estimate, proposal
 * will be ajax based
 * @return int
 */
function ajax_on_total_items()
{
    return hooks()->apply_filters('ajax_on_total_items', 200);
}

/**
 * Helper function to get tax by passedid
 * @param  integer $id taxid
 * @return object
 */
function get_tax_by_id($id)
{
    $CI = & get_instance();
    $CI->db->where('id', $id);

    return $CI->db->get(db_prefix() . 'taxes')->row();
}
/**
 * Helper function to get tax by passed name
 * @param  string $name tax name
 * @return object
 */
function get_tax_by_name($name)
{
    $CI = & get_instance();
    $CI->db->where('name', $name);

    return $CI->db->get(db_prefix() . 'taxes')->row();
}
/**
 * This function replace <br /> only nothing exists in the line and first line other then <br />
 *  Replace first <br /> lines to prevent new spaces
 * @param  string $text The text to perform the action
 * @return string
 */
function _maybe_remove_first_and_last_br_tag($text)
{
    $text = preg_replace('/^<br ?\/?>/is', '', $text);
    // Replace last <br /> lines to prevent new spaces while there is new line
    while (preg_match('/<br ?\/?>$/', $text)) {
        $text = preg_replace('/<br ?\/?>$/is', '', $text);
    }

    return $text;
}

/**
 * Helper function to replace info format merge fields
 * Info format = Address formats for customers, proposals, company information
 * @param  string $mergeCode merge field to check
 * @param  mixed $val       value to replace
 * @param  string $txt       from format
 * @return string
 */
function _info_format_replace($mergeCode, $val, $txt)
{
    $tmpVal = strip_tags($val);

    if ($tmpVal != '') {
        $result = preg_replace('/({' . $mergeCode . '})/i', $val, $txt);
    } else {
        $re     = '/\s{0,}{' . $mergeCode . '}(<br ?\/?>(\n))?/i';
        $result = preg_replace($re, '', $txt);
    }

    return $result;
}

/**
 * Helper function to replace info format custom field merge fields
 * Info format = Address formats for customers, proposals, company information
 * @param  mixed $id    custom field id
 * @param  string $label custom field label
 * @param  mixed $value custom field value
 * @param  string $txt   from format
 * @return string
 */
function _info_format_custom_field($id, $label, $value, $txt)
{
    if ($value != '') {
        $result = preg_replace('/({cf_' . $id . '})/i', $label . ': ' . $value, $txt);
    } else {
        $re     = '/\s{0,}{cf_' . $id . '}(<br ?\/?>(\n))?/i';
        $result = preg_replace($re, '', $txt);
    }

    return hooks()->apply_filters('info_format_custom_field', $result, [
        'id'    => $id,
        'label' => $label,
        'txt'   => $txt,
    ]);
}

/**
 * Perform necessary checking for custom fields info format
 * @param  array $custom_fields custom fields
 * @param  string $txt           info format text
 * @return string
 */
function _info_format_custom_fields_check($custom_fields, $txt)
{
    if (count($custom_fields) == 0 || preg_match_all('/({cf_[0-9]{1,}})/i', $txt, $matches, PREG_SET_ORDER, 0) > 0) {
        $txt = preg_replace('/\s{0,}{cf_[0-9]{1,}}(<br ?\/?>(\n))?/i', '', $txt);
    }

    return $txt;
}

if (!function_exists('format_customer_info')) {
    /**
     * Format customer address info
     * @param  object  $data        customer object from database
     * @param  string  $for         where this format will be used? Eq statement invoice etc
     * @param  string  $type        billing/shipping
     * @param  boolean $companyLink company link to be added on customer company/name, this is used in admin area only
     * @return string
     */
    function format_customer_info($data, $for, $type, $companyLink = false)
    {
        $format   = get_option('customer_info_format');
        $clientId = '';


        if ($for == 'statement') {
            $clientId = $data->userid;
        } elseif ($type == 'billing') {
            $clientId = $data->clientid;
        }

        $companyName = '';
        if ($for == 'statement') {
            $companyName = get_company_name($clientId);
        } elseif ($type == 'billing') {
            $companyName = $data->client->company;
        }

        if ($for == 'invoice' || $for == 'estimate' || $for == 'payment' || $for == 'credit_note') {
            if (isset($data->client->show_primary_contact) && $data->client->show_primary_contact == 1) {
                $primaryContactId = get_primary_contact_user_id($clientId);
                if ($primaryContactId) {
                    $companyName = get_contact_full_name($primaryContactId) . '<br />' . $companyName;
                }
            }
        }

        $street = '';
        if ($type == 'billing') {
            $street = $data->billing_street;
        } elseif ($type == 'shipping') {
            $street = $data->shipping_street;
        }

        $city = '';
        if ($type == 'billing') {
            $city = $data->billing_city;
        } elseif ($type == 'shipping') {
            $city = $data->shipping_city;
        }
        $state = '';
        if ($type == 'billing') {
            $state = $data->billing_state;
        } elseif ($type == 'shipping') {
            $state = $data->shipping_state;
        }
        $zipCode = '';
        if ($type == 'billing') {
            $zipCode = $data->billing_zip;
        } elseif ($type == 'shipping') {
            $zipCode = $data->shipping_zip;
        }

        $countryCode = '';
        $countryName = '';
        $country     = null;
        if ($type == 'billing') {
            $country = get_country($data->billing_country);
        } elseif ($type == 'shipping') {
            $country = get_country($data->shipping_country);
        }

        if ($country) {
            $countryCode = $country->iso2;
            $countryName = $country->short_name;
        }

        $phone = '';
        if ($for == 'statement' && isset($data->phonenumber)) {
            $phone = $data->phonenumber;
        } elseif ($type == 'billing' && isset($data->client->phonenumber)) {
            $phone = $data->client->phonenumber;
        }

        $vat = '';
        if ($for == 'statement' && isset($data->vat)) {
            $vat = $data->vat;
        } elseif ($type == 'billing' && isset($data->client->vat)) {
            $vat = $data->client->vat;
        }

        if ($companyLink && (!isset($data->deleted_customer_name) || (isset($data->deleted_customer_name) && empty($data->deleted_customer_name)))) {
            $companyName = '<a href="' . admin_url('clients/client/' . $clientId) . '" target="_blank"><b>' . $companyName . '</b></a>';
        } elseif ($companyName != '') {
            $companyName = '<b>' . $companyName . '</b>';
        }

        $format = _info_format_replace('company_name', $companyName, $format);
        $format = _info_format_replace('customer_id', $clientId, $format);
        $format = _info_format_replace('street', $street, $format);
        $format = _info_format_replace('city', $city, $format);
        $format = _info_format_replace('state', $state, $format);
        $format = _info_format_replace('zip_code', $zipCode, $format);
        $format = _info_format_replace('country_code', $countryCode, $format);
        $format = _info_format_replace('country_name', $countryName, $format);
        $format = _info_format_replace('phone', $phone, $format);
        $format = _info_format_replace('vat_number', $vat, $format);
        $format = _info_format_replace('vat_number_with_label', $vat == '' ? '' : _l('client_vat_number') . ': ' . $vat, $format);

        $customFieldsCustomer = [];

        // On shipping address no custom fields are shown
        if ($type != 'shipping') {
            $whereCF = [];

            if (is_custom_fields_for_customers_portal()) {
                $whereCF['show_on_client_portal'] = 1;
            }

            $customFieldsCustomer = get_custom_fields('customers', $whereCF);
        }

        foreach ($customFieldsCustomer as $field) {
            $value  = get_custom_field_value($clientId, $field['id'], 'customers');
            $format = _info_format_custom_field($field['id'], $field['name'], $value, $format);
        }

        // If no custom fields found replace all custom fields merge fields to empty
        $format = _info_format_custom_fields_check($customFieldsCustomer, $format);
        $format = _maybe_remove_first_and_last_br_tag($format);

        // Remove multiple white spaces
        $format = preg_replace('/\s+/', ' ', $format);
        $format = trim($format);

        return hooks()->apply_filters('customer_info_text', $format, [
            'data'         => $data,
            'for'          => $for,
            'type'         => $type,
            'company_link' => $companyLink,
        ]);
    }
}

if (!function_exists('format_proposal_info')) {
    /**
     * Format proposal info format
     * @param  object $proposal proposal from database
     * @param  string $for      where this info will be used? Admin area, HTML preview?
     * @return string
     */
    function format_proposal_info($proposal, $for = '')
    {
        $format = get_option('proposal_info_format');

        $countryCode = '';
        $countryName = '';
        $country     = get_country($proposal->country);

        if ($country) {
            $countryCode = $country->iso2;
            $countryName = $country->short_name;
        }

        $proposalTo = '<b>' . $proposal->proposal_to . '</b>';
        $phone      = $proposal->phone;
        $email      = $proposal->email;

        if ($for == 'admin') {
            $hrefAttrs = '';
            if ($proposal->rel_type == 'lead') {
                $hrefAttrs = ' href="#" onclick="init_lead(' . $proposal->rel_id . '); return false;" data-toggle="tooltip" data-title="' . _l('lead') . '"';
            } else {
                $hrefAttrs = ' href="' . admin_url('clients/client/' . $proposal->rel_id) . '" data-toggle="tooltip" data-title="' . _l('client') . '"';
            }
            $proposalTo = '<a' . $hrefAttrs . '>' . $proposalTo . '</a>';
        }

        if ($for == 'html' || $for == 'admin') {
            $phone = '<a href="tel:' . $proposal->phone . '">' . $proposal->phone . '</a>';
            $email = '<a href="mailto:' . $proposal->email . '">' . $proposal->email . '</a>';
        }

        $format = _info_format_replace('proposal_to', $proposalTo, $format);
        $format = _info_format_replace('address', $proposal->address, $format);
        $format = _info_format_replace('city', $proposal->city, $format);
        $format = _info_format_replace('state', $proposal->state, $format);

        $format = _info_format_replace('country_code', $countryCode, $format);
        $format = _info_format_replace('country_name', $countryName, $format);

        $format = _info_format_replace('zip_code', $proposal->zip, $format);
        $format = _info_format_replace('phone', $phone, $format);
        $format = _info_format_replace('email', $email, $format);

        $whereCF = [];
        if (is_custom_fields_for_customers_portal()) {
            $whereCF['show_on_client_portal'] = 1;
        }
        $customFieldsProposals = get_custom_fields('proposal', $whereCF);

        foreach ($customFieldsProposals as $field) {
            $value  = get_custom_field_value($proposal->id, $field['id'], 'proposal');
            $format = _info_format_custom_field($field['id'], $field['name'], $value, $format);
        }

        // If no custom fields found replace all custom fields merge fields to empty
        $format = _info_format_custom_fields_check($customFieldsProposals, $format);
        $format = _maybe_remove_first_and_last_br_tag($format);

        // Remove multiple white spaces
        $format = preg_replace('/\s+/', ' ', $format);
        $format = trim($format);

        return hooks()->apply_filters('proposal_info_text', $format, ['proposal' => $proposal, 'for' => $for]);
    }
}

if (!function_exists('format_organization_info')) {
    /**
     * Format company info/address format
     * @return string
     */
    function format_organization_info()
    {
        $format = get_option('company_info_format');
        $vat    = get_option('company_vat');

        $format = _info_format_replace('company_name', '<b style="color:black" class="company-name-formatted">' . get_option('invoice_company_name') . '</b>', $format);
        $format = _info_format_replace('address', get_option('invoice_company_address'), $format);
        $format = _info_format_replace('city', get_option('invoice_company_city'), $format);
        $format = _info_format_replace('state', get_option('company_state'), $format);

        $format = _info_format_replace('zip_code', get_option('invoice_company_postal_code'), $format);
        $format = _info_format_replace('country_code', get_option('invoice_company_country_code'), $format);
        $format = _info_format_replace('phone', get_option('invoice_company_phonenumber'), $format);
        $format = _info_format_replace('vat_number', $vat, $format);
        $format = _info_format_replace('vat_number_with_label', $vat == '' ? '':_l('company_vat_number') . ': ' . $vat, $format);

        $custom_company_fields = get_company_custom_fields();

        foreach ($custom_company_fields as $field) {
            $format = _info_format_custom_field($field['id'], $field['label'], $field['value'], $format);
        }

        $format = _info_format_custom_fields_check($custom_company_fields, $format);
        $format = _maybe_remove_first_and_last_br_tag($format);

        // Remove multiple white spaces
        $format = preg_replace('/\s+/', ' ', $format);
        $format = trim($format);

        return hooks()->apply_filters('organization_info_text', $format);
    }
}

/**
 * Return decimal places
 * The srcipt do not support more then 2 decimal places but developers can use action hook to change the decimal places
 * @return [type] [description]
 */
function get_decimal_places()
{
    return hooks()->apply_filters('app_decimal_places', 2);
}

/**
 * Get all items by type eq. invoice, proposal, estimates, credit note
 * @param  string $type rel_type value
 * @return array
 */
function get_items_by_type($type, $id)
{
    $CI = &get_instance();
    $CI->db->select();
    $CI->db->from(db_prefix() . 'itemable');
    $CI->db->where('rel_id', $id);
    $CI->db->where('rel_type', $type);
    $CI->db->order_by('item_order', 'asc');

    return $CI->db->get()->result_array();
}
/**
* Function that update total tax in sales table eq. invoice, proposal, estimates, credit note
* @param  mixed $id
* @return void
*/
function update_sales_total_tax_column($id, $type, $table)
{
    $CI = &get_instance();
    $CI->db->select('discount_percent, discount_type, discount_total, subtotal');
    $CI->db->from($table);
    $CI->db->where('id', $id);

    $data = $CI->db->get()->row();

    $items = get_items_by_type($type, $id);

    $total_tax         = 0;
    $taxes             = [];
    $_calculated_taxes = [];

    $func_taxes = 'get_' . $type . '_item_taxes';

    foreach ($items as $item) {
        $item_taxes = call_user_func($func_taxes, $item['id']);
        if (count($item_taxes) > 0) {
            foreach ($item_taxes as $tax) {
                $calc_tax     = 0;
                $tax_not_calc = false;
                if (!in_array($tax['taxname'], $_calculated_taxes)) {
                    array_push($_calculated_taxes, $tax['taxname']);
                    $tax_not_calc = true;
                }

                if ($tax_not_calc == true) {
                    $taxes[$tax['taxname']]          = [];
                    $taxes[$tax['taxname']]['total'] = [];
                    array_push($taxes[$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                    $taxes[$tax['taxname']]['tax_name'] = $tax['taxname'];
                    $taxes[$tax['taxname']]['taxrate']  = $tax['taxrate'];
                } else {
                    array_push($taxes[$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                }
            }
        }
    }

    foreach ($taxes as $tax) {
        $total = array_sum($tax['total']);
        if ($data->discount_percent != 0 && $data->discount_type == 'before_tax') {
            $total_tax_calculated = ($total * $data->discount_percent) / 100;
            $total                = ($total - $total_tax_calculated);
        } elseif ($data->discount_total != 0 && $data->discount_type == 'before_tax') {
            $t     = ($data->discount_total / $data->subtotal) * 100;
            $total = ($total - $total * $t / 100);
        }
        $total_tax += $total;
    }

    $CI->db->where('id', $id);
    $CI->db->update($table, [
            'total_tax' => $total_tax,
    ]);
}

/**
 * Function used for sales eq. invoice, estimate, proposal, credit note
 * @param  mixed $item_id   item id
 * @param  array $post_item $item from $_POST
 * @param  mixed $rel_id    rel_id
 * @param  string $rel_type  where this item tax is related
 */
function _maybe_insert_post_item_tax($item_id, $post_item, $rel_id, $rel_type)
{
    $affectedRows = 0;
    if (isset($post_item['taxname']) && is_array($post_item['taxname'])) {
        $CI = &get_instance();
        foreach ($post_item['taxname'] as $taxname) {
            if ($taxname != '') {
                $tax_array = explode('|', $taxname);
                if (isset($tax_array[0]) && isset($tax_array[1])) {
                    $tax_name = trim($tax_array[0]);
                    $tax_rate = trim($tax_array[1]);
                    if (total_rows(db_prefix() . 'item_tax', [
                        'itemid' => $item_id,
                        'taxrate' => $tax_rate,
                        'taxname' => $tax_name,
                        'rel_id' => $rel_id,
                        'rel_type' => $rel_type,
                    ]) == 0) {
                        $CI->db->insert(db_prefix() . 'item_tax', [
                                'itemid'   => $item_id,
                                'taxrate'  => $tax_rate,
                                'taxname'  => $tax_name,
                                'rel_id'   => $rel_id,
                                'rel_type' => $rel_type,
                        ]);
                        $affectedRows++;
                    }
                }
            }
        }
    }

    return $affectedRows > 0 ? true : false;
}

/**
 * Add new item do database, used for proposals,estimates,credit notes,invoices
 * This is repetitive action, that's why this function exists
 * @param array $item     item from $_POST
 * @param mixed $rel_id   relation id eq. invoice id
 * @param string $rel_type relation type eq invoice
 */
function add_new_sales_item_post($item, $rel_id, $rel_type)
{
    $custom_fields = false;

    if (isset($item['custom_fields'])) {
        $custom_fields = $item['custom_fields'];
    }

    $CI = &get_instance();

    $CI->db->insert(db_prefix() . 'itemable', [
                    'description'      => $item['description'],
                    'long_description' => nl2br($item['long_description']),
                    'qty'              => $item['qty'],
                    'rate'             => number_format($item['rate'], get_decimal_places(), '.', ''),
                    'rel_id'           => $rel_id,
                    'rel_type'         => $rel_type,
                    'item_order'       => $item['order'],
                    'unit'             => $item['unit'],
                ]);

    $id = $CI->db->insert_id();

    if ($custom_fields !== false) {
        handle_custom_fields_post($id, $custom_fields);
    }

    return $id;
}

/**
 * Update sales item from $_POST, eq invoice item, estimate item
 * @param  mixed $item_id item id to update
 * @param  array $data    item $_POST data
 * @param  string $field   field is require to be passed for long_description,rate,item_order to do some additional checkings
 * @return boolean
 */
function update_sales_item_post($item_id, $data, $field = '')
{
    $update = [];
    if ($field !== '') {
        if ($field == 'long_description') {
            $update[$field] = nl2br($data[$field]);
        } elseif ($field == 'rate') {
            $update[$field] = number_format($data[$field], get_decimal_places(), '.', '');
        } elseif ($field == 'item_order') {
            $update[$field] = $data['order'];
        } else {
            $update[$field] = $data[$field];
        }
    } else {
        $update = [
            'item_order'       => $data['order'],
            'description'      => $data['description'],
            'long_description' => nl2br($data['long_description']),
            'rate'             => number_format($data['rate'], get_decimal_places(), '.', ''),
            'qty'              => $data['qty'],
            'unit'             => $data['unit'],
        ];
    }

    $CI = &get_instance();
    $CI->db->where('id', $item_id);
    $CI->db->update(db_prefix() . 'itemable', $update);

    return $CI->db->affected_rows() > 0 ? true : false;
}

/**
 * When item is removed eq from invoice will be stored in removed_items in $_POST
 * With foreach loop this function will remove the item from database and it's taxes
 * @param  mixed $id       item id to remove
 * @param  string $rel_type item relation eq. invoice, estimate
 * @return boolena
 */
function handle_removed_sales_item_post($id, $rel_type)
{
    $CI = &get_instance();

    $CI->db->where('id', $id);
    $CI->db->delete(db_prefix() . 'itemable');
    if ($CI->db->affected_rows() > 0) {
        delete_taxes_from_item($id, $rel_type);

        $CI->db->where('relid', $id);
        $CI->db->where('fieldto', 'items');
        $CI->db->delete(db_prefix() . 'customfieldsvalues');

        return true;
    }

    return false;
}

/**
 * Remove taxes from item
 * @param  mixed $item_id  item id
 * @param  string $rel_type relation type eq. invoice, estimate etc.
 * @return boolean
 */
function delete_taxes_from_item($item_id, $rel_type)
{
    $CI = &get_instance();
    $CI->db->where('itemid', $item_id)
    ->where('rel_type', $rel_type)
    ->delete(db_prefix() . 'item_tax');

    return $CI->db->affected_rows() > 0 ? true : false;
}

function is_sale_discount_applied($data)
{
    return $data->discount_total > 0;
}

function is_sale_discount($data, $is)
{
    if ($data->discount_percent == 0 && $data->discount_total == 0) {
        return false;
    }

    $discount_type = 'fixed';
    if ($data->discount_percent != 0) {
        $discount_type = 'percent';
    }

    return $discount_type == $is;
}

/**
 * Get items table for preview
 * @param  object  $transaction   e.q. invoice, estimate from database result row
 * @param  string  $type          type, e.q. invoice, estimate, proposal
 * @param  string  $for           where the items will be shown, html or pdf
 * @param  boolean $admin_preview is the preview for admin area
 * @return object
 */
function get_items_table_data($transaction, $type, $for = 'html', $admin_preview = false)
{
    include_once(APPPATH . 'libraries/App_items_table.php');
    $class = new App_items_table($transaction, $type, $for, $admin_preview);

    $class = hooks()->apply_filters('items_table_class', $class, $transaction, $type, $for, $admin_preview);

    if (!$class instanceof App_items_table_template) {
        show_error(get_class($class) . ' must be instance of "App_items_template"');
    }

    return $class;
}

function sales_number_format($number, $format, $applied_prefix, $date)
{
    $originalNumber = $number;
    $prefixPadding  = get_option('number_padding_prefixes');

    if ($format == 1) {
        // Number based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT);
    } elseif ($format == 2) {
        // Year based
        $number = $applied_prefix . date('Y', strtotime($date)) . '/' . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT);
    } elseif ($format == 3) {
        // Number-yy based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT) . '-' . date('y', strtotime($date));
    } elseif ($format == 4) {
        // Number-mm-yyyy based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT) . '/' . date('m', strtotime($date)) . '/' . date('Y', strtotime($date));
    }

    return hooks()->apply_filters('sales_number_format', $number, [
        'format'         => $format,
        'date'           => $date,
        'number'         => $originalNumber,
        'prefix_padding' => $prefixPadding,
    ]);
}

/**
 * Helper function to get currency by ID or by Name
 * @since  2.3.2
 * @param  mixed $id_or_name
 * @return object
 */
function get_currency($id_or_name)
{
    $CI = &get_instance();
    if (!class_exists('currencies_model', false)) {
        $CI->load->model('currencies_model');
    }

    if (is_numeric($id_or_name)) {
        return $CI->currencies_model->get($id_or_name);
    }

    return $CI->currencies_model->get_by_name($id_or_name);
}

/**
 * Get base currency
 * @since  2.3.2
 * @return object
 */
function get_base_currency()
{
    $CI = &get_instance();

    if (!class_exists('currencies_model', false)) {
        $CI->load->model('currencies_model');
    }

    return $CI->currencies_model->get_base_currency();
}
