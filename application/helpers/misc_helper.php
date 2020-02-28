<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Return locale for media usafe plugin
 * @return string
 */
function get_media_locale()
{
    return \app\services\utilities\Locale::getElFinderLangKey($GLOBALS['locale']);
}

/**
 * Tinymce language set can be complicated and this function will scan the available languages
 * Will return lang filename in the tinymce plugins folder if found or if $locale is en will return just en
 * @param  string $locale
 * @return string
 */
function get_tinymce_language($locale)
{
    return app\services\utilities\Locale::getTinyMceLangKey($locale, list_files(FCPATH . 'assets/plugins/tinymce/langs/'));
}

/**
 * Replace google drive links with actual a tag
 * @param  string $text
 * @return string
 */
function handle_google_drive_links_in_text($text)
{
    $pattern = '#\bhttps?://drive.google.com[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#';
    preg_match_all($pattern, $text, $matchGoogleDriveLinks);

    if (isset($matchGoogleDriveLinks[0]) && is_array($matchGoogleDriveLinks[0])) {
        foreach ($matchGoogleDriveLinks[0] as $driveLink) {
            $link = '<a href="' . $driveLink . '">' . $driveLink . '</a>';
            $text = str_replace($driveLink, $link, $text);
            $text = str_replace('<' . $link . '>', $link, $text);
        }
    }

    return $text;
}
/**
 * Get system favourite colors
 * @return array
 */
function get_system_favourite_colors()
{
    // don't delete any of these colors are used all over the system
    $colors = [
        '#28B8DA',
        '#03a9f4',
        '#c53da9',
        '#757575',
        '#8e24aa',
        '#d81b60',
        '#0288d1',
        '#7cb342',
        '#fb8c00',
        '#84C529',
        '#fb3b3b',
    ];

    return hooks()->apply_filters('system_favourite_colors', $colors);
}

function process_digital_signature_image($partBase64, $path)
{
    if (empty($partBase64)) {
        return false;
    }

    _maybe_create_upload_path($path);
    $filename = unique_filename($path, 'signature.png');

    $decoded_image = base64_decode($partBase64);

    $retval = false;

    $path = rtrim($path, '/') . '/' . $filename;

    $fp = fopen($path, 'w+');

    if (fwrite($fp, $decoded_image)) {
        $retval                                 = true;
        $GLOBALS['processed_digital_signature'] = $filename;
    }

    fclose($fp);

    return $retval;
}

/**
 * Used for estimate and proposal acceptance info array
 * @param  boolean $empty should the array values be empty or taken from $_POST
 * @return array
 */
function get_acceptance_info_array($empty = false)
{
    $CI        = &get_instance();
    $signature = null;

    if (isset($GLOBALS['processed_digital_signature'])) {
        $signature = $GLOBALS['processed_digital_signature'];
        unset($GLOBALS['processed_digital_signature']);
    }

    $data = [
        'signature'            => $signature,
        'acceptance_firstname' => !$empty ? $CI->input->post('acceptance_firstname') : null,
        'acceptance_lastname'  => !$empty ? $CI->input->post('acceptance_lastname') : null,
        'acceptance_email'     => !$empty ? $CI->input->post('acceptance_email'): null,
        'acceptance_date'      => !$empty ? date('Y-m-d H:i:s') : null,
        'acceptance_ip'        => !$empty ? $CI->input->ip_address() : null,
        'acceptance_ip'        => !$empty ? $CI->input->ip_address() : null,
    ];

    return hooks()->apply_filters('acceptance_info_array', $data, $empty);
}

/**
 * For html5 form accepted attributes
 * This function is used for the form attachments
 * @return string
 */
function get_form_accepted_mimes()
{
    $allowed_extensions  = get_option('allowed_files');
    $_allowed_extensions = explode(',', $allowed_extensions);
    $all_form_ext        = '';
    $CI                  = &get_instance();
    // Chrome doing conflict when the regular extensions are appended to the accept attribute which cause top popup
    // to select file to stop opening
    if ($CI->agent->browser() != 'Chrome') {
        $all_form_ext .= $allowed_extensions;
    }
    if (is_array($_allowed_extensions)) {
        if ($all_form_ext != '') {
            $all_form_ext .= ', ';
        }
        foreach ($_allowed_extensions as $ext) {
            $all_form_ext .= get_mime_by_extension($ext) . ', ';
        }
    }

    $all_form_ext = rtrim($all_form_ext, ', ');

    return $all_form_ext;
}

/**
 * CLear the session for the setup menu to be open
 * @return null
 */
function close_setup_menu()
{
    get_instance()->session->set_userdata([
        'setup-menu-open' => '',
    ]);
}

/**
 * Add http to url
 * @param  string $url url to add http
 * @return string
 */
function maybe_add_http($url)
{
    if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
        $url = 'http://' . $url;
    }

    return $url;
}
/**
 * Return specific alert bootstrap class
 * @return string
 */
function get_alert_class()
{
    $CI          = &get_instance();
    $alert_class = '';
    if ($CI->session->flashdata('message-success')) {
        $alert_class = 'success';
    } elseif ($CI->session->flashdata('message-warning')) {
        $alert_class = 'warning';
    } elseif ($CI->session->flashdata('message-info')) {
        $alert_class = 'info';
    } elseif ($CI->session->flashdata('message-danger')) {
        $alert_class = 'danger';
    }

    return hooks()->apply_filters('alert_class', $alert_class);
}

/**
 * Generate random alpha numeric string
 * @param  integer $length the length of the string
 * @return string
 */
function generate_two_factor_auth_key()
{
    $key  = '';
    $keys = array_merge(range(0, 9), range('a', 'z'));

    for ($i = 0; $i < 16; $i++) {
        $key .= $keys[array_rand($keys)];
    }

    $key .= uniqid();

    return $key;
}
/**
 * Function that will replace the dropbox link size for the images
 * This function is used to preview dropbox image attachments
 * @param  string $url
 * @param  string $bounding_box
 * @return string
 */
function optimize_dropbox_thumbnail($url, $bounding_box = '800')
{
    $url = str_replace('bounding_box=75', 'bounding_box=' . $bounding_box, $url);

    return $url;
}
/**
 * Prepare label when splitting weeks for charts
 * @param  array $weeks week
 * @param  mixed $week  week day - number
 * @return string
 */
function split_weeks_chart_label($weeks, $week)
{
    return \app\services\utilities\Date::splitWeeksChartLabel($weeks, $week);
}
/**
 * Get ranges weeks between 2 dates
 * @param  object $start_time date object
 * @param  objetc $end_time   date object
 * @return array
 */
function get_weekdays_between_dates($start_time, $end_time)
{
    return \app\services\utilities\Date::weekdaysBetweenDates($start_time, $end_time);
}

/**
 * Check whether the knowledge base can be viewed
 * @param  boolean $excludeStaff exclude this check for staff member
 * @return boolean
 */
function is_knowledge_base_viewable($excludeStaff = false)
{
    return (get_option('use_knowledge_base') == 1 && !is_client_logged_in() && get_option('knowledge_base_without_registration') == 1) || (get_option('use_knowledge_base') == 1 && is_client_logged_in()) || ($excludeStaff === false && is_staff_logged_in());
}

function _prepare_attachments_array_for_export($attachments)
{
    foreach ($attachments as $key => $item) {
        unset($attachments[$key]['id']);
        unset($attachments[$key]['visible_to_customer']);
        unset($attachments[$key]['staffid']);
        unset($attachments[$key]['contact_id']);
        unset($attachments[$key]['task_comment_id']);
    }

    return array_values($attachments);
}

function _prepare_items_array_for_export($items, $type)
{
    $cf = count($items) > 0 ? get_items_custom_fields_for_table_html($items[0]['rel_id'], $type) : [];

    foreach ($items as $key => $item) {
        $taxes     = [];
        $taxesFunc = 'get_' . $type . '_item_taxes';
        if (function_exists($taxesFunc)) {
            $taxes = call_user_func($taxesFunc, $item['id']);
            foreach ($taxes as $taxKey => $tax) {
                $t = explode('|', $tax['taxname']);

                $taxes[$taxKey]['taxname'] = $t[0];
                $taxes[$taxKey]['taxrate'] = $t[1];
            }
        }

        $items[$key]['tax']               = $taxes;
        $items[$key]['additional_fields'] = [];

        foreach ($cf as $custom_field) {
            $items[$key]['additional_fields'] = [
                 'name'  => $custom_field['name'],
                 'value' => get_custom_field_value($item['id'], $custom_field['id'], 'items'),
                ];
        }
    }

    return $items;
}

/**
 * Helper function to get all knowledge base groups in the parents groups
 * @param  boolean $only_customers prevent showing internal kb articles in customers area
 * @param  array   $where
 * @return array
 */
function get_all_knowledge_base_articles_grouped($only_customers = true, $where = [])
{
    $CI = & get_instance();
    $CI->load->model('knowledge_base_model');
    $groups = $CI->knowledge_base_model->get_kbg('', 1);
    $i      = 0;
    foreach ($groups as $group) {
        $CI->db->select('slug,subject,description,' . db_prefix() . 'knowledge_base.active as active_article,articlegroup,articleid,staff_article');
        $CI->db->from(db_prefix() . 'knowledge_base');
        $CI->db->where('articlegroup', $group['groupid']);
        $CI->db->where('active', 1);
        if ($only_customers == true) {
            $CI->db->where('staff_article', 0);
        }
        $CI->db->where($where);
        $CI->db->order_by('article_order', 'asc');
        $articles = $CI->db->get()->result_array();
        if (count($articles) == 0) {
            unset($groups[$i]);
            $i++;

            continue;
        }
        $groups[$i]['articles'] = $articles;
        $i++;
    }

    return array_values($groups);
}
/**
 * Helper function to get all knowledbase groups
 * @return array
 */
function get_kb_groups()
{
    $CI = & get_instance();

    return $CI->db->get(db_prefix() . 'knowledge_base_groups')->result_array();
}
/**
 * Helper function to get all announcements for user
 * @param  boolean $staff Is this client or staff
 * @return array
 */
function get_announcements_for_user($staff = true)
{
    if (!is_logged_in()) {
        return [];
    }

    $CI = & get_instance();
    $CI->db->select();

    if ($staff == true) {
        $CI->db->where('announcementid NOT IN (SELECT announcementid FROM ' . db_prefix() . 'dismissed_announcements WHERE staff=1 AND userid = ' . get_staff_user_id() . ') AND showtostaff = 1');
    } else {
        $contact_id = get_contact_user_id();
        if (!is_client_logged_in()) {
            return [];
        }

        if ($contact_id) {
            $CI->db->where('announcementid NOT IN (SELECT announcementid FROM ' . db_prefix() . 'dismissed_announcements WHERE staff=0 AND userid = ' . $contact_id . ') AND showtousers = 1');
        } else {
            return [];
        }
    }
    $CI->db->order_by('dateadded', 'desc');
    $announcements = $CI->db->get(db_prefix() . 'announcements');
    if ($announcements) {
        return $announcements->result_array();
    }

    return [];
}

/**
 * Set update message after successfully update
 * @param integer $version the latest version number from the migration config
 */
function app_set_update_message_info($version)
{
    update_option('update_info_message', '
        <div class="col-md-12">
            <div class="alert alert-success bold">
                <h4 class="bold">Hi! Thanks for updating Perfex CRM - You are using version ' . wordwrap($version, 1, '.', true) . '</h4>
                <p>
                   This window will reload automaticaly in 10 seconds and will try to clear your browser/cloudflare cache, however its recomended to clear your browser cache manually.
                </p>
            </div>
        </div>
        <script>
        setTimeout(function(){
            window.location.reload();
        },10000);
        </script>');
}

/**
 * Set pipe.php file permissions to 0755 after update
 */
function app_set_pipe_php_permissions()
{
    if (file_exists(FCPATH . 'pipe.php')) {
        @chmod(FCPATH . 'pipe.php', 0755);
    }
}

/**
 * Custom function to check whether the imap_open function exists
 * Can happen imap to be enabled but the imap_open function is added in disabled function list
 * @param  string $redirectOnError URL to redirect on error
 * @return mixed
 */
function app_check_imap_open_function($redirectOnError = null)
{
    if (!function_exists('imap_open')) {
        $CI      = &get_instance();
        $message = 'Function \'imap_open\' does not exists, this can happen if PHP IMAP extension is not enabled or the function is disabled from your hosting provider.';
        if ($CI->input->is_ajax_request()) {
            echo json_encode([
                'alert_type' => 'danger',
                'message'    => $message,
            ]);
            die;
        }
        set_alert('danger', $message);
        redirect(($redirectOnError ? $redirectOnError : $_SERVER['HTTP_REFERER']));
    }
}

/**
 * Solves the issue where the user is trying to login into the clients area as staff
 * Hook added in core_hooks_helper.php
 * @since  2.3.2
 * @param  array $data  hook data
 * @return mixed
 */
function _maybe_user_is_trying_to_login_into_the_clients_area_as_staff($data)
{
    $dateInstallation = get_option('di');

    // Should not happen but do nothing in this case
    if ($dateInstallation == '') {
        return;
    }

    // Check if the date installation is older then 1 week, this check is only available in the first week
    // Because most of the users do this mistake in the first week
    if ($dateInstallation <= time() - (60 * 60 * 24 * 7 * 1)) {
        return;
    }

    // Exists as staff member, but not exists as contact
    if (total_rows('staff', ['email' => $data['email']]) > 0
        && total_rows('contacts', ['email' => $data['email']]) == 0) {
        get_instance()->session->set_flashdata('mistaken_login_area_check_performed', '1');
    }
}

/**
 * Show the mistaken login area
 * Hook added in core_hooks_helper.php
 * @since  2.3.2
 * @return mixed
 */
function _maybe_mistaken_login_area_check_performed()
{
    if (get_instance()->session->flashdata('mistaken_login_area_check_performed') === '1') {
        echo '<div class="alert alert-warning">';
        echo '<h4>Temporary Message</h4>';
        echo '<b>It looks like yo are trying to login as admin/staff member in the clients area.</b><br /><br />';
        echo 'Administrators/staff <b>must</b> log in at <a href="' . admin_url() . '">' . admin_url() . '</a><br /><br />';
        echo 'Customer contacts <b>must</b> login at <a href="' . site_url() . '">' . site_url() . '</a><br />';
        echo '</div>';
    }
}

/**
 * On each update there is message/code inserted in the database
 */
function _maybe_show_just_updated_message()
{
    if (get_option('update_info_message') != '') {
        if (is_admin()) {
            $message = get_option('update_info_message');
            update_option('update_info_message', '');
            echo $message;
        }
    }
}

function show_pdf_unable_to_get_image_size_error()
{
    ?>
    <div style="font-size:17px;">
       <hr />
       <p>This error can be shown if the <b>PDF library can't read the image from your server</b>.</p>
       <p>Very often this is happening <b>when you are using custom PDF logo url in Setup -> Settings -> PDF</b>, first make sure that the url you added in Setup->Settings->PDF for the custom pdf logo is valid and the image exists if the problem still exists you will need to use a <b>direct path</b> to the image to include in the PDF documents. Follow the steps mentioned below:</p>
       <p><strong>Method 1 (easy)</strong></p>
       <ul>
        <li>Upload the logo image in the installation directory eq. <?php echo FCPATH; ?>mylogo.jpg</li>
        <li><a href="<?php echo admin_url('settings?group=pdf'); ?>" target="_blank">Navigate to Setup -> Settings -> PDF</a> -> Custom PDF Company Logo URL and only add the filename like: <b>mylogo.jpg</b>, now Custom PDF Company Logo URL should be only filename not full URL.</li>
        <li>Try to re-generate PDF document again.</li>
    </ul>
    <p><strong>Method 2 (advanced)</strong></p>
    <small>Try this method if method 1 is still not working.</small>
    <ul>
        <li>Consult with your hosting provider to confirm that the server is able to use PHP's <a href="http://php.net/manual/en/function.file-get-contents.php" target="_blank">file_get_contents</a> or <a href="http://php.net/manual/en/curl.examples-basic.php" target="_blank">cUrl</a> to download the file. </li>
        <li>Try to re-generate PDF document again.</li>
    </ul>
    <?php if (strpos($_SERVER['REQUEST_URI'], '/proposals') !== false) {
        ?>
        <hr />
        <p>Additionally, if this PDF document is proposal, you may need to re-check if any images added inside the proposal content are broken, make sure that the images URL are actually valid.</p>
        <?php
    } ?>
</div>
<?php
}
