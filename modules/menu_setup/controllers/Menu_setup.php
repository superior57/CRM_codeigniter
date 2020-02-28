<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Menu_setup extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_admin()) {
            access_denied('Menu Setup');
        }
    }

    public function main_menu()
    {
        hooks()->remove_filter('sidebar_menu_items', 'app_admin_sidebar_custom_options', 999);

        $data['menu_items'] = $this->app_menu->get_sidebar_menu_items();

        $data['menu_options'] = json_decode(get_option('aside_menu_active'));

        $data['title'] = _l('main_menu');
        $this->load->view('main_menu', $data);
    }

    public function update_aside_menu()
    {
        hooks()->do_action('before_update_aside_menu');
        update_option('aside_menu_active', json_encode($this->prepare_menu_options('sidebar')));
    }

    public function reset_aside_menu()
    {
        update_option('aside_menu_active', json_encode([]));
        hooks()->do_action('aside_menu_resetted');
        redirect(admin_url('menu_setup/main_menu'));
    }

    public function setup_menu()
    {
        hooks()->remove_filter('setup_menu_items', 'app_admin_setup_menu_custom_options', 999);

        $data['menu_items'] = $this->app_menu->get_setup_menu_items();

        $data['menu_options'] = json_decode(get_option('setup_menu_active'));

        $data['no_disable'] = hooks()->apply_filters('setup_menu_no_disable_items', [
            'setup-menu-options',
            'main-menu-options',
            'modules',
            'settings',
        ]);

        $data['title'] = _l('setup_menu');
        $this->load->view('setup_menu', $data);
    }

    public function update_setup_menu()
    {
        hooks()->do_action('before_update_setup_menu');

        update_option('setup_menu_active', json_encode($this->prepare_menu_options('setup')));
    }

    public function reset_setup_menu()
    {
        update_option('setup_menu_active', json_encode([]));
        hooks()->do_action('setup_menu_resetted');

        redirect(admin_url('menu_setup/setup_menu'));
    }

    private function prepare_menu_options($group)
    {
        $new     = [];
        $options = $this->input->post('options');

        foreach ($options as $key => $val) {
            if ($val['id'] == 'dashboard' && $val['position'] == 5) {
                $val['position'] = 1;
            }

            if (isset($val['children'])) {
                $newChild = [];

                foreach ($val['children'] as $keyChild => $child) {
                    $initialIcon = $this->app_menu->get_initial_icon($child['id'], $group);
                    if ($child['icon'] === $initialIcon) {
                        $child['icon'] = '';
                    } elseif ($initialIcon != '' && $child['icon'] == '') {
                        $child['icon'] = false;
                    }
                    $newChild[$child['id']] = $child;
                }

                $val['children'] = $newChild;
            }

            $initialIcon = $this->app_menu->get_initial_icon($val['id'], $group);
            if ($val['icon'] === $initialIcon) {
                $val['icon'] = '';
            } elseif ($initialIcon != '' && $val['icon'] == '') {
                $val['icon'] = false;
            }

            $new[$val['id']] = $val;
        }

        return $new;
    }
}
