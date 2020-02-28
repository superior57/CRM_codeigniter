<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App
{
    /**
     * Options autoload=1
     * @var array
     */
    private $options = [];

    /**
     * Quick actions create aside
     * @var array
     */
    private $quick_actions = [];

    /**
     * CI Instance
     * @deprecated 1.9.8 Use $this->ci instead
     * @var object
     */
    private $_instance;

    /**
     * CI Instance
     * @var object
     */
    private $ci;

    /**
     * Show or hide setup menu
     * @var boolean
     */
    private $show_setup_menu = true;

    /**
     * Available reminders
     * @var array
     */
    private $available_reminders = [
            'customer',
            'lead',
            'estimate',
            'invoice',
            'proposal',
            'expense',
            'credit_note',
            'ticket',
            'task',
    ];

    /**
     * Tables where currency id is used
     * @var array
     */
    private $tables_with_currency = [];

    /**
     * Media folder
     * @var string
     */
    private $media_folder;

    /**
     * Available languages
     * @var array
     */
    private $available_languages = [];

    public function __construct()
    {
        $this->ci = & get_instance();
        // @deprecated
        $this->_instance = $this->ci;

        $this->init();

        hooks()->do_action('app_base_after_construct_action');
    }

    /**
     * Check if database upgrade is required
     * @param  string  $v
     * @return boolean
     */
    public function is_db_upgrade_required($v = '')
    {
        if (!is_numeric($v)) {
            $v = $this->get_current_db_version();
        }

        $this->ci->load->config('migration');
        if ((int) $this->ci->config->item('migration_version') !== (int) $v) {
            return true;
        }

        return false;
    }

    /**
     * Return current database version
     * @return string
     */
    public function get_current_db_version()
    {
        $this->ci->db->limit(1);

        return $this->ci->db->get(db_prefix() . 'migrations')->row()->version;
    }

    /**
     * Upgrade database
     * @return mixed
     */
    public function upgrade_database()
    {

        $update = $this->upgrade_database_silent();

        if ($update['success'] == false) {
            show_error($update['message']);
        } else {
            set_alert('success', 'Your database is up to date');

            if (is_staff_logged_in()) {
                redirect(admin_url(), 'refresh');
            } else {
                redirect(admin_url('authentication'));
            }
        }
    }

    /**
     * Make request to server to get latest version info
     * @return mixed
     */
    public function get_update_info()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT      => $this->ci->agent->agent_string(),
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_URL            => UPDATE_INFO_URL,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => [
                'update_info'     => 'true',
                'current_version' => $this->get_current_db_version(),
                'php_version'     => PHP_VERSION,
                'purchase_key'    => get_option('purchase_key'),
            ],
        ]);

        $result = curl_exec($curl);
        $error  = '';

        if (!$curl || !$result) {
            $error = 'Curl Error - Contact your hosting provider with the following error as reference: Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
        }

        curl_close($curl);

        if ($error != '') {
            return $error;
        }

        return $result;
    }

    /**
     * Return all available languages in the application/language folder
     * @return array
     */
    public function get_available_languages()
    {
        return hooks()->apply_filters('before_get_languages', $this->available_languages);
    }

    /**
     * Function that will parse table data from the tables folder for amin area
     * @param  string $table  table filename
     * @param  array  $params additional params
     * @return void
     */
    public function get_table_data($table, $params = [])
    {
        $params = hooks()->apply_filters('table_params', $params, $table);

        foreach ($params as $key => $val) {
            $$key = $val;
        }

        $customFieldsColumns = [];

        $path = VIEWPATH . 'admin/tables/' . $table . EXT;

        if (!file_exists($path)) {
            $path = $table;
            if (!endsWith($path, EXT)) {
                $path .= EXT;
            }
        } else {
            $myPrefixedPath = VIEWPATH . 'admin/tables/my_' . $table . EXT;
            if (file_exists($myPrefixedPath)) {
                $path = $myPrefixedPath;
            }
        }

        include_once($path);

        echo json_encode($output);
        die;
    }

    /**
     * All available reminders keys for the features
     * @return array
     */
    public function get_available_reminders_keys()
    {
        return $this->available_reminders;
    }

    /**
     * Get all db options
     * @return array
     */
    public function get_options()
    {
        return $this->options;
    }

    /**
     * Function that gets option based on passed name
     * @param  string $name
     * @return string
     */
    public function get_option($name)
    {
        if ($name == 'number_padding_invoice_and_estimate') {
            $name = 'number_padding_prefixes';
        }

        $val  = '';
        $name = trim($name);

        if (!isset($this->options[$name])) {
            // is not auto loaded
            $this->ci->db->select('value');
            $this->ci->db->where('name', $name);
            $row = $this->ci->db->get(db_prefix() . 'options')->row();
            if ($row) {
                $val = $row->value;
            }
        } else {
            $val = $this->options[$name];
        }

        return hooks()->apply_filters('get_option', $val, $name);
    }

    /**
     * Add new quick action data
     * @param array $item
     */
    public function add_quick_actions_link($item = [])
    {
        if (!isset($item['position'])) {
            $item['position'] = null;
        }

        $this->quick_actions[] = $item;
    }

    /**
     * Quick actions data set from core/AdminController.php
     * @return array
     */
    public function get_quick_actions_links()
    {
        return hooks()->apply_filters('quick_actions_links', app_sort_by_position($this->quick_actions));
    }

    /**
     * Aside.php will set the menu visibility here based on few conditions
     * @param int $total_setup_menu_items total setup menu items shown to the user
     */
    public function set_setup_menu_visibility($total_setup_menu_items)
    {
        $this->show_setup_menu = $total_setup_menu_items == 0 ? false : true;
    }

    /**
     * Check if should the script show the setup menu or not
     * @return boolean
     */
    public function show_setup_menu()
    {
        return hooks()->apply_filters('show_setup_menu', $this->show_setup_menu);
    }

    /**
     * Return tables that currency id is used
     * @return array
     */
    public function get_tables_with_currency()
    {
        return hooks()->apply_filters('tables_with_currency', $this->tables_with_currency);
    }

    /**
     * Return the media folder name
     * @return string
     */
    public function get_media_folder()
    {
        return hooks()->apply_filters('get_media_folder', $this->media_folder);
    }

    /**
     * Upgrade database without throwing any errors
     * @return mixed
     */
    private function upgrade_database_silent()
    {
        $this->ci->load->config('migration');

        $beforeUpdateVersion = $this->get_current_db_version();
        $updateToVersion     = $this->ci->config->item('migration_version');

        $this->ci->load->library('migration', [
            'migration_enabled'     => true,
            'migration_type'        => $this->ci->config->item('migration_type'),
            'migration_table'       => $this->ci->config->item('migration_table'),
            'migration_auto_latest' => $this->ci->config->item('migration_auto_latest'),
            'migration_version'     => $updateToVersion,
            'migration_path'        => $this->ci->config->item('migration_path'),
        ]);

        hooks()->do_action('before_update_database', $updateToVersion);
        define('DOING_DATABASE_UPGRADE', true);
        if ($this->ci->migration->current() === false) {
            return [
                'success' => false,
                'message' => $this->ci->migration->error_string(),
            ];
        }

        delete_option('upgraded_from_version');
        add_option('upgraded_from_version', $beforeUpdateVersion);

        hooks()->do_action('database_updated', $updateToVersion);

        return ['success' => true];
    }

    /**
     * Init necessary data
     */
    protected function init()
    {
        // Temporary checking for v1.8.0
        if ($this->ci->db->field_exists('autoload', db_prefix() . 'options')) {
            $options = $this->ci->db->select('name, value')
            ->where('autoload', 1)
            ->get(db_prefix() . 'options')->result_array();
        } else {
            $options = $this->ci->db->select('name, value')
            ->get(db_prefix() . 'options')->result_array();
        }

        // Loop the options and store them in a array to prevent fetching again and again from database
        foreach ($options as $option) {
            $this->options[$option['name']] = $option['value'];
        }

        /**
         * Available languages
         */
        foreach (list_folders(APPPATH . 'language') as $language) {
            if (is_dir(APPPATH . 'language/' . $language)) {
                array_push($this->available_languages, $language);
            }
        }

        /**
         * Media folder
         * @var string
         */
        $this->media_folder = hooks()->apply_filters('before_set_media_folder', 'media');

        /**
         * Tables with currency
         * @var array
         */
        $this->tables_with_currency = [
            [
                'table' => db_prefix() . 'invoices',
                'field' => 'currency',
            ],
            [
                'table' => db_prefix() . 'expenses',
                'field' => 'currency',
            ],
            [
                'table' => db_prefix() . 'proposals',
                'field' => 'currency',
            ],
            [
                'table' => db_prefix() . 'estimates',
                'field' => 'currency',
            ],
            [
                'table' => db_prefix() . 'clients',
                'field' => 'default_currency',
            ],
            [
                'table' => db_prefix() . 'creditnotes',
                'field' => 'currency',
            ],
            [
                'table' => db_prefix() . 'subscriptions',
                'field' => 'currency',
            ],
        ];
    }

    /**
     * Predefined contact permission
     * @deprecated 1.9.8 use get_contact_permissions() instead
     * @return array
     */
    public function get_contact_permissions()
    {
        return get_contact_permissions();
    }
}
