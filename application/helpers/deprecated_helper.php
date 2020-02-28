<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Deprecated function error
 * @param  string $function    The function that was called
 * @param  string $version     The version that deprecated the function
 * @param  string $replacement The new function that should be called
 * @return mixed
 */
function _deprecated_function($function, $version, $replacement = null)
{
    hooks()->do_action('deprecated_function_run', $function, $replacement, $version);

    /**
     * Filters whether to trigger an error for deprecated functions.
     *
     * @since 2.3.2
     *
     * @param bool $trigger Whether to trigger the error for deprecated functions. Default true.
     */
    if (ENVIRONMENT != 'production' && hooks()->apply_filters('deprecated_function_trigger_error', true)) {
        if (! is_null($replacement)) {
            trigger_error(sprintf('%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', $function, $version, $replacement));
        } else {
            trigger_error(sprintf('%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', $function, $version));
        }

        _has_deprecated_errors_admin_body_class();
    }
}

function _deprecated_hook($hook, $version, $replacement = null, $message = null)
{
    hooks()->do_action('deprecated_hook_run', $hook, $replacement, $version, $message);

    /**
     * Filters whether to trigger deprecated hook errors.
     *
     * @since 2.3.1
     */
    if (ENVIRONMENT != 'production' && hooks()->apply_filters('deprecated_hook_trigger_error', true)) {
        $message = empty($message) ? '' : ' ' . $message;

        if (! is_null($replacement)) {
            /* translators: 1: Hook name, 2: version number, 3: alternative hook name */
            trigger_error(sprintf('Hook %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', $hook, $version, $replacement) . $message);
        } else {
            /* translators: 1: Hook name, 2: version number */
            trigger_error(sprintf('Hook %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', $hook, $version) . $message);
        }

        _has_deprecated_errors_admin_body_class();
    }
}
/**
 * @since  2.3.2
 * @private
 * Adds filter for admin body class to add has-deprecated-errors class on body
 * This is available only when the errors are thrown in php files like classes, before the VIEW loads
 * because when the errors are thrown after of <body> the menu item is hidding the errors
 * @return void
 */
function _has_deprecated_errors_admin_body_class()
{
    if (hooks()->has_filter('admin_body_class', '_add_has_deprecated_errors_admin_body_class')) {
        return;
    }

    hooks()->add_filter('admin_body_class', '_add_has_deprecated_errors_admin_body_class');
}

/**
 * @since  2.3.2
 * @private
 * Adds has-deprecated-errors class to body
 * @return array
 */
function _add_has_deprecated_errors_admin_body_class($classes)
{
    $classes[] = 'has-deprecated-errors';

    return $classes;
}

/**
 * @deprecated 2.3.0 use starsWith instead
 */
if (!function_exists('_startsWith')) {
    function _startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
}

/**
 * @deprecated
 */
function get_table_items_html_and_taxes($items, $type, $admin_preview = false)
{
    return get_table_items_and_taxes($items, $type, $admin_preview);
}

/**
 * @deprecated
 */
function get_table_items_pdf_and_taxes($items, $type)
{
    return get_table_items_and_taxes($items, $type);
}

/**
 * @deprecated
 */
function get_project_label($id, $replace_default_by_muted = false)
{
    return project_status_color_class($id, $replace_default_by_muted);
}

/**
 * @deprecated
 */
function project_status_color_class($id, $replace_default_by_muted = false)
{
    if ($id == 1 || $id == 5) {
        $class = 'default';
        if ($replace_default_by_muted == true) {
            $class = 'muted';
        }
    } elseif ($id == 2) {
        $class = 'info';
    } elseif ($id == 3) {
        $class = 'warning';
    } else {
        // ID == 4 finished
        $class = 'success';
    }

    return hooks()->apply_filters('project_status_color_class', $class, $id);
}

/**
 * @deprecated
 * Return class based on task priority id
 * @param  mixed $id
 * @return string
 */
function get_task_priority_class($id)
{
    if ($id == 1) {
        $class = 'muted';
    } elseif ($id == 2) {
        $class = 'info';
    } elseif ($id == 3) {
        $class = 'warning';
    } else {
        $class = 'danger';
    }

    return $class;
}

/**
 * @deprecated
 */
function project_status_by_id($id)
{
    $label     = _l('project_status_' . $id);
    $hook_data = hooks()->apply_filters('project_status_label', ['id' => $id, 'label' => $label]);
    $label     = $hook_data['label'];

    return $label;
}

/**
 * @deprecated
 */
function format_seconds($seconds)
{
    $minutes = $seconds / 60;
    $hours   = $minutes / 60;
    if ($minutes >= 60) {
        return round($hours, 2) . ' ' . _l('hours');
    } elseif ($seconds > 60) {
        return round($minutes, 2) . ' ' . _l('minutes');
    }

    return $seconds . ' ' . _l('seconds');
}

/**
 * @deprecated
 */
function add_encryption_key_old()
{
    $CI          = & get_instance();
    $key         = generate_encryption_key();
    $config_path = APPPATH . 'config/config.php';
    $CI->load->helper('file');
    @chmod($config_path, FILE_WRITE_MODE);
    $config_file = read_file($config_path);
    $config_file = trim($config_file);
    $config_file = str_replace("\$config['encryption_key'] = '';", "\$config['encryption_key'] = '" . $key . "';", $config_file);
    if (!$fp = fopen($config_path, FOPEN_WRITE_CREATE_DESTRUCTIVE)) {
        return false;
    }
    flock($fp, LOCK_EX);
    fwrite($fp, $config_file, strlen($config_file));
    flock($fp, LOCK_UN);
    fclose($fp);
    @chmod($config_path, FILE_READ_MODE);

    return $key;
}

/**
* @deprecated
* Function moved in main.js
*/
function app_admin_ajax_search_function()
{
    ?>
<script>
  function init_ajax_search(type, selector, server_data, url){

    var ajaxSelector = $('body').find(selector);
    if(ajaxSelector.length){
      var options = {
        ajax: {
          url: (typeof(url) == 'undefined' ? admin_url + 'misc/get_relation_data' : url),
          data: function () {
            var data = {};
            data.type = type;
            data.rel_id = '';
            data.q = '{{{q}}}';
            if(typeof(server_data) != 'undefined'){
              jQuery.extend(data, server_data);
            }
            return data;
          }
        },
        locale: {
          emptyTitle: "<?php echo _l('search_ajax_empty'); ?>",
          statusInitialized: "<?php echo _l('search_ajax_initialized'); ?>",
          statusSearching:"<?php echo _l('search_ajax_searching'); ?>",
          statusNoResults:"<?php echo _l('not_results_found'); ?>",
          searchPlaceholder:"<?php echo _l('search_ajax_placeholder'); ?>",
          currentlySelected:"<?php echo _l('currently_selected'); ?>",
        },
        requestDelay:500,
        cache:false,
        preprocessData: function(processData){
          var bs_data = [];
          var len = processData.length;
          for(var i = 0; i < len; i++){
            var tmp_data =  {
              'value': processData[i].id,
              'text': processData[i].name,
            };
            if(processData[i].subtext){
              tmp_data.data = {subtext:processData[i].subtext}
            }
            bs_data.push(tmp_data);
          }
          return bs_data;
        },
        preserveSelectedPosition:'after',
        preserveSelected:true
      }
      if(ajaxSelector.data('empty-title')){
        options.locale.emptyTitle = ajaxSelector.data('empty-title');
      }
      ajaxSelector.selectpicker().ajaxSelectPicker(options);
    }
  }
 </script>
<?php
}

/**
 * @deprecated
 */
function number_unformat($number, $force_number = true)
{
    if ($force_number) {
        $number = preg_replace('/^[^\d]+/', '', $number);
    } elseif (preg_match('/^[^\d]+/', $number)) {
        return false;
    }
    $dec_point     = get_option('decimal_separator');
    $thousands_sep = get_option('thousand_separator');
    $type          = (strpos($number, $dec_point) === false) ? 'int' : 'float';
    $number        = str_replace([
        $dec_point,
        $thousands_sep,
    ], [
        '.',
        '',
    ], $number);
    settype($number, $type);

    return $number;
}


/**
 * Output the select plugin with locale
 * @param  string $locale current locale
 * @return mixed
 */
function app_select_plugin_js($locale = 'en')
{
    echo "<script src='" . base_url('assets/plugins/app-build/bootstrap-select.min.js?v=' . get_app_version()) . "'></script>" . PHP_EOL;

    if ($locale != 'en') {
        if (file_exists(FCPATH . 'assets/plugins/bootstrap-select/js/i18n/defaults-' . $locale . '.min.js')) {
            echo "<script src='" . base_url('assets/plugins/bootstrap-select/js/i18n/defaults-' . $locale . '.min.js') . "'></script>" . PHP_EOL;
        } elseif (file_exists(FCPATH . 'assets/plugins/bootstrap-select/js/i18n/defaults-' . $locale . '_' . strtoupper($locale) . '.min.js')) {
            echo "<script src='" . base_url('assets/plugins/bootstrap-select/js/i18n/defaults-' . $locale . '_' . strtoupper($locale) . '.min.js') . "'></script>" . PHP_EOL;
        }
    }
}

/**
 * Output the validation plugin with locale
 * @param  string $locale current locale
 * @return mixed
 */
function app_jquery_validation_plugin_js($locale = 'en')
{
    echo "<script src='" . base_url('assets/plugins/jquery-validation/jquery.validate.min.js?v=' . get_app_version()) . "'></script>" . PHP_EOL;
    if ($locale != 'en') {
        if (file_exists(FCPATH . 'assets/plugins/jquery-validation/localization/messages_' . $locale . '.min.js')) {
            echo "<script src='" . base_url('assets/plugins/jquery-validation/localization/messages_' . $locale . '.min.js') . "'></script>" . PHP_EOL;
        } elseif (file_exists(FCPATH . 'assets/plugins/jquery-validation/localization/messages_' . $locale . '_' . strtoupper($locale) . '.min.js')) {
            echo "<script src='" . base_url('assets/plugins/jquery-validation/localization/messages_' . $locale . '_' . strtoupper($locale) . '.min.js') . "'></script>" . PHP_EOL;
        }
    }
}

/**
 * Based on the template slug and email the function will fetch a template from database
 * The template will be fetched on the language that should be sent
 * @param  string $template_slug
 * @param  string $email
 * @return object
 */
function get_email_template_for_sending($template_slug, $email)
{
    $CI = & get_instance();

    $language = get_email_template_language($template_slug, $email);

    if (!is_dir(APPPATH . 'language/' . $language)) {
        $language = 'english';
    }

    if (!class_exists('emails_model', false)) {
        $CI->load->model('emails_model');
    }

    $template = $CI->emails_model->get(['language' => $language, 'slug' => $template_slug], 'row');

    // Template languages not yet inserted
    // Users needs to visit Setup->Email Templates->Any template to initialize all languages
    if (!$template) {
        $template = $CI->emails_model->get(['language' => 'english', 'slug' => $template_slug], 'row');
    } else {
        if ($template && $template->message == '') {
            // Template message blank use the active language default template
            $template = $CI->emails_model->get(['language' => get_option('active_language'), 'slug' => $template_slug], 'row');

            if ($template->message == '') {
                $template = $CI->emails_model->get(['language' => 'english', 'slug' => $template_slug], 'row');
            }
        }
    }

    return $template;
}

/**
 * @deprecated 2.3.0
 * This function will parse email template merge fields and replace with the corresponding merge fields passed before sending email
 * @param  object $template     template from database
 * @param  array $merge_fields available merge fields
 * @return object
 */
function _parse_email_template_merge_fields($template, $merge_fields)
{
    return parse_email_template_merge_fields($template, $merge_fields);
}



/**
 * All email client templates slugs used for sending the emails
 * If you create new email template you can and must add the slug here with action hook.
 * Those are used to identify in what language should the email template to be sent
 * @deprecated 2.3.0
 * @return array
 */
function get_client_email_templates_slugs()
{
    $templates = [
        'new-client-created',
        'client-statement',
        'invoice-send-to-client',
        'new-ticket-opened-admin',
        'ticket-reply',
        'ticket-autoresponse',
        'assigned-to-project',
        'credit-note-send-to-client',
        'invoice-payment-recorded',
        'invoice-overdue-notice',
        'invoice-already-send',
        'estimate-send-to-client',
        'contact-forgot-password',
        'contact-password-reseted',
        'contact-set-password',
        'estimate-already-send',
        'contract-expiration',
        'proposal-send-to-customer',
        'proposal-client-thank-you',
        'proposal-comment-to-client',
        'estimate-thank-you-to-customer',
        'send-contract',
        'contract-comment-to-client',
        'auto-close-ticket',
        'new-project-discussion-created-to-customer',
        'new-project-file-uploaded-to-customer',
        'new-project-discussion-comment-to-customer',
        'project-finished-to-customer',
        'estimate-expiry-reminder',
        'estimate-expiry-reminder',
        'task-status-change-to-contacts',
        'task-added-attachment-to-contacts',
        'task-commented-to-contacts',
        'send-subscription',
        'subscription-payment-failed',
        'subscription-payment-succeeded',
        'subscription-canceled',
        'client-registration-confirmed',
        'contact-verification-email',
    ];

    return hooks()->apply_filters('client_email_templates', $templates);
}
/**
 * All email staff templates slugs used for sending the emails
 * If you create new email template you can and must add the slug here with action hook.
 * Those are used to identify in what language should the email template to be sent
 * @deprecated 2.3.0
 * @return array
 */
function get_staff_email_templates_slugs()
{
    $templates = [
        'reminder-email-staff',
        'new-ticket-created-staff',
        'two-factor-authentication',
        'ticket-reply-to-admin',
        'ticket-assigned-to-admin',
        'task-assigned',
        'task-added-as-follower',
        'task-commented',
        'contract-comment-to-admin',
        'staff-password-reseted',
        'staff-forgot-password',
        'task-status-change-to-staff',
        'task-added-attachment',
        'estimate-declined-to-staff',
        'estimate-accepted-to-staff',
        'proposal-client-accepted',
        'proposal-client-declined',
        'proposal-comment-to-admin',
        'task-deadline-notification',
        'invoice-payment-recorded-to-staff',
        'new-project-discussion-created-to-staff',
        'new-project-file-uploaded-to-staff',
        'new-project-discussion-comment-to-staff',
        'staff-added-as-project-member',
        'new-staff-created',
        'new-client-registered-to-admin',
        'new-lead-assigned',
        'contract-expiration-to-staff',
        'gdpr-removal-request',
        'gdpr-removal-request-lead',
        'contract-signed-to-staff',
        'customer-subscribed-to-staff',
        'new-customer-profile-file-uploaded-to-staff',
    ];

    return hooks()->apply_filters('staff_email_templates', $templates);
}


/**
 * Function that will return in what language the email template should be sent
 * @param  string $template_slug the template slug
 * @param  string $email         email that this template will be sent
 * @deprecated 2.3.0
 * @return string
 */
function get_email_template_language($template_slug, $email)
{
    $CI       = & get_instance();
    $language = get_option('active_language');

    if (total_rows(db_prefix() . 'contacts', [
        'email' => $email,
    ]) > 0 && in_array($template_slug, get_client_email_templates_slugs())) {
        $CI->db->where('email', $email);

        $contact = $CI->db->get(db_prefix() . 'contacts')->row();
        $lang    = get_client_default_language($contact->userid);
        if ($lang != '') {
            $language = $lang;
        }
    } elseif (total_rows(db_prefix() . 'staff', [
            'email' => $email,
        ]) > 0 && in_array($template_slug, get_staff_email_templates_slugs())) {
        $CI->db->where('email', $email);
        $staff = $CI->db->get(db_prefix() . 'staff')->row();

        $lang = get_staff_default_language($staff->staffid);
        if ($lang != '') {
            $language = $lang;
        }
    } elseif (isset($GLOBALS['SENDING_EMAIL_TEMPLATE_CLASS']) || defined('EMAIL_TEMPLATE_PROPOSAL_ID_HELP')) {
        if (defined('EMAIL_TEMPLATE_PROPOSAL_ID_HELP')) {
            $CI->db->select('rel_type,rel_id')
            ->where('id', EMAIL_TEMPLATE_PROPOSAL_ID_HELP);
            $proposal = $CI->db->get(db_prefix() . 'proposals')->row();
        } else {
            $class = $GLOBALS['SENDING_EMAIL_TEMPLATE_CLASS'];

            // check for leads default language
            if ($class->get_rel_type() == 'proposal') {
                $CI->db->select('rel_type,rel_id')
            ->where('id', $class->get_rel_id());
                $proposal = $CI->db->get(db_prefix() . 'proposals')->row();
            } elseif ($class->get_rel_type() == 'lead') {
                $CI->db->select('id, default_language')
            ->where('id', $class->get_rel_id());
                $lead = $CI->db->get(db_prefix() . 'leads')->row();
            }
        }
        if (isset($proposal) && $proposal && $proposal->rel_type == 'lead') {
            $CI->db->select('default_language')
                ->where('id', $proposal->rel_id);

            $lead = $CI->db->get(db_prefix() . 'leads')->row();
        }

        if (isset($lead) && $lead && !empty($lead->default_language)) {
            $language = $lead->default_language;
        }
    }

    return hooks()->apply_filters('email_template_language', $language, ['template_slug' => $template_slug, 'email' => $email]);
}

/**
 * @deprecated 2.3.0
 * @return string
 */
function default_aside_menu_active()
{
    return json_encode([]);
}

/**
 * @deprecated 2.3.0
 * @return string
 */
function add_main_menu_item($options = [], $parent = '')
{
    return false;
}


/**
 * @deprecated 2.3.0
 * @return string
 */
function add_setup_menu_item($options = [], $parent = '')
{
    return false;
}

/**
 * @deprecated 2.3.0
 * @return string
 */
function default_setup_menu_active()
{
    return json_encode([]);
}

if (!function_exists('get_table_items_and_taxes')) {
    /**
     * Function for all table items HTML and PDF
     * @deprecated 2.3.0 use get_items_table_data instead
     * @param  array  $items         all items
     * @param  string  $type          where do items come form, eq invoice,estimate,proposal etc..
     * @param  boolean $admin_preview in admin preview add additional sortable classes
     * @return array
     */
    function get_table_items_and_taxes($items, $type, $admin_preview = false)
    {
        $cf = count($items) > 0 ? get_items_custom_fields_for_table_html($items[0]['rel_id'], $type) : [];

        static $rel_data = null;

        $result['html']    = '';
        $result['taxes']   = [];
        $_calculated_taxes = [];
        $i                 = 1;
        foreach ($items as $item) {

              // No relation data on preview becuase taxes are not saved in database
            if (!defined('INVOICE_PREVIEW_SUBSCRIPTION')) {
                if (!$rel_data) {
                    $rel_data = get_relation_data($item['rel_type'], $item['rel_id']);
                }
            } else {
                $rel_data = $GLOBALS['items_preview_transaction'];
            }

            $item_taxes = [];

            // Separate functions exists to get item taxes for Invoice, Estimate, Proposal, Credit Note
            $func_taxes = 'get_' . $type . '_item_taxes';
            if (function_exists($func_taxes)) {
                $item_taxes = call_user_func($func_taxes, $item['id']);
            }

            $itemHTML        = '';
            $trAttributes    = '';
            $tdFirstSortable = '';

            if ($admin_preview == true) {
                $trAttributes    = ' class="sortable" data-item-id="' . $item['id'] . '"';
                $tdFirstSortable = ' class="dragger item_no"';
            }

            if (class_exists('pdf', false) || class_exists('app_pdf', false)) {
                $font_size = get_option('pdf_font_size');
                if ($font_size == '') {
                    $font_size = 10;
                }

                $trAttributes .= ' style="font-size:' . ($font_size + 4) . 'px;"';
            }

            $itemHTML .= '<tr nobr="true"' . $trAttributes . '>';
            $itemHTML .= '<td' . $tdFirstSortable . ' align="center">' . $i . '</td>';

            $itemHTML .= '<td class="description" align="left;">';
            if (!empty($item['description'])) {
                $itemHTML .= '<span style="font-size:' . (isset($font_size) ? $font_size + 4 : '') . 'px;"><strong>' . $item['description'] . '</strong></span>';

                if (!empty($item['long_description'])) {
                    $itemHTML .= '<br />';
                }
            }
            if (!empty($item['long_description'])) {
                $itemHTML .= '<span style="color:#424242;">' . $item['long_description'] . '</span>';
            }

            $itemHTML .= '</td>';

            foreach ($cf as $custom_field) {
                $itemHTML .= '<td align="left">' . get_custom_field_value($item['id'], $custom_field['id'], 'items') . '</td>';
            }

            $itemHTML .= '<td align="right">' . floatVal($item['qty']);
            if ($item['unit']) {
                $itemHTML .= ' ' . $item['unit'];
            }

            $rate = hooks()->apply_filters(
                'item_preview_rate',
                app_format_number($item['rate']),
                ['item' => $item, 'relation' => $rel_data, 'taxes' => $item_taxes]
            );

            $itemHTML .= '</td>';
            $itemHTML .= '<td align="right">' . $rate . '</td>';
            if (get_option('show_tax_per_item') == 1) {
                $itemHTML .= '<td align="right">';
            }

            if (defined('INVOICE_PREVIEW_SUBSCRIPTION')) {
                $item_taxes = $item['taxname'];
            }

            if (count($item_taxes) > 0) {
                foreach ($item_taxes as $tax) {
                    $calc_tax     = 0;
                    $tax_not_calc = false;

                    if (!in_array($tax['taxname'], $_calculated_taxes)) {
                        array_push($_calculated_taxes, $tax['taxname']);
                        $tax_not_calc = true;
                    }
                    if ($tax_not_calc == true) {
                        $result['taxes'][$tax['taxname']]          = [];
                        $result['taxes'][$tax['taxname']]['total'] = [];
                        array_push($result['taxes'][$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                        $result['taxes'][$tax['taxname']]['tax_name'] = $tax['taxname'];
                        $result['taxes'][$tax['taxname']]['taxrate']  = $tax['taxrate'];
                    } else {
                        array_push($result['taxes'][$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                    }
                    if (get_option('show_tax_per_item') == 1) {
                        $item_tax = '';
                        if ((count($item_taxes) > 1 && get_option('remove_tax_name_from_item_table') == false) || get_option('remove_tax_name_from_item_table') == false || multiple_taxes_found_for_item($item_taxes)) {
                            $tmp      = explode('|', $tax['taxname']);
                            $item_tax = $tmp[0] . ' ' . app_format_number($tmp[1]) . '%<br />';
                        } else {
                            $item_tax .= app_format_number($tax['taxrate']) . '%';
                        }

                        $itemHTML .= hooks()->apply_filters('item_tax_table_row', $item_tax, [
                            'item_taxes' => $item_taxes,
                            'item_id'    => $item['id'],
                        ]);
                    }
                }
            } else {
                if (get_option('show_tax_per_item') == 1) {
                    $itemHTML .= hooks()->apply_filters('item_tax_table_row', '0%', [
                            'item_taxes' => $item_taxes,
                            'item_id'    => $item['id'],
                        ]);
                }
            }

            if (get_option('show_tax_per_item') == 1) {
                $itemHTML .= '</td>';
            }

            /**
             * Possible action hook user to include tax in item total amount calculated with the quantiy
             * eq Rate * QTY + TAXES APPLIED
             */

            $item_amount_with_quantity = hooks()->apply_filters(
                'item_preview_amount_with_currency',
            app_format_number(($item['qty'] * $item['rate'])),
            [
                'item'       => $item,
                'item_taxes' => $item_taxes,
            ]
            );

            $itemHTML .= '<td class="amount" align="right">' . $item_amount_with_quantity . '</td>';
            $itemHTML .= '</tr>';
            $result['html'] .= $itemHTML;
            $i++;
        }

        if ($rel_data) {
            foreach ($result['taxes'] as $tax) {
                $total_tax = array_sum($tax['total']);
                if ($rel_data->discount_percent != 0 && $rel_data->discount_type == 'before_tax') {
                    $total_tax_tax_calculated = ($total_tax * $rel_data->discount_percent) / 100;
                    $total_tax                = ($total_tax - $total_tax_tax_calculated);
                } elseif ($rel_data->discount_total != 0 && $rel_data->discount_type == 'before_tax') {
                    $t         = ($rel_data->discount_total / $rel_data->subtotal) * 100;
                    $total_tax = ($total_tax - $total_tax * $t / 100);
                }

                $result['taxes'][$tax['tax_name']]['total_tax'] = $total_tax;
                // Tax name is in format NAME|PERCENT
                $tax_name_array                               = explode('|', $tax['tax_name']);
                $result['taxes'][$tax['tax_name']]['taxname'] = $tax_name_array[0];
            }
        }

        // Order taxes by taxrate
        // Lowest tax rate will be on top (if multiple)
        usort($result['taxes'], function ($a, $b) {
            return $a['taxrate'] - $b['taxrate'];
        });

        $rel_data = null;

        return hooks()->apply_filters('before_return_table_items_html_and_taxes', $result, [
            'items'         => $items,
            'type'          => $type,
            'admin_preview' => $admin_preview,
        ]);
    }
}

/**
 * Custom format number function for the app
 * @deprecated 2.3.0 use app_format_number instead
 * @param  mixed  $total
 * @param  boolean $foce_check_zero_decimals whether to force check
 * @return mixed
 */
function _format_number($total, $foce_check_zero_decimals = false)
{
    return app_format_number($total, $foce_check_zero_decimals);
}

/**
 * Function that will loop through taxes and will check if there is 1 tax or multiple
 * @deprecated 2.3.0 because of typo, use multiple_taxes_found_for_item
 * @param  array $taxes
 * @return boolean
 */
function mutiple_taxes_found_for_item($taxes)
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
 * @deprecated 2.3.0
 * Use theme_assets_url instead
 * Get current template assets url
 * @return string Assets url
 */
function template_assets_url()
{
    return theme_assets_url();
}
/**
 * @deprecated 2.3.0 Use theme_assets_path instead
 * Return active template asset path
 * @return string
 */
function template_assets_path()
{
    return theme_assets_path();
}

if (!function_exists('render_custom_styles')) {
    /**
     * @deprecated
     * Only for backward compatibility in case some old themes are still using this function e.q. in the head
     * This will help to not throw 404 errors
     */
    function render_custom_styles($type)
    {
        return '';
    }
}

/**
 * Format money with 2 decimal based on symbol
 * @deprecated 2.3.2 use app_format_money($total, $currency, $excludeSymbol = false)
 * @param  mixed $total
 * @param  string $symbol Money symbol
 * @return string
 */
function format_money($total, $symbol = '')
{
    _deprecated_function('format_money', '2.3.2', 'app_format_money');

    if (!is_numeric($total) && $total != 0) {
        return $total;
    }

    $CI = &get_instance();
    $CI->db->where('symbol', $symbol);
    $CI->db->limit(1);
    $currency = $CI->db->get(db_prefix() . 'currencies')->row();

    if ($currency) {
        return app_format_money($total, $currency);
    }
    $decimal_separator  = get_option('decimal_separator');
    $thousand_separator = get_option('thousand_separator');
    $currency_placement = get_option('currency_placement');


    $d = get_decimal_places();
    if (get_option('remove_decimals_on_zero') == 1) {
        if (!is_decimal($total)) {
            $d = 0;
        }
    }

    $totalFormatted = number_format($total, $d, $decimal_separator, $thousand_separator);

    $formattedWithCurrency = $currency_placement === 'after' ? $totalFormatted . '' . $symbol : $symbol . '' . $totalFormatted;

    return hooks()->apply_filters('money_after_format_with_currency', $formattedWithCurrency, [
        'total'              => $total,
        'symbol'             => $symbol,
        'decimal_separator'  => $decimal_separator,
        'thousand_separator' => $thousand_separator,
        'currency_placement' => $currency_placement,
        'decimal_places'     => $d,
    ]);
}

/**
 * @deprecated 2.3.0
 * Load app stylesheet based on option
 * Can load minified stylesheet and non minified
 *
 * This function also check if there is my_ prefix stylesheet to load them.
 * If in options is set to load minified files and the filename that is passed do not contain minified version the
 * original file will be used.
 *
 * @param  string $path
 * @param  string $filename
 * @return string
 */
function app_stylesheet($path, $filename)
{
    return get_instance()->app_css->coreStylesheet($path, $filename);
}
/**
 * @deprecated 2.3.0
 * Load app script based on option
 * Can load minified stylesheet and non minified
 *
 * This function also check if there is my_ prefix stylesheet to load them.
 * If in options is set to load minified files and the filename that is passed do not contain minified version the
 * original file will be used.
 *
 * @param  string $path
 * @param  string $filename
 * @return string
 */
function app_script($path, $filename)
{
    return get_instance()->app_scripts->coreScript($path, $filename);
}

/**
 * @deprecated 2.3.2
 */
function add_projects_assets($group = 'admin')
{
    _deprecated_function('add_projects_assets', '2.3.2');

    $CI = &get_instance();

    $CI->app_scripts->add('jquery-comments-js', 'assets/plugins/jquery-comments/js/jquery-comments.min.js', $group);
    $CI->app_scripts->add('jquery-gantt-js', 'assets/plugins/gantt/js/jquery.fn.gantt.min.js', $group);

    $CI->app_css->add('jquery-comments-css', 'assets/plugins/jquery-comments/css/jquery-comments.css', $group);
    $CI->app_css->add('jquery-gantt-css', 'assets/plugins/gantt/css/style.css', $group);
}

/**
 * CHeck missing key from the main english language
 * @param  string $language language to check
 * @return void
 */
function check_missing_language_strings($language)
{
    _deprecated_function('check_missing_language_strings', '2.3.2');

    $langs = [];
    $CI    = & get_instance();
    $CI->lang->load('english_lang', 'english');
    $english = $CI->lang->language;
    $langs[] = [
        'english' => $english,
    ];
    $original      = $english;
    $keys_original = [];
    foreach ($original as $k => $val) {
        $keys_original[$k] = true;
    }
    $CI->lang->is_loaded = [];
    $CI->lang->language  = [];
    $CI->lang->load($language . '_lang', $language);
    $$language = $CI->lang->language;
    $langs[]   = [
        $language => $$language,
    ];
    $CI->lang->is_loaded = [];
    $CI->lang->language  = [];
    $missing_keys        = [];
    for ($i = 0; $i < count($langs); $i++) {
        foreach ($langs[$i] as $lang => $data) {
            if ($lang != 'english') {
                $keys_current = [];
                foreach ($data as $k => $v) {
                    $keys_current[$k] = true;
                }
                foreach ($keys_original as $k_original => $val_original) {
                    if (!array_key_exists($k_original, $keys_current)) {
                        $keys_missing = true;
                        array_push($missing_keys, $k_original);
                        echo '<b>Missing language key</b> from language:' . $lang . ' - <b>key</b>:' . $k_original . '<br />';
                    }
                }
            }
        }
    }
    if (isset($keys_missing)) {
        echo '<br />--<br />Language keys missing please create <a href="https://help.perfexcrm.com/overwrite-translation-text/" target="_blank">custom_lang.php</a> and add the keys listed above.';
        echo '<br /> Here is how you should add the keys (You can just copy paste this text above and add your translations)<br /><br />';
        foreach ($missing_keys as $key) {
            echo '$lang[\'' . $key . '\'] = \'Add your translation\';<br />';
        }
    } else {
        echo '<h1>No Missing Language Keys Found</h1>';
    }
    die;
}

/**
 * @deprecated 2.3.2 use log_activity ($description, $staffid = null) instead
 * Log Activity for everything
 * @param  string $description Activity Description
 * @param  integer $staffid    Who done this activity
 */
function logActivity($description, $staffid = null)
{
    _deprecated_function('logActivity', '2.3.2', 'log_activity');

    log_activity($description, $staffid);
}


/**
 * @deprecated 2.3.3
 * All permissions available in the app with conditions
 * @return array
 */
function get_permission_conditions()
{
    return hooks()->apply_filters('staff_permissions_conditions', [
        'contracts' => [
            'view'     => true,
            'view_own' => true,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'leads' => [
            'view'        => true,
            'view_own'    => false,
            'edit'        => false,
            'create'      => false,
            'delete'      => true,
            'help'        => _l('help_leads_permission_view'),
            'help_create' => _l('help_leads_create_permission'),
            'help_edit'   => _l('help_leads_edit_permission'),
        ],
        'tasks' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
            'help'     => _l('help_tasks_permissions'),
        ],
        'checklist_templates' => [
            'view'     => false,
            'view_own' => false,
            'edit'     => false,
            'create'   => true,
            'delete'   => true,
        ],
        'reports' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => false,
            'create'   => false,
            'delete'   => false,
        ],
        'settings' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => true,
            'create'   => false,
            'delete'   => false,
        ],
        'projects' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
            'help'     => _l('help_project_permissions'),
        ],
        'subscriptions' => [
            'view'     => true,
            'view_own' => true,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'staff' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'customers' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'email_templates' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => true,
            'create'   => false,
            'delete'   => false,
        ],
        'roles' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'expenses' => [
            'view'     => true,
            'view_own' => true,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'bulk_pdf_exporter' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => false,
            'create'   => false,
            'delete'   => false,
        ],
        'knowledge_base' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'proposals' => [
            'view'     => true,
            'view_own' => true,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'estimates' => [
            'view'     => true,
            'view_own' => true,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'payments' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'invoices' => [
            'view'     => true,
            'view_own' => true,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'credit_notes' => [
            'view'     => true,
            'view_own' => true,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
        'items' => [
            'view'     => true,
            'view_own' => false,
            'edit'     => true,
            'create'   => true,
            'delete'   => true,
        ],
    ]);
}
