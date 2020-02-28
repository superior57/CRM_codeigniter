<?php

defined('BASEPATH') or exit('No direct script access allowed');
header('Content-Type: text/html; charset=utf-8');

/**
 * Check if the document should be RTL or LTR
 * The checking are performed in multiple ways eq Contact/Staff Direction from profile or from general settings *
 * @param  boolean $client_area
 * @return boolean
 */
function is_rtl($client_area = false)
{
    $CI = & get_instance();
    if (is_client_logged_in()) {
        $CI->db->select('direction')->from(db_prefix() . 'contacts')->where('id', get_contact_user_id());
        $direction = $CI->db->get()->row()->direction;

        if ($direction == 'rtl') {
            return true;
        } elseif ($direction == 'ltr') {
            return false;
        } elseif (empty($direction)) {
            if (get_option('rtl_support_client') == 1) {
                return true;
            }
        }

        return false;
    } elseif ($client_area == true) {
        // Client not logged in and checked from clients area
        if (get_option('rtl_support_client') == 1) {
            return true;
        }
    } elseif (is_staff_logged_in()) {
        if (isset($GLOBALS['current_user'])) {
            $direction = $GLOBALS['current_user']->direction;
        } else {
            $CI->db->select('direction')->from(db_prefix() . 'staff')->where('staffid', get_staff_user_id());
            $direction = $CI->db->get()->row()->direction;
        }

        if ($direction == 'rtl') {
            return true;
        } elseif ($direction == 'ltr') {
            return false;
        } elseif (empty($direction)) {
            if (get_option('rtl_support_admin') == 1) {
                return true;
            }
        }

        return false;
    } elseif ($client_area == false) {
        if (get_option('rtl_support_admin') == 1) {
            return true;
        }
    }

    return false;
}

/**
 * Check whether the data is intended to be shown for the customer
 * For example this function is used for custom fields, pdf language loading etc...
 * @return boolean
 */
function is_data_for_customer()
{
    return is_client_logged_in()
            || (!is_staff_logged_in() && !is_client_logged_in())
            || defined('SEND_MAIL_TEMPLATE')
            || defined('CLIENTS_AREA')
            || defined('GDPR_EXPORT');
}

/**
 * Generate encryption key for app-config.php
 * @return stirng
 */
function generate_encryption_key()
{
    $CI = & get_instance();
    // In case accessed from my_functions_helper.php
    $CI->load->library('encryption');
    $key = bin2hex($CI->encryption->create_key(16));

    return $key;
}

/**
 * Return application version formatted
 * @return string
 */
function get_app_version()
{
    $CI = &get_instance();
    $CI->load->config('migration');

    return wordwrap($CI->config->item('migration_version'), 1, '.', true);
}

/**
 * Set current full url to for user to be redirected after login
 * Check below function to see why is this
 */
function redirect_after_login_to_current_url()
{
    $redirectTo = current_full_url();

    // This can happen if at the time you received a notification but your session was expired the system stored this as last accessed URL so after login can redirect you to this URL.
    if (strpos($redirectTo, 'notifications_check') !== false) {
        return;
    }

    get_instance()->session->set_userdata([
        'red_url' => $redirectTo,
    ]);
}
/**
* Check if user accessed url while not logged in to redirect after login
* @return null
*/
function maybe_redirect_to_previous_url()
{
    $CI = &get_instance();
    if ($CI->session->has_userdata('red_url')) {
        $red_url = $CI->session->userdata('red_url');
        $CI->session->unset_userdata('red_url');
        redirect($red_url);
    }
}
/**
 * Function used to validate all recaptcha from google reCAPTCHA feature
 * @param  string $str
 * @return boolean
 */
function do_recaptcha_validation($str = '')
{
    $CI = & get_instance();
    $CI->load->library('form_validation');
    $google_url = 'https://www.google.com/recaptcha/api/siteverify';
    $secret     = get_option('recaptcha_secret_key');
    $ip         = $CI->input->ip_address();
    $url        = $google_url . '?secret=' . $secret . '&response=' . $str . '&remoteip=' . $ip;
    $curl       = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($curl);
    curl_close($curl);
    $res = json_decode($res, true);
    //reCaptcha success check
    if ($res['success']) {
        return true;
    }
    $CI->form_validation->set_message('recaptcha', _l('recaptcha_error'));

    return false;
}
/**
 * Get current date format from options
 * @return string
 */
function get_current_date_format($php = false)
{
    $format = get_option('dateformat');
    $format = explode('|', $format);

    $format = hooks()->apply_filters('get_current_date_format', $format, $php);

    if ($php == false) {
        return $format[1];
    }

    return $format[0];
}
/**
 * Is user logged in
 * @return boolean
 */
function is_logged_in()
{
    return (is_client_logged_in() || is_staff_logged_in());
}
/**
 * Is client logged in
 * @return boolean
 */
function is_client_logged_in()
{
    return get_instance()->session->has_userdata('client_logged_in');
}
/**
 * Is staff logged in
 * @return boolean
 */
function is_staff_logged_in()
{
    return get_instance()->session->has_userdata('staff_logged_in');
}
/**
 * Return logged staff User ID from session
 * @return mixed
 */
function get_staff_user_id()
{
    $CI = & get_instance();

    if (defined('API')) {
        $CI->load->config('rest');

        $api_key_variable = $CI->config->item('rest_key_name');
        $key_name         = 'HTTP_' . strtoupper(str_replace('-', '_', $api_key_variable));

        if ($key = $CI->input->server($key_name)) {
            $CI->db->where('key', $key);
            $key = $CI->db->get($CI->config->item('rest_keys_table'))->row();
            if ($key) {
                return $key->user_id;
            }
        }
    }

    if (!is_staff_logged_in()) {
        return false;
    }

    return $CI->session->userdata('staff_user_id');
}
/**
 * Return logged client User ID from session
 * @return mixed
 */
function get_client_user_id()
{
    if (!is_client_logged_in()) {
        return false;
    }

    return get_instance()->session->userdata('client_user_id');
}

/**
 * Get contact user id
 * @return mixed
 */
function get_contact_user_id()
{
    $CI = & get_instance();
    if (!$CI->session->has_userdata('contact_user_id')) {
        return false;
    }

    return $CI->session->userdata('contact_user_id');
}
/**
 * Get timezones list
 * @return array timezones
 */
function get_timezones_list()
{
    return app\services\Timezones::get();
}

/**
 * Check if visitor is on mobile
 * @return boolean
 */
function is_mobile()
{
    if (get_instance()->agent->is_mobile()) {
        return true;
    }

    return false;
}
/**
 * Set session alert / flashdata
 * @param string $type    Alert type
 * @param string $message Alert message
 */
function set_alert($type, $message)
{
    get_instance()->session->set_flashdata('message-' . $type, $message);
}
/**
 * Redirect to blank admin page
 * @param  string $message Alert message
 * @param  string $alert   Alert type
 */
function blank_page($message = '', $alert = 'danger')
{
    set_alert($alert, $message);
    redirect(admin_url('not_found'));
}
/**
 * Redirect to access danied page and log activity
 * @param  string $permission If permission based to check where user tried to acces
 */
function access_denied($permission = '')
{
    set_alert('danger', _l('access_denied'));

    log_activity('Tried to access page where don\'t have permission' . ($permission != '' ? ' [' . $permission . ']' : ''));

    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        redirect($_SERVER['HTTP_REFERER']);
    } else {
        redirect(admin_url('access_denied'));
    }
}
/**
 * Throws header 401 not authorized, used for ajax requests
 */
function ajax_access_denied()
{
    header('HTTP/1.0 401 Unauthorized');
    echo _l('access_denied');
    die;
}
/**
 * Set debug message - message wont be hidden in X seconds from javascript
 * @since  Version 1.0.1
 * @param string $message debug message
 */
function set_debug_alert($message)
{
    get_instance()->session->set_flashdata('debug', $message);
}

/**
 * System popup message for admin area
 * This is used to show some general message for user within a big full screen div with white background
 * @param string $message message for the system popup
 */
function set_system_popup($message)
{
    if (!is_admin()) {
        return false;
    }

    if (defined('APP_DISABLE_SYSTEM_STARTUP_HINTS') && APP_DISABLE_SYSTEM_STARTUP_HINTS) {
        return false;
    }

    get_instance()->session->set_userdata([
        'system-popup' => $message,
    ]);
}
/**
 * Available date formats
 * @return array
 */
function get_available_date_formats()
{
    $date_formats = [
        'd-m-Y|%d-%m-%Y' => 'd-m-Y',
        'd/m/Y|%d/%m/%Y' => 'd/m/Y',
        'm-d-Y|%m-%d-%Y' => 'm-d-Y',
        'm.d.Y|%m.%d.%Y' => 'm.d.Y',
        'm/d/Y|%m/%d/%Y' => 'm/d/Y',
        'Y-m-d|%Y-%m-%d' => 'Y-m-d',
        'd.m.Y|%d.%m.%Y' => 'd.m.Y',
    ];

    return hooks()->apply_filters('available_date_formats', $date_formats);
}
/**
 * Get weekdays as array
 * @return array
 */
function get_weekdays()
{
    return [
        _l('wd_monday'),
        _l('wd_tuesday'),
        _l('wd_wednesday'),
        _l('wd_thursday'),
        _l('wd_friday'),
        _l('wd_saturday'),
        _l('wd_sunday'),
    ];
}
/**
 * Get non translated week days for query help
 * Do not edit this
 * @return array
 */
function get_weekdays_original()
{
    return [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];
}
/**
 * Outputs language string based on passed line
 * @since  Version 1.0.1
 * @param  string $line   language line key
 * @param  mixed $label   sprint_f label
 * @return string         language text
 */
function _l($line, $label = '', $log_errors = true)
{
    $CI = & get_instance();

    $hook_data = hooks()->apply_filters('before_get_language_text', ['line' => $line, 'label' => $label]);

    $line  = $hook_data['line'];
    $label = $hook_data['label'];

    if (is_array($label) && count($label) > 0) {
        $_line = vsprintf($CI->lang->line(trim($line), $log_errors), $label);
    } else {
        $_line = @sprintf($CI->lang->line(trim($line), $log_errors), $label);
    }

    $hook_data = hooks()->apply_filters('after_get_language_text', ['line' => $line, 'formatted_line' => $_line]);

    $_line = $hook_data['formatted_line'];
    $line  = $hook_data['line'];

    if ($_line != '') {
        if (preg_match('/"/', $_line) && !is_html($_line)) {
            $_line = html_escape($_line);
        }

        return ForceUTF8\Encoding::toUTF8($_line);
    }

    if (mb_strpos($line, '_db_') !== false) {
        return 'db_translate_not_found';
    }

    return ForceUTF8\Encoding::toUTF8($line);
}

/**
 * Format date to selected dateformat
 * @param  date $date Valid date
 * @return date/string
 */
function _d($date)
{
    $formatted = '';

    if ($date == '' || is_null($date) || $date == '0000-00-00') {
        return $formatted;
    }

    if (strpos($date, ' ') !== false) {
        return _dt($date);
    }

    $format    = get_current_date_format();
    $formatted = strftime($format, strtotime($date));

    return hooks()->apply_filters('after_format_date', $formatted, $date);
}

/**
 * Format datetime to selected datetime format
 * @param  datetime $date datetime date
 * @return datetime/string
 */
function _dt($date, $is_timesheet = false)
{
    $original = $date;

    if ($date == '' || is_null($date) || $date == '0000-00-00 00:00:00') {
        return '';
    }

    $format = get_current_date_format();
    $hour12 = (get_option('time_format') == 24 ? false : true);

    if ($is_timesheet == false) {
        $date = strtotime($date);
    }

    if ($hour12 == false) {
        $tf = '%H:%M:%S';
        if ($is_timesheet == true) {
            $tf = '%H:%M';
        }
        $date = strftime($format . ' ' . $tf, $date);
    } else {
        $date = date(get_current_date_format(true) . ' g:i A', $date);
    }

    return hooks()->apply_filters('after_format_datetime', $date, ['original' => $original, 'is_timesheet' => $is_timesheet]);
}

/**
 * Convert string to sql date based on current date format from options
 * @param  string $date date string
 * @return mixed
 */
function to_sql_date($date, $datetime = false)
{
    if ($date == '' || $date == null) {
        return null;
    }

    $to_date     = 'Y-m-d';
    $from_format = get_current_date_format(true);

    $date = hooks()->apply_filters('before_sql_date_format', $date, [
        'from_format' => $from_format,
        'is_datetime' => $datetime,
    ]);

    if ($datetime == false) {
        return hooks()->apply_filters('to_sql_date_formatted', date_format(date_create_from_format($from_format, $date), $to_date));
    }

    if (strpos($date, ' ') === false) {
        $date .= ' 00:00:00';
    } else {
        $hour12 = (get_option('time_format') == 24 ? false : true);
        if ($hour12 == false) {
            $_temp = explode(' ', $date);
            $time  = explode(':', $_temp[1]);
            if (count($time) == 2) {
                $date .= ':00';
            }
        } else {
            $tmp  = _simplify_date_fix($date, $from_format);
            $time = date('G:i', strtotime($tmp));
            $tmp  = explode(' ', $tmp);
            $date = $tmp[0] . ' ' . $time . ':00';
        }
    }

    $date = _simplify_date_fix($date, $from_format);
    $d    = strftime('%Y-%m-%d %H:%M:%S', strtotime($date));

    return hooks()->apply_filters('to_sql_date_formatted', $d);
}

/**
 * Function that will check the date before formatting and replace the date places
 * This function is custom developed because for some date formats converting to y-m-d format is not possible
 * @param  string $date        the date to check
 * @param  string $from_format from format
 * @return string
 */
function _simplify_date_fix($date, $from_format)
{
    if ($from_format == 'd/m/Y') {
        $date = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$2-$1 $4', $date);
    } elseif ($from_format == 'm/d/Y') {
        $date = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$1-$2 $4', $date);
    } elseif ($from_format == 'm.d.Y') {
        $date = preg_replace('#(\d{2}).(\d{2}).(\d{4})\s(.*)#', '$3-$1-$2 $4', $date);
    } elseif ($from_format == 'm-d-Y') {
        $date = preg_replace('#(\d{2})-(\d{2})-(\d{4})\s(.*)#', '$3-$1-$2 $4', $date);
    }

    return $date;
}
/**
 * Check if passed string is valid date
 * @param  string  $date
 * @return boolean
 */
function is_date($date)
{
    if (strlen($date) < 10) {
        return false;
    }

    return (bool) strtotime($date);
}
/**
 * Get available locaes predefined for the system
 * If you add a language and the locale do not exist in this array you can use action hook to add new locale
 * @return array
 */
function get_locales()
{
    $locales = \app\services\utilities\Locale::app();

    return hooks()->apply_filters('before_get_locales', $locales);
}
/**
 * Get locale key by system language
 * @param  string $language language name from (application/languages) folder name
 * @return string
 */
function get_locale_key($language = 'english')
{
    $locale = \app\services\utilities\Locale::getByLanguage($language);

    return hooks()->apply_filters('before_get_locale', $locale);
}
/**
 * Get current url with query vars
 * @return string
 */
function current_full_url()
{
    $CI  = & get_instance();
    $url = $CI->config->site_url($CI->uri->uri_string());

    return $_SERVER['QUERY_STRING'] ? $url . '?' . $_SERVER['QUERY_STRING'] : $url;
}
/**
 * Triggers
 * @param  array  $users id of users to receive notifications
 * @return null
 */
function pusher_trigger_notification($users = [])
{
    if (get_option('pusher_realtime_notifications') == 0) {
        return false;
    }

    if (!is_array($users) || count($users) == 0) {
        return false;
    }

    $channels = [];
    foreach ($users as $id) {
        array_push($channels, 'notifications-channel-' . $id);
    }

    $channels = array_unique($channels);

    $CI = &get_instance();

    $CI->load->library('app_pusher');

    $CI->app_pusher->trigger($channels, 'notification', []);
}


/**
 * Generate md5 hash
 * @return string
 */
function app_generate_hash()
{
    return md5(rand() . microtime() . time() . uniqid());
}

/**
 * @since  2.3.2
 * Get CSRF formatter for AJAX usage
 * @return array
 */
function get_csrf_for_ajax()
{
    $csrf               = [];
    $csrf['formatted']  = [get_instance()->security->get_csrf_token_name() => get_instance()->security->get_csrf_hash()];
    $csrf['token_name'] = get_instance()->security->get_csrf_token_name();
    $csrf['hash']       = get_instance()->security->get_csrf_hash();

    return $csrf;
}

/**
 * If user have enabled CSRF proctection this function will take care of the ajax requests and append custom header for CSRF
 * @return mixed
 */
function csrf_jquery_token()
{
    ?>
    <script>
        if (typeof (jQuery) === 'undefined' && !window.deferAfterjQueryLoaded) {
            window.deferAfterjQueryLoaded = [];
            Object.defineProperty(window, "$", {
                set: function (value) {
                    window.setTimeout(function () {
                        $.each(window.deferAfterjQueryLoaded, function (index, fn) {
                            fn();
                        });
                    }, 0);
                    Object.defineProperty(window, "$", {
                        value: value
                    });
                },
                configurable: true
            });
        }

        var csrfData = <?php echo json_encode(get_csrf_for_ajax()); ?>;

        if (typeof(jQuery) == 'undefined') {
            window.deferAfterjQueryLoaded.push(function () {
                csrf_jquery_ajax_setup();
            });
            window.addEventListener('load',function(){
                csrf_jquery_ajax_setup();
            }, true);
        } else {
            csrf_jquery_ajax_setup();
        }

        function csrf_jquery_ajax_setup() {
            $.ajaxSetup({
                data: csrfData.formatted
            });
        }
 </script>
 <?php
}

/**
 * In some places of the script we use app_happy_text function to output some words in orange color
 * @param  string $text the text to check
 * @return string
 */
function app_happy_text($text)
{
    $regex = hooks()->apply_filters('app_happy_text_regex', 'congratulations!?|congrats!?|happy!?|feel happy!?|awesome!?|yay!?');
    $re    = '/' . $regex . '/i';

    $app_happy_color = hooks()->apply_filters('app_happy_text_color', 'rgb(255, 59, 0)');

    preg_match_all($re, $text, $matches, PREG_SET_ORDER, 0);
    foreach ($matches as $match) {
        $text = preg_replace(
            '/' . $match[0] . '/i',
            '<span style="color:' . $app_happy_color . ';font-weight:bold;">' . $match[0] . '</span>',
            $text
        );
    }

    return $text;
}

/**
 * Return server temporary directory
 * @return string
 */
function get_temp_dir()
{
    if (function_exists('sys_get_temp_dir')) {
        $temp = sys_get_temp_dir();
        if (@is_dir($temp) && is_writable($temp)) {
            return rtrim($temp, '/\\') . '/';
        }
    }

    $temp = ini_get('upload_tmp_dir');
    if (@is_dir($temp) && is_writable($temp)) {
        return rtrim($temp, '/\\') . '/';
    }

    $temp = app_temp_dir();

    if (is_dir($temp) && is_writable($temp)) {
        return $temp;
    }

    return '/tmp/';
}

/**
 * Creates instance of phpass
 * @since  2.3.1
 * @return object PasswordHash class
 */
function app_hasher()
{
    global $app_hasher;

    if (empty($app_hasher)) {
        require_once(APPPATH . 'third_party/phpass.php');
        // By default, use the portable hash from phpass
        $app_hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);
    }

    return $app_hasher;
}

/**
 * Hashes password for user
 * @since  2.3.1
 * @param  string $password plain password
 * @return string
 */
function app_hash_password($password)
{
    return app_hasher()->HashPassword($password);
}

// TODO
function round_timesheet_time($datetime)
{
    $dt = new DateTime($datetime);
    $r  = 15;
    // echo roundUpToMinuteInterval($dt,$r)->format('Y-m-d H:i:s') . '<br />';
    // echo roundDownToMinuteInterval($dt,$r)->format('Y-m-d H:i:s') . '<br />';
    $datetime = roundUpToMinuteInterval($dt, $r)->format('Y-m-d H:i:s');

    return $datetime;
}

/**
 * @param $dateTime
 * @param int $minuteInterval
 * @return \DateTime
 */
function roundUpToMinuteInterval($dateTime, $minuteInterval = 10)
{
    return $dateTime->setTime(
        $dateTime->format('H'),
        ceil($dateTime->format('i') / $minuteInterval) * $minuteInterval,
        0
    );
}

/**
 * @param $dateTime
 * @param int $minuteInterval
 * @return \DateTime
 */
function roundDownToMinuteInterval($dateTime, $minuteInterval = 10)
{
    return $dateTime->setTime(
        $dateTime->format('H'),
        floor($dateTime->format('i') / $minuteInterval) * $minuteInterval,
        0
    );
}

/**
 * @param $dateTime
 * @param int $minuteInterval
 * @return \DateTime
 */
function roundToNearestMinuteInterval($dateTime, $minuteInterval = 10)
{
    return $dateTime->setTime(
        $dateTime->format('H'),
        round($dateTime->format('i') / $minuteInterval) * $minuteInterval,
        0
    );
}

/**
 * @since  2.3.2
 * Get last upgrade copy data if exists
 * @return mixed
 */
function get_last_upgrade_copy_data()
{
    $lastUpgradeCopyData = get_option('last_upgrade_copy_data');
    if ($lastUpgradeCopyData !== '') {
        $lastUpgradeCopyData = json_decode($lastUpgradeCopyData);

        return is_object($lastUpgradeCopyData) ? $lastUpgradeCopyData : false;
    }

    return false;
}
