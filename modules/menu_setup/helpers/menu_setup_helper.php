<?php

defined('BASEPATH') or exit('No direct script access allowed');

function app_admin_sidebar_custom_options($items)
{
    return _apply_menu_items_options($items, json_decode(get_option('aside_menu_active')));
}

function app_admin_sidebar_custom_positions($items)
{
    return _apply_menu_items_position($items, json_decode(get_option('aside_menu_active')));
}

function app_admin_setup_menu_custom_options($items)
{
    return _apply_menu_items_options($items, json_decode(get_option('setup_menu_active')));
}

function app_admin_setup_menu_custom_positions($items)
{
    return _apply_menu_items_position($items, json_decode(get_option('setup_menu_active')));
}

function _apply_menu_items_options($items, $options)
{
    foreach ($items as $key => $item) {
        if (isset($options->{$item['slug']})) {
            if (isset($options->{$item['slug']}->disabled)
                && $options->{$item['slug']}->disabled === 'true') {
                // Main item is disabled
                unset($items[$key]);
            } else {
                // Main item has custom icon
                if (isset($options->{$item['slug']}->icon) && $options->{$item['slug']}->icon === false) {
                    // False is when user set the icon empty from the builder
                    $items[$key]['icon'] = '';
                } elseif (!empty($options->{$item['slug']}->icon)) {
                    $items[$key]['icon'] = $options->{$item['slug']}->icon;
                }
            }

            foreach ($item['children'] as $childKey => $child) {
                if (isset($options->{$item['slug']}->children->{$child['slug']})) {
                    if (isset($options->{$item['slug']}->children->{$child['slug']}->disabled)
                        && $options->{$item['slug']}->children->{$child['slug']}->disabled === 'true') {
                        // Is disabled
                        unset($items[$key]['children'][$childKey]);
                    } else {
                        // Has custom icon
                        if ($options->{$item['slug']}->children->{$child['slug']}->icon === false) {
                            $items[$key]['children'][$childKey]['icon'] = '';
                        } elseif (!empty($options->{$item['slug']}->children->{$child['slug']}->icon)) {
                            $items[$key]['children'][$childKey]['icon'] = $options->{$item['slug']}->children->{$child['slug']}->icon;
                        }
                    }
                }
            }
        }
    }

    return $items;
}

function _apply_menu_items_position($items, $options)
{
    if (!is_array($options)) {
        $CI = &get_instance();
        // Has applied options
        $newItems          = [];
        $newItemsAddedKeys = [];
        foreach ($options as $key => $item) {
            // Check if the item is found because can be removed
            if ($newItem = $CI->app_menu->filter_item($items, $item->id)) {

                $newItems[$key]      = $newItem;
                $newItemsAddedKeys[] = $key;

                $newItems[$key]['children'] = [];
                if (isset($item->children)) {
                    foreach ($item->children as $child) {
                        if ($newChildItem = $CI->app_menu->filter_item($items, $child->id)) {
                            $newItems[$key]['children'][] = $newChildItem;
                            $newItemsAddedKeys[]          = $newChildItem['slug'];
                        }
                    }
                }
            }
        }

        // Let's check if item is missed from $items to $newItems
        foreach ($items as $key => $item) {
            if (!in_array($key, $newItemsAddedKeys)) {
                $newItems[$key] = $item;
            }

            if (isset($item['collapse'])) {
                foreach ($item['children'] as $childKey => $child) {
                    if (!in_array($child['slug'], $newItemsAddedKeys)) {
                        $newItems[$key]['children'][] = $child;
                    }
                }
            }
        }

        $items = $newItems;
    }

    // Finally apply the positions
    foreach ($items as $key => $item) {
        if (isset($options->{$item['slug']})) {

            $items[$key]['position'] = (int) $options->{$item['slug']}->position;

            foreach ($item['children'] as $childKey => $child) {
                if (isset($options->{$item['slug']}->children->{$child['slug']})) {
                    $items[$key]['children'][$childKey]['position'] = (int) $options->{$item['slug']}->children->{$child['slug']}->position;
                }
            }
        }
    }

    return $items;
}

function _menu_options_filter_child($menu_options, $slug)
{
    foreach ($menu_options as $option) {
        if (isset($option->children)) {
            foreach ($option->children as $childKey => $child) {
                if ($child->id == $slug) {
                    return $child;
                }
            }
        }
    }

    return false;
}

function app_get_menu_setup_icon($menu_options, $slug, $group)
{
    $child = _menu_options_filter_child($menu_options, $slug);

    // No options applied
    if (!isset($menu_options->{$slug}) && $child === false) {
        return get_instance()->app_menu->get_initial_icon($slug, $group);
    }

    // Icon is set empty by user on parent item
    if (isset($menu_options->{$slug})
        && $menu_options->{$slug}->icon === false) {
        return '';
    }

    // Icon is set empty by user on child item
    if ($child !== false && $child->icon === false) {
        return '';
    }

    // no icon applied, get the initial icon
    if (isset($menu_options->{$slug}) && $menu_options->{$slug}->icon === '') {
        return get_instance()->app_menu->get_initial_icon($slug, $group);
    } elseif (isset($menu_options->{$slug})) {
        // Custom icon is set on parent
        return $menu_options->{$slug}->icon;
    }
    // no icon applied, get the initial icon
    if ($child && $child->icon === '') {
        return get_instance()->app_menu->get_initial_icon($slug, $group);
    } elseif ($child) {
        // Custom icon is set on child
        return $child->icon;
    }

    return '';
}
