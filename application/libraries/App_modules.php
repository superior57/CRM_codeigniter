<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_modules
{
    /**
     * @since  2.3.4
     * There is function add_module_support($module_name, $feature) so modules can hook support
     * Check the function add_module_support for more info
     * @var array
     */
    private static $supports = [];

    private $ci;

    /**
     * The modules info that is stored in database
     * @var array
     */
    private $db_modules = [];

    /**
     * All valid modules
     * @var array
     */
    private $modules = [];

    /**
     * All activated modules
     * @var array
     */
    private $active_modules = [];

    /**
     * Module new version data
     * @var array
     */
    private $new_version_data = [];

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('directory');

        /**
         * The modules feature is added in version 2.3.0 if the current database version is smaller don't try to load the modules
         * This code exists because after update, the database is not yet updated and the table modules does not exists and will throw errors.
         */
        if ($this->ci->app->get_current_db_version() < 230) {
            return;
        }

        $this->initialize();
    }

    /**
     * Activate Module
     * @param  string $name Module Name [system_name]
     * @return boolean
     */
    public function activate($name)
    {
        $module = $this->get($name);

        if (!$module) {
            return false;
        }

        /**
         * Check if module is already added to database
         */

        if (!$this->module_exists_in_database($name)) {
            $this->ci->db->where('module_name', $name);
            $this->ci->db->insert(db_prefix() . 'modules', ['module_name' => $name, 'installed_version' => $module['headers']['version']]);
        }

        include_once($module['init_file']);

        /**
        * Maybe used from another modules?
        */
        hooks()->do_action('pre_activate_module', $module);

        /**
         * Module developers can add hooks for their own activate actions that needs to be taken
         */
        hooks()->do_action("activate_{$name}_module");

        /**
         * Activate the module in database
         */
        $this->ci->db->where('module_name', $name);
        $this->ci->db->update(db_prefix() . 'modules', ['active' => 1]);

        /**
         * After module is activated action
         */
        hooks()->do_action('module_activated', $module);

        return true;
    }

    /**
     * Deactivate Module
     * @param  string $name Module Name [system_name]
     * @return boolean
     */
    public function deactivate($name)
    {
        $module = $this->get($name);

        if (!$module) {
            return false;
        }

        /**
         * Maybe used from another modules?
         */
        hooks()->do_action('pre_deactivate_module', $module);

        /**
         * Module developers can add hooks for their own activate actions that needs to be taken
         */
        hooks()->do_action("deactivate_{$name}_module");

        /**
         * Deactivate the module in database
         */
        $this->ci->db->where('module_name', $name);
        $this->ci->db->update(db_prefix() . 'modules', ['active' => 0]);


        /**
         * After module is activated action
         */
        hooks()->do_action('module_deactivated', $module);

        return true;
    }

    /**
     * Uninstall Module
     * @param  string $name Module Name [system_name]
     * @return boolean
     */
    public function uninstall($name)
    {
        $module = $this->get($name);

        if (!$module) {
            return false;
        }

        /**
         * Module needs to be deactivated first in order to be uninstalled
         */
        if ($module['activated'] == 1 || in_array($name, uninstallable_modules())) {
            return false;
        }

        /**
         * Maybe used from another modules?
         */
        hooks()->do_action('pre_uninstall_module', $module);

        /**
         * Remove the module from database
         */
        $this->ci->db->where('module_name', $name);
        $this->ci->db->delete(db_prefix() . 'modules');

        /**
         * Module developers can add hooks for their own uninstall actions that needs to be taken
         */
        $uninstallPath = $module['path'] . 'uninstall.php';
        if (file_exists($uninstallPath)) {
            include_once($uninstallPath);
        } else {
            hooks()->do_action("uninstall_{$name}_module");
        }

        /**
         * Delete module files
         */
        if (is_dir($module['path'])) {
            delete_files($module['path'], true);
            rmdir($module['path']);
        }

        /**
         * After module is uninstalled action
         */
        hooks()->do_action('module_uninstalled', $module);

        return true;
    }

    /**
     * Get all activated modules
     * @return array
     */
    public function get_activated()
    {
        return $this->active_modules;
    }

    /**
     * Check whether a module is active
     * @param  string  $name module name
     * @return boolean
     */
    public function is_active($name)
    {
        return array_key_exists($name, $this->get_activated());
    }

    /**
     * Check whether a module is inactive
     * @param  string  $name module name
     * @return boolean
     */
    public function is_inactive($name)
    {
        return ! $this->is_active($name);
    }

    /**
     * Check whether a module is installed for a first time
     * @param  string  $name module name
     * @return boolean
     */
    public function is_installed($name)
    {
        if (!isset($this->modules[$name])) {
            return false;
        }

        return $this->modules[$name]['installed_version'] !== false;
    }

    /**
     * Check if the module minimum requirement version is met
     * @param  [type]  $name [description]
     * @return boolean       [description]
     */
    public function is_minimum_version_requirement_met($name)
    {
        $module = $this->get($name);

        if (!isset($module['headers']['requires_at_least'])) {
            return true;
        }

        $this->ci->config->load('migration');
        $appVersion               = wordwrap($this->ci->config->item('migration_version'), 1, '.', true);
        $moduleRequiresAppVersion = $module['headers']['requires_at_least'];

        if (version_compare($appVersion, $moduleRequiresAppVersion, '>=')) {
            return true;
        }

        return false;
    }

    /**
     * Upgrade module to latest database version
     * @param  string $name module name
     * @return mixed
     */
    public function upgrade_database($name)
    {
        $migration = new App_module_migration($name);

        if ($migration->to_latest() === false) {
            return $migration->error_string();
        }

        return true;
    }

    /**
     * Check whether database upgrade is required to module
     * When module Version header is different then the one stored in database
     * @param  string  $name module name
     * @return boolean
     */
    public function is_database_upgrade_required($name)
    {
        $module = $this->get($name);

        $moduleInstalledVersion = $module['installed_version'];

        if ($moduleInstalledVersion == false) {
            // Not yet activated for the first time
            return false;
        }

        $moduleFilesVersion = $module['headers']['version'];

        /**
        * Check if downgrade is required
        * By default, version_compare() returns -1 if the first version is lower than the second,
        * 0 if they are equal, and 1 if the second is lower.
        */
        if (version_compare($moduleInstalledVersion, $moduleFilesVersion) === 1) {
            return true;
        }

        if (version_compare($moduleFilesVersion, $moduleInstalledVersion) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Modules can create release_handler.php file inside the module root directory and apply their own logic to check whether there is new version available.
     * release_handler.php file should return false if there is no version available or array with e.q. the following params:
     * $data['version'] = VERSION_NUMBER;
     * (Optional) $data['changelog'] = 'https://official-website.com/plugin/changelog';
     * (Optional) $data['update_handler'] = '';
     * @param  string  $name module system name
     * @return mixed
     */
    public function new_version_available($name)
    {
        $retVal = $this->get_new_version_data($name);

        if ($retVal !== false && !is_array($retVal)) {
            return false;
        }

        return $retVal;
    }

    public function get_new_version_data($name)
    {
        if (isset($this->new_version_data[$name])) {
            return $this->new_version_data[$name];
        }

        $file = module_dir_path($name, 'release_handler.php');

        if (!file_exists($file)) {
            return false;
        }

        $retVal                        = include_once($file);
        $this->new_version_data[$name] = $retVal;

        if ($this->is_update_handler_available($name)) {
            hooks()->add_action('module_' . $name . '_update_handler', $retVal['update_handler']);
        }

        return $retVal;
    }

    public function update_to_new_version($name)
    {
        $data = $this->get_new_version_data($name);
        hooks()->do_action('module_' . $name . '_update_handler', $data['update_handler']);
    }

    public function is_update_handler_available($name)
    {
        $retVal = $this->get_new_version_data($name);
        if (isset($retVal['update_handler']) && $retVal['update_handler']) {
            return true;
        }

        return false;
    }

    /**
     * Return the number of modules that requires database upgrade
     * @return integer
     */
    public function number_of_modules_that_require_database_upgrade()
    {
        $CI       = &get_instance();
        $cacheKey = 'no-of-modules-require-database-upgrade';
        $total    = $CI->app_object_cache->get($cacheKey);
        if ($total === false) {
            $total = 0;
            foreach ($this->modules as $module) {
                if ($this->is_database_upgrade_required($module['system_name'])) {
                    $total++;
                }
            }
            $CI->app_object_cache->add($cacheKey, $total);
        }

        return $total;
    }

    /**
     * Get all modules or specific module if module system name is passed
     * This method returns all modules including active and inactive
     * @param  mixed $module
     * @return mixed
     */
    public function get($module = null)
    {
        if (!$module) {
            $modules = $this->modules;

            /* Sort modules by name */

            usort($modules, function ($a, $b) {
                return strcmp(strtolower($a['headers']['module_name']), strtolower($b['headers']['module_name']));
            });

            return $modules;
        }

        if (isset($this->modules[$module])) {
            return $this->modules[$module];
        }

        return null;
    }

    /**
     * Get module from database
     * @param  string $name module system name
     * @return mixed
     */
    public function get_database_module($name)
    {
        if (isset($this->db_modules[$name])) {
            return $this->db_modules[$name];
        }

        $this->ci->db->where('module_name', $name);

        return $this->ci->db->get(db_prefix() . 'modules')->row();
    }

    /**
     * Initialize all modules
     * @return null
     */
    public function initialize()
    {
        // For caching
        $this->query_db_modules();

        foreach ($this->get_valid_modules() as $module) {
            $name = $module['name'];
            // If the module hasn't already been added and isn't a file
            if (!isset($this->modules[$name])) {
                /**
                 * System name
                 */
                $this->modules[$name]['system_name'] = $name;

                /**
                 * Module headers
                 */
                $this->modules[$name]['headers'] = $this->get_headers($module['init_file']);
                /**
                 * Init file path
                 * The file name must be the same like the module folder name
                 */
                $this->modules[$name]['init_file'] = $module['init_file'];
                /**
                 * Module path
                 */
                $this->modules[$name]['path'] = $module['path'];

                // Check if module is activated
                $moduleDB = $this->get_database_module($name);

                if ($moduleDB && $moduleDB->active == 1) {
                    $this->modules[$name]['activated'] = 1;
                    // Add to active modules handler
                    $this->active_modules[$name] = $this->modules[$name];
                } else {
                    $this->modules[$name]['activated'] = 0;
                }
                /**
                 * Installed version
                 */
                $this->modules[$name]['installed_version'] = $moduleDB ? $moduleDB->installed_version : false;
            }
        }
    }

    /**
     * @since 2.3.4
     * @see add_module_support function.
     *
     * @param string $module_name  module name
     * @param string $feature     support feature
     *
     */
    public function add_supports_feature($module_name, $feature)
    {
        if (!isset(self::$supports[$module_name])) {
            self::$supports[$module_name] = [];
        }

        if (in_array($feature, self::$supports[$module_name])) {
            return;
        }

        self::$supports[$module_name][] = $feature;
    }

    /**
     * @since 2.3.4
     * @see add_module_support function.
     *
     * @param string $module_name  module name
     * @param string $feature     support feature
     * @return  boolean
     */
    public function supports_feature($module_name, $feature)
    {
        return isset(self::$supports[$module_name]) && in_array($feature, self::$supports[$module_name]);
    }

    /**
     * Get module headers info
     * @param  string $module_source the module init file location
     * @return array
     */
    public function get_headers($module_source)
    {
        $module_data = read_file($module_source); // Read the module init file.

        preg_match('|Module Name:(.*)$|mi', $module_data, $name);
        preg_match('|Module URI:(.*)$|mi', $module_data, $uri);
        preg_match('|Version:(.*)|i', $module_data, $version);
        preg_match('|Description:(.*)$|mi', $module_data, $description);
        preg_match('|Author:(.*)$|mi', $module_data, $author_name);
        preg_match('|Author URI:(.*)$|mi', $module_data, $author_uri);
        preg_match('|Requires at least:(.*)$|mi', $module_data, $requires_at_least);

        $arr = [];

        if (isset($name[1])) {
            $arr['module_name'] = trim($name[1]);
        }

        if (isset($uri[1])) {
            $arr['uri'] = trim($uri[1]);
        }

        if (isset($version[1])) {
            $arr['version'] = trim($version[1]);
        } else {
            $arr['version'] = 0;
        }

        if (isset($description[1])) {
            $arr['description'] = trim($description[1]);
        }

        if (isset($author_name[1])) {
            $arr['author'] = trim($author_name[1]);
        }

        if (isset($author_uri[1])) {
            $arr['author_uri'] = trim($author_uri[1]);
        }

        if (isset($requires_at_least[1])) {
            $arr['requires_at_least'] = trim($requires_at_least[1]);
        }

        return $arr;
    }

    /**
     * Check whether module is inserted into database table
     * @param  string $name module system name
     * @return boolean
     */
    private function module_exists_in_database($name)
    {
        return (bool) $this->get_database_module($name);
    }

    /**
     * Get valid modules
     * @return array
     */
    private function get_valid_modules()
    {
        /**
        * Modules path
        * APP_MODULES_PATH constant is defined in application/config/constants.php
        * @var array
        */
        $modules       = directory_map(APP_MODULES_PATH, 1);
        $valid_modules = [];

        if ($modules) {
            foreach ($modules as $name) {
                $name = strtolower(trim($name));

                /**
                 * Filename may be returned like chat/ or chat\ from the directory_map function
                 */
                foreach (['\\', '/'] as $trim) {
                    $name = rtrim($name, $trim);
                }

                // If the module hasn't already been added and isn't a file
                if (!stripos($name, '.')) {
                    $module_path = APP_MODULES_PATH . $name . '/';
                    $init_file   = $module_path . $name . '.php';

                    // Make sure a valid module file by the same name as the folder exists
                    if (file_exists($init_file)) {
                        $valid_modules[] = [
                            'init_file' => $init_file,
                            'name'      => $name,
                            'path'      => $module_path,
                        ];
                    }
                }
            }
        }

        return $valid_modules;
    }

    private function query_db_modules()
    {
        $db_modules = $this->ci->db->get(db_prefix() . 'modules')->result();

        foreach ($db_modules as $db_module) {
            $this->db_modules[$db_module->module_name] = $db_module;
        }
    }
}
