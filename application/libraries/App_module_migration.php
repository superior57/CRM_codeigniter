<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_module_migration
{
    /**
     * Migration numbering type
     *
     * @var	string
     */
    protected $_migration_type = 'sequential';

    /**
     * Path to module migration classes
     *
     * @var string
     */
    protected $_migration_path = null;

    /**
     * Current module migration version
     *
     * @var mixed
     */
    protected $_migration_version = 0;

    /**
     * Database table with migration info
     *
     * @var string
     */
    protected $_migration_table;

    /**
     * Migration basename regex
     *
     * @var string
     */
    protected $_migration_regex;

    /**
     * Error message
     *
     * @var string
     */
    protected $_error_string = '';

    /**
     * Codeigniter Instance
     */
    protected $ci;

    /**
     * Module name
     */
    protected $module_name = '';

    /**
     * Initialize Migration Class
     *
     * @param	string module name
     * @return	void
     */
    public function __construct($module = null)
    {
        $this->ci = &get_instance();

        $this->_migration_table = db_prefix().'modules';
        // Loaded via the autoload feature, not module name in this case is passed, ignore
        if ($module === null) {
            return;
        }

        // Set module name
        $this->module_name = $module;

        $this->_migration_path = APP_MODULES_PATH . $this->module_name . '/' . 'migrations/';

        // Set the current module version
        $this->set_current_module_version();

        // Add trailing slash if not set
        $this->_migration_path = rtrim($this->_migration_path, '/') . '/';

        // They'll probably be using dbforge
        $this->ci->load->dbforge();

        // Migration basename regex
        $this->_migration_regex = '/^\d{3}_(\w+)$/';

        // Load the CodeIgniter migration language
        $this->ci->lang->load('migration');
    }

    // --------------------------------------------------------------------

    /**
     * Migrate to a schema version
     *
     * Calls each migration step required to get to the schema version of
     * choice
     *
     * @param	string	$target_version	Target schema version
     * @return	mixed	TRUE if no migrations are found, current version string on success, FALSE on failure
     */
    public function version($target_version)
    {
        // Note: We use strings, so that timestamp versions work on 32-bit systems
        $current_version = $this->_get_version();
        $target_version  = sprintf('%03d', $target_version);

        $migrations = $this->find_migrations();

        if ($target_version > 0 && ! isset($migrations[$target_version])) {
            $this->_error_string = sprintf($this->ci->lang->line('migration_not_found'), $target_version);

            return false;
        }

        if ($target_version > $current_version) {
            $method = 'up';
        } elseif ($target_version < $current_version) {
            $method = 'down';
            // We need this so that migrations are applied in reverse order
            krsort($migrations);
        } else {
            // Well, there's nothing to migrate then ...
            return true;
        }

        // Validate all available migrations within our target range.
        //
        // Unfortunately, we'll have to use another loop to run them
        // in order to avoid leaving the procedure in a broken state.
        //
        // See https://github.com/bcit-ci/CodeIgniter/issues/4539
        $pending = [];
        foreach ($migrations as $number => $file) {
            // Ignore versions out of our range.
            //
            // Because we've previously sorted the $migrations array depending on the direction,
            // we can safely break the loop once we reach $target_version ...
            if ($method === 'up') {
                if ($number <= $current_version) {
                    continue;
                } elseif ($number > $target_version) {
                    break;
                }
            } else {
                if ($number > $current_version) {
                    continue;
                } elseif ($number <= $target_version) {
                    break;
                }
            }

            // Check for sequence gaps
            if (isset($previous) && abs($number - $previous) > 1) {
                $this->_error_string = sprintf($this->ci->lang->line('migration_sequence_gap'), $number);

                return false;
            }

            $previous = $number;

            include_once($file);
            $class = 'Migration_' . ucfirst(strtolower($this->_get_migration_name(basename($file, '.php'))));

            // Validate the migration file structure
            if (! class_exists($class, false)) {
                $this->_error_string = sprintf($this->ci->lang->line('migration_class_doesnt_exist'), $class);

                return false;
            } elseif (! is_callable([$class, $method])) {
                $this->_error_string = sprintf($this->ci->lang->line('migration_missing_' . $method . '_method'), $class);

                return false;
            }

            $pending[$number] = [$class, $method];
        }

        // Now just run the necessary migrations
        foreach ($pending as $number => $migration) {

            $migration[0] = new $migration[0];
            call_user_func($migration);
            $current_version = $number;
            $this->_update_version($current_version);
        }

        // This is necessary when moving down, since the the last migration applied
        // will be the down() method for the next migration up from the target
        if ($current_version <> $target_version) {
            $current_version = $target_version;
            $this->_update_version($current_version);
        }

        return $current_version;
    }

    // --------------------------------------------------------------------

    /**
     * Sets the schema to the latest migration
     *
     * @return	mixed	Current version string on success, FALSE on failure
     */
    public function latest()
    {
        $migrations = $this->find_migrations();

        if (empty($migrations)) {
            $this->_error_string = $this->ci->lang->line('migration_none_found');

            return false;
        }

        $last_migration = basename(end($migrations));

        // Calculate the last migration step from existing migration
        // filenames and proceed to the standard version migration
        return $this->version($this->_get_migration_number($last_migration));
    }

    // --------------------------------------------------------------------

    /**
     * Sets the schema to the migration version set in config
     *
     * @return	mixed	TRUE if no migrations are found, current version string on success, FALSE on failure
     */
    public function to_latest()
    {
        return $this->version($this->_migration_version);
    }

    // --------------------------------------------------------------------

    /**
     * Error string
     *
     * @return	string	Error message returned as a string
     */
    public function error_string()
    {
        return $this->_error_string;
    }

    // --------------------------------------------------------------------

    /**
     * Retrieves list of available migration scripts
     *
     * @return	array	list of migration file paths sorted by version
     */
    public function find_migrations()
    {
        $migrations = [];

        // Load all *_*.php files in the migrations path
        foreach (glob($this->_migration_path . '*_*.php') as $file) {
            $name = basename($file, '.php');

            // Filter out non-migration files
            if (preg_match($this->_migration_regex, $name)) {
                $number = $this->_get_migration_number($name);

                // There cannot be duplicate migration numbers
                if (isset($migrations[$number])) {
                    $this->_error_string = sprintf($this->ci->lang->line('migration_multiple_version'), $number);
                    show_error($this->_error_string);
                }

                $migrations[$number] = $file;
            }
        }

        ksort($migrations);

        return $migrations;
    }

    // --------------------------------------------------------------------

    /**
     * Extracts the migration number from a filename
     *
     * @param	string	$migration
     * @return	string	Numeric portion of a migration filename
     */
    protected function _get_migration_number($migration)
    {
        return sscanf($migration, '%[0-9]+', $number)
            ? $number : '0';
    }

    // --------------------------------------------------------------------

    /**
     * Extracts the migration class name from a filename
     *
     * @param	string	$migration
     * @return	string	text portion of a migration filename
     */
    protected function _get_migration_name($migration)
    {
        $parts = explode('_', $migration);
        array_shift($parts);

        return implode('_', $parts);
    }

    // --------------------------------------------------------------------

    /**
     * Retrieves current schema version
     *
     * @return	string	Current migration version
     */
    protected function _get_version()
    {
        $row = $this->ci->db->select('installed_version')->where('module_name', $this->module_name)->get($this->_migration_table)->row();

        return $row ? str_replace('.', '', $row->installed_version) : '0';
    }

    /**
     * Set the current module version
     */
    protected function set_current_module_version()
    {
        $module = $this->ci->app_modules->get($this->module_name);

        $this->_migration_version = str_replace('.', '', $module['headers']['version']);
    }

    // --------------------------------------------------------------------

    /**
     * Stores the current schema version
     *
     * @param	string	$migration	Migration reached
     * @return	void
     */
    protected function _update_version($migration)
    {
        $this->ci->db->where('module_name', $this->module_name);
        $this->ci->db->update($this->_migration_table, [
            'installed_version' => wordwrap($migration, 1, '.', true),
        ]);
    }
}
