<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_assets
{
    /**
     * All registered assets
     * @var array
     */
    protected $registered = [];

    /**
    *
    * @since 2.3.0
    * @var  array
    */
    protected $to_do = [];

    /**
    *
    * @since 2.3.0
    * @var array
    */
    public $done = [];

    /**
    * An array of handle handleGroups to enqueue.
    *
    * @since 2.3.0
    * @var array
    */
    protected $handleGroups = [];

    /**
     * Default group for assets in the customers area theme
     * @var string
     */
    protected $themeGroup = 'customers-area-default';

    public function theme($name, $data, $deps = [])
    {
        return $this->add($name, $data, $this->themeGroup, $deps);
    }

    public function default_theme_group()
    {
        return $this->themeGroup;
    }

    public function remove($name, $group = 'admin')
    {
        if (isset($this->registered[$group][$name])) {
            unset($this->registered[$group][$name]);

            return true;
        }

        return false;
    }

    /**
     * Used for core js/css version
     * @return mixed
     */
    public function core_version()
    {
        return ENVIRONMENT == 'development' ? time() : get_app_version();
    }

    public function core_file($path, $fileName)
    {
        if (get_option('use_minified_files') == 1) {
            $fileName = $this->getMinifiedFileName($fileName, $path);
        }

        return rtrim($path, '/') . '/' . $fileName;
    }

    public function getMinifiedFileName($nonMinifiedFileName, $path)
    {
        $fileNameArray = explode('.', $nonMinifiedFileName);
        $last          = count($fileNameArray) - 1;
        $extension     = $fileNameArray[$last];
        unset($fileNameArray[$last]);

        $filename = '';
        foreach ($fileNameArray as $t) {
            $filename .= $t . '.';
        }

        $filename .= 'min.' . $extension;

        if (file_exists($path . '/' . $filename)) {
            $nonMinifiedFileName = $filename;
        }

        return $nonMinifiedFileName;
    }

    protected function initializeEmptyGroup($group)
    {
        $exists = array_key_exists($group, $this->registered);

        if (!$exists || ($exists && !is_array($this->registered[$group]))) {
            $this->registered[$group] = [];
        }
    }

    protected function compileUrl($path, $version = true)
    {
        $url = $path;

        if (!$this->strStartsWith($path, 'http') && !$this->strStartsWith($path, '//')) {
            $url = base_url($path);

            if ($version) {
                // parse_url returns a string if the URL has parameters or NULL if not
                $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'v=' . get_app_version();
            }
        }

        return $url;
    }

    protected function attributesToString($id, $defaults, $asset)
    {
        if (isset($asset['attributes'])) {
            $defaults = array_merge($defaults, $asset['attributes']);
        }

        return $this->removeEmptyStringAttributes(_attributes_to_string(
            $this->removeEmptyAttributes($defaults)
        ), $id);
    }

    protected function removeEmptyAttributes($attributes)
    {
        foreach ($attributes as $key => $val) {
            if (empty($val)) {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }

    protected function removeEmptyStringAttributes($parsedAttributes, $id)
    {
        // E.q. // 0="defer" becomes defer
        $re = '/(\d\=\")([a-zA-Z0-9-_]+)\"/m';

        return preg_replace($re, '$2', $parsedAttributes);
    }

    protected function strStartsWith($haystack, $needle)
    {
        return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    /**
    * @since 2.3.0
    */
    protected function do_items($handles, $assetsGroup = false, $group = false)
    {
        $this->all_deps($handles, $assetsGroup);

        foreach ($this->to_do as $key => $handle) {
            if (!in_array($handle, $this->done, true) && isset($this->registered[$assetsGroup][$handle])) {

                /*
                 * Attempt to process the item. If successful,
                 * add the handle to the done array.
                 *
                 * Unset the item from the to_do array.
                */
                if (isset($this->registered[$assetsGroup][$handle])) {
                    $this->done[$handle] = $this->registered[$assetsGroup][$handle];
                }

                unset($this->to_do[$key]);
            }
        }

        return $this->done;
    }

    /**
     * @since 2.3.0
     */
    protected function all_deps($handles, $assetsGroup = false, $recursion = false, $group = false)
    {
        if (!$handles = (array) $handles) {
            return false;
        }

        foreach ($handles as $handle) {
            $queued = in_array($handle, $this->to_do, true);

            if (in_array($handle, $this->done, true)) { // Already done
                continue;
            }

            $moved     = $this->set_group($handle, $recursion, $group);
            $new_group = $this->handleGroups[ $handle ];

            if ($queued && !$moved) { // already queued and in the right group
                continue;
            }

            $keep_going = true;

            if (!isset($this->registered[$assetsGroup][$handle])) {
                $keep_going = false;
            } // Item doesn't exist.

            elseif ($this->registered[$assetsGroup][$handle]['deps'] && array_diff($this->registered[$assetsGroup][$handle]['deps'], array_keys($this->registered[$assetsGroup]))) {
                $keep_going = false;
            } // Item requires dependencies that don't exist.
            elseif ($this->registered[$assetsGroup][$handle]['deps'] && !$this->all_deps($this->registered[$assetsGroup][$handle]['deps'], $assetsGroup, true, $new_group)) {
                $keep_going = false;
            } // Item requires dependencies that don't exist.

            if (! $keep_going) { // Either item or its dependencies don't exist.
                if ($recursion) {
                    return false;
                } // Abort this branch.

                continue; // We're at the top level. Move on to the next one.
            }

            if ($queued) { // Already grabbed it and its dependencies.
                continue;
            }

            $this->to_do[] = $handle;
        }

        return true;
    }

    /**
     * @since 2.3.0
     */
    protected function set_group($handle, $recursion, $group)
    {
        $group = (int) $group;

        if (isset($this->handleGroups[ $handle ]) && $this->handleGroups[ $handle ] <= $group) {
            return false;
        }

        $this->handleGroups[ $handle ] = $group;

        return true;
    }
}
