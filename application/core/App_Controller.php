<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_Controller extends CI_Controller
{
    protected $current_db_version;

    public function __construct()
    {
        parent::__construct();

        $GLOBALS['EXT']->call_hook('pre_controller_constructor');

        /*
            if(!$this->input->is_ajax_request()){
                $this->output->enable_profiler(TRUE);
            }
        */

        /**
         * Fix for users who don't replace all files during update !!!
         */
        if (!class_exists('ForceUTF8\Encoding') && file_exists(APPPATH . 'vendor/autoload.php')) {
            require_once(APPPATH . 'vendor/autoload.php');
        }

        if (is_dir(FCPATH . 'install') && ENVIRONMENT != 'development') {
            die('<h3>Delete the install folder</h3>');
        }

        if (CI_VERSION != '3.1.10') {
            echo '<h2>Additionally you will need to replace the <b>system</b> folder. We updated Codeigniter to 3.1.10.</h2>';
            echo '<p>From the newest downloaded files upload the <b>system</b> folder to your installation directory.';
            die;
        }

        if (!extension_loaded('mbstring') && (!function_exists('mb_strtoupper') || !function_exists('mb_strtolower'))) {
            die('<h1>"mbstring" PHP extension is not loaded. Enable this extension from cPanel or consult with your hosting provider to assist you enabling "mbstring" extension.</h4>');
        }

        $this->db->reconnect();

        if (is_mobile()) {
            $this->session->set_userdata(['is_mobile' => true]);
        } else {
            $this->session->unset_userdata('is_mobile');
        }

        /**
         * Set system timezone based on selected timezone from options
         * @var string
         */
        $timezone = get_option('default_timezone');
        if ($timezone != '') {
            date_default_timezone_set($timezone);
        }


        /**
         * Clear last upgrade copy data
         * @var object
         */
        if ($lastUpdate = get_last_upgrade_copy_data()) {
            if ((time() - $lastUpdate->time) > _delete_temporary_files_older_then()) {
                @unlink($lastUpdate->path);
                update_option('last_upgrade_copy_data', '');
            }
        }


        $this->load->model('authentication_model');
        $this->authentication_model->autologin();

        if ($this instanceof ClientsController) {
            load_client_language();
        } elseif ($this instanceof AdminController) {
            load_admin_language();
        } else {
            // When App_Controller is only extended or any other CORE controller that is not instance of ClientsController or AdminController
            // Will load the default sytem language, so we can get the locale and language from $GLOBALS;
            load_admin_language();
        }

        $vars             = [];
        $vars['locale']   = $GLOBALS['locale'];
        $vars['language'] = $GLOBALS['language'];

        $this->load->vars($vars);

        $this->current_db_version = $this->app->get_current_db_version();

        hooks()->do_action('app_init');
    }
}
