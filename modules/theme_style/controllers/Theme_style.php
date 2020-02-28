<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Theme_style extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_admin()) {
            access_denied('Theme Style');
        }
    }

    public function index()
    {
        $data['title'] = _l('theme_style');
        $this->load->view('theme_style', $data);
    }

    public function reset()
    {
        update_option('theme_style', '[]');
        redirect(admin_url('theme_style'));
    }

    public function save()
    {
        hooks()->do_action('before_save_theme_style');

        update_option('theme_style', $this->input->post('data'));

        foreach(['admin_area','clients_area','clients_and_admin'] as $css_area) {
            // Also created the variables
            $$css_area = $this->input->post($css_area);
            $$css_area = trim($$css_area);
            $$css_area = nl2br($$css_area);
        }

        update_option('theme_style_custom_admin_area', $admin_area);
        update_option('theme_style_custom_clients_area', $clients_area);
        update_option('theme_style_custom_clients_and_admin_area', $clients_and_admin);
    }
}
