<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_pusher
{
    private $pusher;

    private $app_key = '';

    private $app_secret = '';

    private $app_id = '';

    private $pusher_options = [];

    public function __construct()
    {
        $this->app_key    = get_option('pusher_app_key');
        $this->app_secret = get_option('pusher_app_secret');
        $this->app_id     = get_option('pusher_app_id');

        $this->initialize();
    }

    private function initialize()
    {
        if ($this->app_key !== '' && $this->app_secret !== '' && $this->app_secret !== '') {
            $pusher_options = hooks()->apply_filters('pusher_options', []);

            if (!isset($pusher_options['cluster']) && get_option('pusher_cluster') != '') {
                $pusher_options['cluster'] = get_option('pusher_cluster');
            }

            $this->pusher_options = $pusher_options;

            $this->pusher = new Pusher\Pusher(
                $this->app_key,
                $this->app_secret,
                $this->app_id,
                $this->pusher_options
            );
        }
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->pusher, $name)) {
            return $this->pusher->{$name}(...$arguments);
        }

        // In case Pusher keys are not set
        if ($this->pusher) {
            throw new \BadMethodCallException('Instance method Pusher->$name() doesn\'t exist');
        }
    }
}
