<?php

defined('BASEPATH') or exit('No direct script access allowed');

define('CLIENTS_AREA', true);

class ClientsController extends App_Controller
{
    public $template = [];

    public $data = [];

    public $use_footer = true;

    public $use_submenu = true;

    public $use_navigation = true;

    public function __construct()
    {
        parent::__construct();

        /**
         * If database upgrade is required, redirect the user to the admin uri because there the upgrade is performed
         * This code can prevent confusions when there are errors thrown on the client side because the database is not updated yet.
         */
        if (is_staff_logged_in()
            && $this->app->is_db_upgrade_required($this->current_db_version)) {
            redirect(admin_url());
        }

        $this->load->library('app_clients_area_constructor');

        if (method_exists($this, 'validateContact')) {
            $this->validateContact();
        }
    }

    public function layout($notInThemeViewFiles = false)
    {
        /**
         * Navigation and submenu
         * @deprecated 2.3.2
         * @var boolean
         */

        $this->data['use_navigation'] = $this->use_navigation == true;
        $this->data['use_submenu']    = $this->use_submenu == true;

        /**
         * @since  2.3.2 new variables
         * @var array
         */
        $this->data['navigationEnabled'] = $this->use_navigation == true;
        $this->data['subMenuEnabled']    = $this->use_submenu == true;

        /**
         * Theme head file
         * @var string
         */
        $this->template['head'] = $this->load->view('themes/' . active_clients_theme() . '/head', $this->data, true);

        $GLOBALS['customers_head'] = $this->template['head'];

        /**
         * Load the template view
         * @var string
         */
        $module                       = CI::$APP->router->fetch_module();
        $this->data['current_module'] = $module;

        $viewPath = !is_null($module) || $notInThemeViewFiles ? $this->view : 'themes/' . active_clients_theme() . '/views/' . $this->view;

        $this->template['view']    = $this->load->view($viewPath, $this->data, true);
        $GLOBALS['customers_view'] = $this->template['view'];

        /**
         * Theme footer
         * @var string
         */
        $this->template['footer'] = $this->use_footer == true
        ? $this->load->view('themes/' . active_clients_theme() . '/footer', $this->data, true)
        : '';
        $GLOBALS['customers_footer'] = $this->template['footer'];

        /**
         * @deprecated 2.3.0
         * Theme scripts.php file is no longer used since vresion 2.3.0, add app_customers_footer() in themes/[theme]/index.php
         * @var string
         */
        $this->template['scripts'] = '';
        if (file_exists(VIEWPATH . 'themes/' . active_clients_theme() . '/scripts.php')) {
            if (ENVIRONMENT != 'production') {
                trigger_error(sprintf('%1$s', 'Clients area theme file scripts.php file is no longer used since version 2.3.0, add app_customers_footer() in themes/[theme]/index.php. You can check the original theme index.php for example.'));
            }

            $this->template['scripts'] = $this->load->view('themes/' . active_clients_theme() . '/scripts', $this->data, true);
        }

        /**
         * Load the theme compiled template
         */
        $this->load->view('themes/' . active_clients_theme() . '/index', $this->template);
    }

    /**
     * Sets view data
     * @param  array $data
     * @return core/ClientsController
     */
    public function data($data)
    {
        if (!is_array($data)) {
            return false;
        }

        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Set view to load
     * @param  string $view view file
     * @return core/ClientsController
     */
    public function view($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Sets view title
     * @param  string $title
     * @return core/ClientsController
     */
    public function title($title)
    {
        $this->data['title'] = $title;

        return $this;
    }

    /**
     * Disables theme navigation
     * @return core/ClientsController
     */
    public function disableNavigation()
    {
        $this->use_navigation = false;

        return $this;
    }

    /**
     * Disables theme navigation
     * @return core/ClientsController
     */
    public function disableSubMenu()
    {
        $this->use_submenu = false;

        return $this;
    }

    /**
    * Disables theme footer
    * @return core/ClientsController
    */
    public function disableFooter()
    {
        $this->use_footer = false;

        return $this;
    }
}
