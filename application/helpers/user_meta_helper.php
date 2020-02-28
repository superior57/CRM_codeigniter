<?php

defined('BASEPATH') or exit('No direct script access allowed');

function add_staff_meta($user_id, $meta_key, $meta_value = '')
{
    return add_meta('staff', $user_id, $meta_key, $meta_value);
}

function update_staff_meta($user_id, $meta_key, $meta_value)
{
    return update_meta('staff', $user_id, $meta_key, $meta_value);
}

function get_staff_meta($user_id, $meta_key = '')
{
    return get_meta('staff', $user_id, $meta_key);
}

function delete_staff_meta($user_id, $meta_key)
{
    return delete_meta('staff', $user_id, $meta_key);
}

function add_contact_meta($user_id, $meta_key, $meta_value = '')
{
    return add_meta('contact', $user_id, $meta_key, $meta_value);
}

function update_contact_meta($user_id, $meta_key, $meta_value)
{
    return update_meta('contact', $user_id, $meta_key, $meta_value);
}

function get_contact_meta($user_id, $meta_key = '')
{
    return get_meta('contact', $user_id, $meta_key);
}

function delete_contact_meta($user_id, $meta_key)
{
    return delete_meta('contact', $user_id, $meta_key);
}

function add_customer_meta($user_id, $meta_key, $meta_value = '')
{
    return add_meta('customer', $user_id, $meta_key, $meta_value);
}

function update_customer_meta($user_id, $meta_key, $meta_value)
{
    return update_meta('customer', $user_id, $meta_key, $meta_value);
}

function get_customer_meta($user_id, $meta_key = '')
{
    return get_meta('customer', $user_id, $meta_key);
}

function delete_customer_meta($user_id, $meta_key)
{
    return delete_meta('customer', $user_id, $meta_key);
}

function add_meta($for, $user_id, $meta_key, $meta_value = '')
{
    /**
     * Do not insert the meta key if already exists
     * Meta keys must be always unique
     */
    if (meta_key_exists($for, $user_id, $meta_key)) {
        return false;
    }

    $CI     = &get_instance();
    $column = _get_meta_key_query_column_for($for);

    if (!$column) {
        return false;
    }

    $CI->db->insert(db_prefix().'user_meta', [
        $column      => $user_id,
        'meta_key'   => $meta_key,
        'meta_value' => $meta_value,
    ]);

    return $CI->db->insert_id();
}

function update_meta($for, $user_id, $meta_key, $meta_value)
{
    /**
     * If user meta do not exists create one
     */
    if (!meta_key_exists($for, $user_id, $meta_key)) {
        return add_meta($for, $user_id, $meta_key, $meta_value);
    }

    $CI = &get_instance();

    if ($column = _get_meta_key_query_column_for($for)) {
        $CI->db->where($column, $user_id);
    } else {
        return false;
    }
    $CI->db->where('meta_key', $meta_key);
    $CI->db->update(db_prefix().'user_meta', ['meta_value' => $meta_value]);

    return $CI->db->affected_rows() > 0;
}

function meta_key_exists($for, $user_id, $meta_key)
{
    $CI = &get_instance();
    if ($column = _get_meta_key_query_column_for($for)) {
        $CI->db->where($column, $user_id);
    } else {
        return false;
    }
    $CI->db->where('meta_key', $meta_key);

    return $CI->db->count_all_results(db_prefix().'user_meta') > 0;
}

function delete_meta($for, $user_id, $meta_key)
{
    $CI = &get_instance();
    if ($column = _get_meta_key_query_column_for($for)) {
        $CI->db->where($column, $user_id);
    } else {
        return false;
    }
    $CI->db->where('meta_key', $meta_key);
    $CI->db->delete(db_prefix().'user_meta');

    return $CI->db->affected_rows() > 0;
}

function get_meta($for, $user_id, $meta_key = '')
{
    $CI   = &get_instance();
    $meta = $CI->app_object_cache->get($for . '-meta-' . $user_id);

    if ($meta) {
        if ($meta_key) {
            return isset($meta[$meta_key]) ? $meta[$meta_key] : '';
        }

        return $meta;
    }
    $meta   = [];
    $column = _get_meta_key_query_column_for($for);

    if (!$column) {
        return $meta;
    }

    $CI->db->where($column, $user_id);
    $meta = $CI->db->get(db_prefix().'user_meta')->result_array();

    $flat = [];

    foreach ($meta as $m) {
        $flat[$m['meta_key']] = $m['meta_value'];
    }

    if (count($flat) === 0) {
        return $meta_key ? '' : [];
    }

    $CI->app_object_cache->add($for . '-meta-' . $user_id, $flat);

    return get_meta($for, $user_id, $meta_key);
}

function _get_meta_key_query_column_for($for)
{
    if ($for == 'staff') {
        return 'staff_id';
    } elseif ($for == 'contact') {
        return 'contact_id';
    } elseif ($for == 'customer') {
        return 'client_id';
    }

    // No delete for all metas
    return false;
}
