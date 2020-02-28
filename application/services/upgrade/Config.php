<?php

namespace app\services\upgrade;

defined('BASEPATH') or exit('No direct script access allowed');

class Config
{
    public $purchase_key;

    public $latest_version;

    public $current_version;

    public $url;

    public $tmp_dir;

    public $tmp_update_dir;

    public $zipFile;

    public $extract_to;

    public function __construct($purchase_key, $latest_version, $current_version, $url, $tmp_dir, $extract_to)
    {
        $this->purchase_key    = $purchase_key;
        $this->latest_version  = $latest_version;
        $this->current_version = $current_version;
        $this->url             = $url;
        $this->tmp_dir         = rtrim($tmp_dir, '/') . '/';
        $this->tmp_update_dir  = $this->tmp_dir . 'v' . $this->latest_version . '/';
        $this->zipFile         = $this->tmp_update_dir . $this->latest_version . '.zip';
        $this->extract_to      = $extract_to;

        if (!is_writable($this->tmp_dir)) {
            throw new \Exception('Temporary directory not writable - <b>' . $this->tmp_dir . '</b><br />Please contact your hosting provider make this directory writable. The directory needs to be writable for the update files.');
        }
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->{$property};
        }
    }
}
