<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_tabs
{
    private $ci;

    private $tabs = [];

    private $child = [];

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function add_customer_profile_tab($slug, $tab)
    {
        $this->add($slug, $tab, 'customer_profile');

        return $this;
    }

    public function get_customer_profile_tabs()
    {
        return $this->get('customer_profile');
    }

    public function add_project_tab($slug, $tab)
    {
        $this->add($slug, $tab, 'project');

        return $this;
    }

    public function add_project_tab_children_item($parent_slug, $tab)
    {
        $this->add_child($parent_slug, $tab, 'project');

        return $this;
    }

    public function get_project_tabs()
    {
        return $this->get('project');
    }

    public function add_settings_tab($slug, $tab)
    {
        $this->add($slug, $tab, 'settings');

        return $this;
    }

    public function add_settings_tab_children_item($parent_slug, $tab)
    {
        $this->add_child($parent_slug, $tab, 'settings');

        return $this;
    }

    public function get_settings_tabs()
    {
        return $this->get('settings');
    }

    /**
     * New Tab
     * @param string $slug tab slug - unique
     * @param array $tab item options
     * name - The name of the item - - Required
     * icon - item icon class
     * view - the view file to load as tab content
     * position - the position of the item
     * visible - whether is visible or not, not applied if custom settings for visible tab applied by the user
     */
    public function add($slug, $tab, $group)
    {
        $tab = app_fill_empty_common_attributes($tab);
        $tab = ['slug' => $slug] + $tab;

        $this->tabs[$group][$slug] = $tab;
    }

    /**
     * Add children item to existing tab item
     * @param string $parent_slug parent slug
     * @param array $item child tab item options
     * slug - The slug of the item - Required and Unique
     * name - The name of the item - - Required
     * icon - item icon class
     * view - the view file to load as tab content
     * position - the position of the item
     * visible - whether is visible or not, not applied if custom settings for visible tab applied by the user
     */
    public function add_child($parent_slug, $tab, $group)
    {
        $tab = app_fill_empty_common_attributes($tab);

        $tab = ['parent_slug' => $parent_slug] + $tab;

        if ((!isset($this->child[$group][$parent_slug]) || !is_array($this->child[$group][$parent_slug]))) {
            $this->child[$group][$parent_slug] = [];
        }

        $this->child[$group][$parent_slug][] = $tab;
    }

    public function get($group)
    {
        hooks()->do_action('before_get_tabs', $group);

        $tabs = isset($this->tabs[$group]) ? $this->tabs[$group] : [];

        foreach ($tabs as $parent => $item) {
            $tabs[$parent]['children'] = $this->get_child($parent, $group);
        }

        $tabs = hooks()->apply_filters("{$group}_tabs", $tabs);

        $tabs = $this->filter_visible_tabs($tabs);

        return app_sort_by_position($tabs);
    }

    public function get_child($parent_slug, $group)
    {
        $children = isset($this->child[$group][$parent_slug]) ? $this->child[$group][$parent_slug] : [];

        $children = hooks()->apply_filters("{$group}_tabs_child_items", $children, $parent_slug);

        return app_sort_by_position($children, true);
    }

    public function filter_visible_tabs($tabs)
    {
        $newTabs = [];
        foreach ($tabs as $key => $tab) {
            $dropdown = isset($tab['collapse']) ? true : false;

            if ($dropdown) {
                $totalChildTabsHidden = 0;
                $newChild             = [];

                foreach ($tab['children'] as $d) {
                    if (isset($d['visible']) && $d['visible'] == false) {
                        $totalChildTabsHidden++;
                    } else {
                        $newChild[] = $d;
                    }
                }

                if ($totalChildTabsHidden == count($tab['children'])) {
                    continue;
                }

                if (count($newChild) > 0) {
                    $tab['children'] = $newChild;
                }

                $newTabs[$tab['slug']] = $tab;
            } else {
                if ((isset($tab['visible']) && $tab['visible'] == true) || !isset($tab['visible'])) {
                    $newTabs[$tab['slug']] = $tab;
                }
            }
        }

        return $newTabs;
    }

    public function filter_tab($tabs, $slug)
    {
        foreach ($tabs as $tab) {
            if ($tab['slug'] == $slug) {
                return $tab;
            }

            foreach ($tab['children'] as $child) {
                if ($child['slug'] == $slug) {
                    return $child;
                }
            }
        }

        return false;
    }
}
