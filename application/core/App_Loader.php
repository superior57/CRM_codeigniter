<?php

defined('BASEPATH') or exit('No direct script access allowed');

/* load the MX_Loader class */
require APPPATH . 'third_party/MX/Loader.php';

class App_Loader extends MX_Loader
{
    public function __construct()
    {
        parent::__construct();
    }

    public function _ci_load($_ci_data)
    {
        extract($_ci_data);

        // CUSTOM CODE

        if (isset($_ci_vars) && isset($_ci_view)) {
            $hook_data = hooks()->apply_filters('app_view_data', ['data' => $_ci_vars, 'path' => $_ci_view]);

            $_ci_view = $hook_data['path'];
            $_ci_vars = $hook_data['data'];
        }

        // CUSTOM CODE END

        if (isset($_ci_view)) {
            $_ci_path = '';

            /* add file extension if not provided */
            $_ci_file = (pathinfo($_ci_view, PATHINFO_EXTENSION)) ? $_ci_view : $_ci_view . EXT;

            foreach ($this->_ci_view_paths as $path => $cascade) {

                // CUSTOM CODE
                $_my_view_file = null;
                $my_view_name  = '';
                $_view_file    = $path . $_ci_file;

                $module = CI::$APP->router->fetch_module();

                if (is_null($module) || (!is_null($module) && module_supports($module, 'my_prefixed_view_files'))) {
                    $_my_view_file_temp_data = explode('/', $_view_file);

                    end($_my_view_file_temp_data);

                    $last_key = key($_my_view_file_temp_data);

                    $my_view_name = 'my_' . $_my_view_file_temp_data[$last_key];

                    unset($_my_view_file_temp_data[$last_key]);

                    foreach ($_my_view_file_temp_data as $_my_file) {
                        $_my_view_file .= DIRECTORY_SEPARATOR . $_my_file;
                    }

                    $_my_view_file = substr($_my_view_file, 1);
                }

                if (!is_null($_my_view_file)
                    && file_exists($_my_view_file . DIRECTORY_SEPARATOR . $my_view_name)) {
                    $_ci_path    = $_my_view_file . DIRECTORY_SEPARATOR . $my_view_name;
                    $file_exists = true;

                    break;
                // CUSTOM CODE END
                } elseif (file_exists($_view_file)) {
                    // THIS IS THE ORIGINAL CODE
                    $_ci_path    = $path . $_ci_file;
                    $file_exists = true;

                    break;
                }

                if (! $cascade) {
                    break;
                }
            }
        } elseif (isset($_ci_path)) {
            $_ci_file = basename($_ci_path);
            if (! file_exists($_ci_path)) {
                $_ci_path = '';
            }
        }

        if (empty($_ci_path)) {
            show_error('Unable to load the requested file: ' . $_ci_file);
        }

        if (isset($_ci_vars)) {
            $this->_ci_cached_vars = array_merge($this->_ci_cached_vars, (array) $_ci_vars);
        }

        extract($this->_ci_cached_vars);

        ob_start();

        if ((bool) @ini_get('short_open_tag') === false && CI::$APP->config->item('rewrite_short_tags') == true) {
            echo eval('?>' . preg_replace("/;*\s*\?>/", '; ?>', str_replace('<?=', '<?php echo ', file_get_contents($_ci_path))));
        } else {
            include($_ci_path);
        }

        log_message('debug', 'File loaded: ' . $_ci_path);

        if ($_ci_return == true) {
            return ob_get_clean();
        }

        if (ob_get_level() > $this->_ci_ob_level + 1) {
            ob_end_flush();
        } else {
            CI::$APP->output->append_output(ob_get_clean());
        }
    }
}
