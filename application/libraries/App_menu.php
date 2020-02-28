<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_menu
{
    private $ci;

    private $items = [];

    private $child = [];

    // top right user menu
    private $user_menu_items = [];

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function add_sidebar_menu_item($slug, $item)
    {
        $this->add($slug, $item, 'sidebar');

        return $this;
    }

    public function add_sidebar_children_item($parent_slug, $item)
    {
        $this->add_child($parent_slug, $item, 'sidebar');

        return $this;
    }

    public function get_sidebar_menu_child_items($parent_slug)
    {
        return $this->get_child($parent_slug, 'sidebar');
    }

    public function get_sidebar_menu_items()
    {
        return $this->get('sidebar');
    }

    public function add_setup_menu_item($slug, $item)
    {
        $this->add($slug, $item, 'setup');

        return $this;
    }

    public function add_setup_children_item($parent_slug, $item)
    {
        $this->add_child($parent_slug, $item, 'setup');

        return $this;
    }

    public function get_setup_menu_child_items($parent_slug)
    {
        return $this->get_child($parent_slug, 'setup');
    }

    public function get_setup_menu_items()
    {
        return $this->get('setup');
    }

    public function add_theme_item($slug, $item)
    {
        $this->add($slug, $item, 'theme');

        return $this;
    }

    public function get_theme_items()
    {
        return $this->get('theme');
    }

    /**
     * New User Menu Item
     * @param string $slug menu slug - unique
     * @param array $item item options
     * name - The name of the item - Required
     * icon - item icon class
     * href - item link
     * href_attrs - href attributes
     * position - the position of the item
     */
    public function add_user_menu_item($slug, $item)
    {
        $item                         = app_fill_empty_common_attributes($item);
        $item                         = ['slug' => $slug] + $item;
        $this->user_menu_items[$slug] = $item;

        return $this;
    }

    public function get_user_menu_items()
    {
        $items = hooks()->apply_filters('nav_user_menu_items', $this->user_menu_items);

        return app_sort_by_position($items);
    }

    /**
     * New Menu Item
     * @param string $slug menu slug - unique
     * @param array $item item options
     * name - The name of the item - - Required
     * icon - item icon class
     * href - item link
     * position - the position of the item
     */
    public function add($slug, $item, $group)
    {
        $item = app_fill_empty_common_attributes($item);
        $item = ['slug' => $slug] + $item;

        $this->items[$group][$slug] = $item;
    }

    /**
     * Add children item to existing menu item
     * @param string $parent_slug parent slug
     * @param array $item child menu item options
     * slug - The slug of the item - Required and Unique
     * name - The name of the item - Required
     * icon - item icon class
     * href - item link
     * position - the position of the item
     */
    public function add_child($parent_slug, $item, $group)
    {
        $item = app_fill_empty_common_attributes($item);

        $item = ['parent_slug' => $parent_slug] + $item;

        if ((!isset($this->child[$group][$parent_slug]) || !is_array($this->child[$group][$parent_slug]))) {
            $this->child[$group][$parent_slug] = [];
        }

        $this->child[$group][$parent_slug][] = $item;
    }

    public function get($group)
    {
        $items = isset($this->items[$group]) ? $this->items[$group] : [];

        foreach ($items as $parent => $item) {
            $items[$parent]['children'] = $this->get_child($parent, $group);
        }

        $items = hooks()->apply_filters("{$group}_menu_items", $items);

        return app_sort_by_position($items);
    }

    public function get_child($parent_slug, $group)
    {
        $children = isset($this->child[$group][$parent_slug]) ? $this->child[$group][$parent_slug] : [];

        $children = hooks()->apply_filters("{$group}_menu_child_items", $children, $parent_slug);

        return app_sort_by_position($children, true);
    }

    public function filter_item($items, $slug)
    {
        foreach ($items as $item) {
            if ($item['slug'] == $slug) {
                return $item;
            }
            foreach ($item['children'] as $child) {
                if ($child['slug'] == $slug) {
                    return $child;
                }
            }
        }

        return false;
    }

    public function get_initial_icon($slug, $group)
    {
        $items = isset($this->items[$group]) ? $this->items[$group] : [];

        foreach ($items as $parent => $item) {
            $items[$parent]['children'] = $this->get_child($parent, $group);
        }

        foreach ($items as $item) {
            if ($item['slug'] == $slug) {
                return $item['icon'];
            }

            foreach ($item['children'] as $child) {
                if ($child['slug'] == $slug) {
                    return $child['icon'];
                }
            }
        }

        return '';
    }
}
