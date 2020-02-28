<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin dashboard widgets
 * We are registering all widgets here
 * Also action hook is included to add new widgets if needed in my_functions_helper.php
 * @return array
 */
function get_dashboard_widgets()
{
    $widgets = [
        [
            'path'      => 'admin/dashboard/widgets/top_stats',
            'container' => 'top-12',
        ],
        [
            'path'      => 'admin/dashboard/widgets/finance_overview',
            'container' => 'left-8',
        ],
        [
            'path'      => 'admin/dashboard/widgets/user_data',
            'container' => 'left-8',
        ],
        [
            'path'      => 'admin/dashboard/widgets/upcoming_events',
            'container' => 'left-8',
        ],
        [
            'path'      => 'admin/dashboard/widgets/calendar',
            'container' => 'left-8',
        ],
        [
            'path'      => 'admin/dashboard/widgets/weekly_payments_chart',
            'container' => 'left-8',
        ],
        [
            'path'      => 'admin/dashboard/widgets/todos',
            'container' => 'right-4',
        ],
        [
            'path'      => 'admin/dashboard/widgets/leads_chart',
            'container' => 'right-4',
        ],
        [
            'path'      => 'admin/dashboard/widgets/projects_chart',
            'container' => 'right-4',
        ],
        [
            'path'      => 'admin/dashboard/widgets/tickets_chart',
            'container' => 'right-4',
        ],
        [
            'path'      => 'admin/dashboard/widgets/projects_activity',
            'container' => 'right-4',
        ],
    ];

    return hooks()->apply_filters('get_dashboard_widgets', $widgets);
}

/**
 * Render widgets based on container
 * The function will check if staff have re-organized the dashboard and apply any order which is needed.
 * @param  string $container
 * @return mixed
 */
function render_dashboard_widgets($container)
{
    $widgets = get_dashboard_widgets();

    $allWidgetsHTML    = [];
    $widgetsIDS        = [];
    $widgetsContainers = [];

    foreach ($widgets as $key => $widget) {
        $wID = basename($widget['path'], '.php');

        $widgetsIDS[$wID] = [
            'widgetIndex'     => $key,
            'widgetPath'      => $widget['path'],
            'widgetContainer' => $widget['container'],
        ];

        if (!isset($widgetsContainers[$widget['container']])) {
            $widgetsContainers[$widget['container']] = [];
        }
        $widget['widgetID']                        = $wID;
        $widgetsContainers[$widget['container']][] = $widget;
    }

    $staff_dashboard = get_staff_meta(get_staff_user_id(), 'dashboard_widgets_order');
    $staff_dashboard = !$staff_dashboard ? [] : unserialize($staff_dashboard);

    $CI = &get_instance();
    if (count($staff_dashboard) == 0) {
        // Default widgets order and containers
        foreach ($widgets as $widget) {
            if ($widget['container'] == $container) {
                $allWidgetsHTML[basename($widget['path'], '.php')] = $CI->load->view($widget['path'], [], true);
            }
        }
    } else {
        $widgetsOutputted = [];
        if (isset($staff_dashboard[$container])) {
            foreach ($staff_dashboard[$container] as $widget) {
                $tmp        = explode('widget-', $widget);
                $widgetName = $tmp[1];
                if (isset($widgetsIDS[$widgetName])) {
                    array_push($widgetsOutputted, $widgetName);
                    $allWidgetsHTML[$widgetName] = $CI->load->view($widgetsIDS[$widgetName]['widgetPath'], [], true);
                }
            }
        }

        foreach ($widgetsIDS as $wID => $widget) {
            // Widget exists but not applied in any staff container settings
            $applied = [];

            foreach ($staff_dashboard as $c => $w) {
                if (in_array('widget-' . $wID, $w)) {
                    array_push($applied, $wID);
                }
            }

            if (!in_array($wID, $applied) && $widget['widgetContainer'] == $container) {
                array_push($widgetsOutputted, $wID);
                $allWidgetsHTML[$wID] = $CI->load->view($widget['widgetPath'], [], true);
            }
        }
    }

    $user_dashboard_visibility = get_staff_meta(get_staff_user_id(), 'dashboard_widgets_visibility');

    if (!$user_dashboard_visibility) {
        $user_dashboard_visibility = [];
    } else {
        $user_dashboard_visibility = unserialize($user_dashboard_visibility);
    }

    if (count($user_dashboard_visibility) > 0) {
        include_once(APPPATH . 'third_party/simple_html_dom.php');
    }

    foreach ($allWidgetsHTML as $widgetID => $widgetHTML) {
        foreach ($user_dashboard_visibility as $visibility) {
            if ($visibility['id'] == $widgetID && $visibility['visible'] == 0) {
                if ($widgetHTML != '') {
                    $html      = str_get_html($widgetHTML);
                    $parentDOM = $html->find('div[id="widget-' . $widgetID . '"]', 0);
                    if (strpos($parentDOM->class, 'hide') !== true) {
                        $parentDOM->class .= ' hide';
                    }
                    $widgetHTML = $html;
                }
            }
        }

        echo $widgetHTML;
    }
}
