<?php

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\zip\Unzip;

class App_module_installer
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * Upload module
     * @return array
     */
    public function from_upload()
    {
        if (isset($_FILES['module']) && _perfex_upload_error($_FILES['module']['error'])) {
            return [
                    'error'   => _perfex_upload_error($_FILES['file']['error']),
                    'success' => false,
            ];
        }

        if (isset($_FILES['module']['name'])) {
            hooks()->do_action('pre_upload_module', $_FILES['module']);

            $response = ['success' => false, 'error' => ''];

            // Get the temp file path
            $uploadedTmpZipPath = $_FILES['module']['tmp_name'];

            $unzip = new Unzip();

            $moduleTemporaryDir = get_temp_dir() . time() . '/';

            try {
                $unzip->extract($uploadedTmpZipPath, $moduleTemporaryDir);

                if ($this->check_module($moduleTemporaryDir) === false) {
                    $response['error'] = 'No valid module is found.';
                } else {
                    $unzip->extract($uploadedTmpZipPath, APP_MODULES_PATH);
                    hooks()->do_action('module_installed', $_FILES['module']);
                    $response['success'] = true;
                }

                $this->clean_up_dir($moduleTemporaryDir);
            } catch (Exception $e) {
                $response['error'] = $e->getMessage();
            }

            return $response;
        }
    }

    public function check_module($source)
    {
        // Check the folder contains at least 1 valid module.
        $modules_found = false;

        $files = get_dir_contents($source);

        if ($files) {
            foreach ($files as $file) {
                if (endsWith($file, '.php')) {
                    $info = $this->ci->app_modules->get_headers($file);
                    if (isset($info['module_name']) && !empty($info['module_name'])) {
                        $modules_found = true;

                        break;
                    }
                }
            }
        }

        if (!$modules_found) {
            return false;
        }

        return $source;
    }

    private function clean_up_dir($source)
    {
        delete_files($source);
        delete_dir($source);
    }
}
