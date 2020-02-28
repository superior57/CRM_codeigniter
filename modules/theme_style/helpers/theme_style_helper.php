<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Defined styling areas for the theme style feature
 * Those string are not translated to keep the language file neat
 * @param  string $type
 * @return array
 */
function get_styling_areas($type = 'admin')
{
    $areas = [
        'admin' => [
            [
                'name'                 => _l('theme_style_sidebar_bg_color'),
                'id'                   => 'admin-menu',
                'target'               => '.admin #side-menu,.admin #setup-menu',
                'css'                  => 'background',
                'additional_selectors' => 'body|background+#setup-menu-wrapper|background',
            ],
            [
                'name'                 => _l('theme_style_sidebar_open_bg_color'),
                'id'                   => 'admin-menu-submenu-open',
                'target'               => '.admin #side-menu li .nav-second-level li,.admin #setup-menu li .nav-second-level li',
                'css'                  => 'background',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_sidebar_links_color'),
                'id'                   => 'admin-menu-links',
                'target'               => '.admin #side-menu li a,.admin #setup-menu li a',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_sidebar_user_welcome_text_color'),
                'id'                   => 'user-welcome-text-color',
                'target'               => '#side-menu li.dashboard_user',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
            [
                'name'   => _l('theme_style_sidebar_active_item_bg_color'),
                'id'     => 'admin-menu-active-item',
                'target' => '
                .admin #side-menu li.active > a,
                .admin #setup-menu li.active > a,
                #side-menu.nav > li > a:hover,
                #side-menu.nav > li > a:focus,
                #setup-menu > li > a:hover,
                #setup-menu > li > a:focus',
                'css'                  => 'background',
                'additional_selectors' => '',
            ],
            [
                'name'   => _l('theme_style_sidebar_active_item_color'),
                'id'     => 'admin-menu-active-item-color',
                'target' => '
                .admin #side-menu li.active > a,
                .admin #setup-menu li.active > a',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_sidebar_active_sub_item_bg_color'),
                'id'                   => 'admin-menu-active-subitem',
                'target'               => '.admin #side-menu li .nav-second-level li.active a,.admin #setup-menu li .nav-second-level li.active a',
                'css'                  => 'background',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_sidebar_active_sub_item_links_color'),
                'id'                   => 'admin-menu-submenu-links',
                'target'               => '.admin #side-menu li .nav-second-level li a,#setup-menu li .nav-second-level li a',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_top_header_bg_color'),
                'id'                   => 'top-header',
                'target'               => '.admin #header',
                'css'                  => 'background',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_top_header_bg_links_color'),
                'id'                   => 'top-header-links',
                'target'               => '.admin .navbar-nav > li > a, ul.mobile-icon-menu>li>a,.mobile-menu-toggle, .open-customizer-mobile',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
        ],
        'customers' => [
            [
                'name'                 => _l('theme_style_navigation_bg_color'),
                'id'                   => 'customers-navigation',
                'target'               => '.customers .navbar-default',
                'css'                  => 'background',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_navigation_link_color'),
                'id'                   => 'customers-navigation-links',
                'target'               => '.customers .navbar-default .navbar-nav>li>a',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_footer_background'),
                'id'                   => 'customers-footer-background',
                'target'               => '.customers footer',
                'css'                  => 'background',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_footer_text_color'),
                'id'                   => 'customers-footer-text',
                'target'               => '.customers footer',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
        ],
        'general' => [
            [
                'name'                 => '<a href="#" onclick="return false;">' . _l('theme_style_links') . '</a> ' . _l('theme_style_color') . ' (href)',
                'id'                   => 'links-color',
                'target'               => 'a',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_link_hover_color'),
                'id'                   => 'links-hover-focus',
                'target'               => 'a:hover,a:focus',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_table_headings_color'),
                'id'                   => 'table-headings',
                'target'               => 'table.dataTable thead tr>th, .table.dataTable>thead:first-child>tr:first-child>th',
                'css'                  => 'color',
                'additional_selectors' => '',
                'example'              => '<table class="table dataTable"><thead><tr><th style="border-bottom: 1px solid #f0f0f0" class="sorting">' . _l('theme_style_example_table_heading') . ' 1</th><th style="border-bottom: 1px solid #f0f0f0" class="sorting">' . _l('theme_style_example_table_heading') . ' 2</th></tr></thead></table>',
            ],
            [
                'name'                 => 'Items Table Headings Background Color',
                'id'                   => 'table-items-heading',
                'target'               => '.table.items thead',
                'css'                  => 'background',
                'additional_selectors' => '',
                'example'              => '<table class="table items"><thead><tr><th>' . _l('theme_style_example_table_heading') . ' 1</th><th>' . _l('theme_style_example_table_heading') . ' 2</th></tr></thead></table>',
            ],
            [
                'name'                 => _l('theme_style_admin_login_background'),
                'id'                   => 'admin-login-background',
                'target'               => 'body.login_admin',
                'css'                  => 'background',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_text_muted'),
                'id'                   => 'text-muted',
                'target'               => '.text-muted',
                'css'                  => 'color',
                'additional_selectors' => '',
                'example'              => '<p>' . _l('theme_style_example_text', '<span class="bold text-muted">' . _l('theme_style_text_muted') . '</span>') . '</p>',
            ],
            [
                'name'                 => _l('theme_style_text_danger'),
                'id'                   => 'text-danger',
                'target'               => '.text-danger',
                'css'                  => 'color',
                'additional_selectors' => '',
                'example'              => '<p>' . _l('theme_style_example_text', '<span class="bold text-danger">' . _l('theme_style_text_danger') . '</span>') . '</p>',
            ],
            [
                'name'                 => _l('theme_style_text_warning'),
                'id'                   => 'text-warning',
                'target'               => '.text-warning',
                'css'                  => 'color',
                'additional_selectors' => '',
                'example'              => '<p>' . _l('theme_style_example_text', '<span class="bold text-warning">' . _l('theme_style_text_warning') . '</span>') . '</p>',
            ],
            [
                'name'                 => _l('theme_style_text_info'),
                'id'                   => 'text-info',
                'target'               => '.text-info',
                'css'                  => 'color',
                'additional_selectors' => '',
                'example'              => '<p>' . _l('theme_style_example_text', '<span class="bold text-info">' . _l('theme_style_text_info') . '</span>') . '</p>',
            ],
            [
                'name'                 => _l('theme_style_text_success'),
                'id'                   => 'text-success',
                'target'               => '.text-success',
                'css'                  => 'color',
                'additional_selectors' => '',
                'example'              => '<p>' . _l('theme_style_example_text', '<span class="bold text-success">' . _l('theme_style_text_success') . '</span>') . '</p>',
            ],
        ],
        'tabs' => [
            [
                'name'                 => _l('theme_style_tabs_bg_color'),
                'id'                   => 'tabs-bg',
                'target'               => '.nav-tabs',
                'css'                  => 'background',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_tabs_links_color'),
                'id'                   => 'tabs-links',
                'target'               => '.nav-tabs>li>a',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_tabs_active_links_color'),
                'id'                   => 'tabs-links-active-hover',
                'target'               => '.nav-tabs>li.active>a, .nav-tabs>li.active>a:focus, .nav-tabs>li.active>a:hover, .nav-tabs>li>a:focus, .nav-tabs>li>a:hover',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],

            [
                'name'                 => _l('theme_style_tabs_active_border_color'),
                'id'                   => 'tabs-active-border',
                'target'               => '.nav-tabs>li.active>a, .nav-tabs>li.active>a:focus, .nav-tabs>li.active>a:hover, .nav-tabs>li>a:focus, .nav-tabs>li>a:hover',
                'css'                  => 'border-bottom-color',
                'additional_selectors' => '',
            ],
        ],
        'modals' => [
            [
                'name'                 => _l('theme_style_modal_heading_bg'),
                'id'                   => 'modal-heading',
                'target'               => '.modal-header',
                'css'                  => 'background',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_modal_heading_color'),
                'id'                   => 'modal-heading-color',
                'target'               => '.modal-header .modal-title',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_modal_close_btn_color'),
                'id'                   => 'modal-close-button-color',
                'target'               => '.modal-header .close',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
            [
                'name'                 => _l('theme_style_modal_white_text_color'),
                'id'                   => 'modal-header-white-text-color',
                'target'               => '.modal-header .color-white',
                'css'                  => 'color',
                'additional_selectors' => '',
            ],
        ],
        'buttons' => [
            [
                'name'                 => _l('theme_style_button_default'),
                'id'                   => 'btn-default',
                'target'               => '.btn-default',
                'css'                  => 'background-color',
                'additional_selectors' => '.btn-default|border-color',
                'example'              => '<button type="button" class="btn btn-default">' . _l('theme_style_button_default') . '</button>',
            ],
            [
                'name'                 => _l('theme_style_button_info'),
                'id'                   => 'btn-info',
                'target'               => '.btn-info',
                'css'                  => 'background-color',
                'additional_selectors' => '.btn-info|border-color',
                'example'              => '<button type="button" class="btn btn-info">' . _l('theme_style_button_info') . '</button>',
            ],
            [
                'name'                 => _l('theme_style_button_success'),
                'id'                   => 'btn-success',
                'target'               => '.btn-success',
                'css'                  => 'background-color',
                'additional_selectors' => '.btn-success|border-color',
                'example'              => '<button type="button" class="btn btn-success">' . _l('theme_style_button_success') . '</button>',
            ],
            [
                'name'                 => _l('theme_style_button_danger'),
                'id'                   => 'btn-danger',
                'target'               => '.btn-danger',
                'css'                  => 'background-color',
                'additional_selectors' => '.btn-danger|border-color',
                'example'              => '<button type="button" class="btn btn-danger">' . _l('theme_style_button_danger') . '</button>',
            ],
        ],
    ];


    $CI   = & get_instance();
    $tags = get_tags();

    $areas['tags'] = [];

    foreach ($tags as $tag) {
        array_push($areas['tags'], [
                'name'                 => $tag['name'],
                'id'                   => 'tag-' . $tag['id'],
                'target'               => '.tag-id-' . $tag['id'],
                'css'                  => 'color',
                'additional_selectors' => '.tag-id-' . $tag['id'] . '|border-color+ul.tagit li.tagit-choice-editable.tag-id-' . $tag['id'] . '|border-color+ul.tagit li.tagit-choice.tag-id-' . $tag['id'] . ' .tagit-label:not(a)|color',
                'example'              => '<span class="label label-tag tag-id-' . $tag['id'] . '">' . $tag['name'] . '</span>',
            ]);
    }

    $areas = hooks()->apply_filters('get_styling_areas', $areas);

    if (!is_array($type)) {
        return $areas[$type];
    }

    $_areas = [];
    foreach ($type as $t) {
        $_areas[] = $areas[$t];
    }

    return $_areas;
}
/**
 * Will fetch from database the stored applied styles and return
 * @return object
 */
function get_applied_styling_area()
{
    $theme_style = get_option('theme_style');
    if ($theme_style == '') {
        return [];
    }
    $theme_style = json_decode($theme_style);

    return $theme_style;
}
/**
 * Function that will parse and render the applied styles
 * @param  string $type
 * @return void
 */
function theme_style_render($type)
{
    $theme_style   = get_applied_styling_area();
    $styling_areas = get_styling_areas($type);


    foreach ($styling_areas as $type => $area) {
        foreach ($area as $_area) {
            foreach ($theme_style as $applied_style) {
                if ($applied_style->id == $_area['id']) {
                    echo '<style class="custom_style_' . $_area['id'] . '">' . PHP_EOL;
                    echo $_area['target'] . '{' . PHP_EOL;
                    echo $_area['css'] . ':' . $applied_style->color . ';' . PHP_EOL;
                    echo '}' . PHP_EOL;
                    if (startsWith($_area['target'], '.btn')) {
                        echo '
                        ' . $_area['target'] . ':focus,' . $_area['target'] . '.focus,' . $_area['target'] . ':hover,' . $_area['target'] . ':active,
                        ' . $_area['target'] . '.active,
                        .open > .dropdown-toggle' . $_area['target'] . ',' . $_area['target'] . ':active:hover,
                        ' . $_area['target'] . '.active:hover,
                        .open > .dropdown-toggle' . $_area['target'] . ':hover,
                        ' . $_area['target'] . ':active:focus,
                        ' . $_area['target'] . '.active:focus,
                        .open > .dropdown-toggle' . $_area['target'] . ':focus,
                        ' . $_area['target'] . ':active.focus,
                        ' . $_area['target'] . '.active.focus,
                        .open > .dropdown-toggle' . $_area['target'] . '.focus,
                        ' . $_area['target'] . ':active,
                        ' . $_area['target'] . '.active,
                        .open > .dropdown-toggle' . $_area['target'] . '{background-color:' . adjust_color_brightness($applied_style->color, -50) . ';color:#fff;border-color:' . adjust_color_brightness($applied_style->color, -50) . '}';
                        echo '
                        ' . $_area['target'] . '.disabled,
                        ' . $_area['target'] . '[disabled],
                        fieldset[disabled] ' . $_area['target'] . ',
                        ' . $_area['target'] . '.disabled:hover,
                        ' . $_area['target'] . '[disabled]:hover,
                        fieldset[disabled] ' . $_area['target'] . ':hover,
                        ' . $_area['target'] . '.disabled:focus,
                        ' . $_area['target'] . '[disabled]:focus,
                        fieldset[disabled] ' . $_area['target'] . ':focus,
                        ' . $_area['target'] . '.disabled.focus,
                        ' . $_area['target'] . '[disabled].focus,
                        fieldset[disabled] ' . $_area['target'] . '.focus,
                        ' . $_area['target'] . '.disabled:active,
                        ' . $_area['target'] . '[disabled]:active,
                        fieldset[disabled] ' . $_area['target'] . ':active,
                        ' . $_area['target'] . '.disabled.active,
                        ' . $_area['target'] . '[disabled].active,
                        fieldset[disabled] ' . $_area['target'] . '.active {
                            background-color: ' . adjust_color_brightness($applied_style->color, 50) . ';color:#fff;border-color:' . adjust_color_brightness($applied_style->color, 50) . ';}';
                    }
                    if ($_area['additional_selectors'] != '') {
                        $additional_selectors = explode('+', $_area['additional_selectors']);
                        foreach ($additional_selectors as $as) {
                            $_temp = explode('|', $as);
                            echo $_temp[0] . ' {' . PHP_EOL;
                            echo $_temp[1] . ':' . $applied_style->color . ';' . PHP_EOL;
                            echo '}' . PHP_EOL;
                        }
                    }
                    echo '</style>' . PHP_EOL;
                }
            }
        }
    }
}
/**
 * Get selected value for some styling area for the Theme style feature
 * @param  string $type
 * @param  string $selector
 * @return string
 */
function get_custom_style_values($type, $selector)
{
    $value         = '';
    $theme_style   = get_applied_styling_area();
    $styling_areas = get_styling_areas($type);
    foreach ($styling_areas as $area) {
        if ($area['id'] == $selector) {
            foreach ($theme_style as $applied_style) {
                if ($applied_style->id == $selector) {
                    $value = $applied_style->color;

                    break;
                }
            }
        }
    }

    return $value;
}

function render_theme_styling_picker($id, $value, $target, $css, $additional = '')
{
    echo '<div class="input-group mbot15 colorpicker-component" data-target="' . $target . '" data-css="' . $css . '" data-additional="' . $additional . '">
    <input type="text" value="' . $value . '" data-id="' . $id . '" class="form-control" />
    <span class="input-group-addon"><i></i></span>
    </div>';
}
