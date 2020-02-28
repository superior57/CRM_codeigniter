<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(__DIR__ . '/App_assets.php');

class App_scripts extends App_assets
{
    public function add($name, $data, $group = 'admin', $deps = [])
    {
        if (isset($this->registered[$group][$name])) {
            return false;
        }

        $this->initializeEmptyGroup($group);

        if (is_string($data)) {
            $data = ['path' => $data];
        }

        if (!isset($data['deps'])) {
            $data['deps'] = $deps;
        }

        $this->registered[$group][$name] = $data;

        return true;
    }

    public function get($group = 'admin')
    {
        return $group === null ? $this->registered[$group] : $this->registered;
    }

    public function compile($group = 'admin')
    {
        $html = '';

        $defaults = [
            'type' => 'text/javascript',
        ];

        hooks()->do_action('before_compile_scripts_assets', $group);

        $items = $this->do_items(array_keys($this->registered[$group]), $group);

        foreach ($items as $id => $data) {
            $attributes = $defaults;

            /**
             * Set id key for the attributes
             */
            $attributes['id'] = $id;

            /**
             * Check if versioning is set
             * @var boolean
             */
            $version = isset($data['version']) ? $data['version'] : true;

            /**
            * Compile the URL
            */
            $attributes['src'] = $this->compileUrl($data['path'], $version);

            /**
            * Finally build the <script> for JS file
            */

            $html .= '<script' . $this->attributesToString($id, $attributes, $data) . '></script>' . PHP_EOL;
        }

        return $html;
    }

    /**
     * @deprecated 2.3.0
     */
    public function coreScript($path, $fileName)
    {
        if (get_option('use_minified_files') == 1) {
            $fileName = $this->getMinifiedFileName($fileName, $path);
        }

        $ver = ENVIRONMENT == 'development' ? time() : get_app_version();

        return '<script src="' . base_url($path . '/' . $fileName . '?v=' . $ver) . '"></script>' . PHP_EOL;
    }
}
