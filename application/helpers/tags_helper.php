<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Function that add and edit tags based on passed arguments
 * @param  string $tags
 * @param  mixed $rel_id
 * @param  string $rel_type
 * @return boolean
 */
function handle_tags_save($tags, $rel_id, $rel_type)
{
    return _call_tags_method('save', $tags, $rel_id, $rel_type);
}
/**
 * Get tag from db by name
 * @param  string $name
 * @return object
 */
function get_tag_by_name($name)
{
    return _call_tags_method('get', $name);
}
/**
 * Function that will return all tags used in the app
 * @return array
 */
function get_tags()
{
    return _call_tags_method('all');
}
/**
 * Array of available tags without the keys
 * @return array
 */
function get_tags_clean()
{
    return _call_tags_method('flat');
}
/**
 * Get all tag ids
 * @return array
 */
function get_tags_ids()
{
    return _call_tags_method('ids');
}
/**
 * Function that will parse all the tags and return array with the names
 * @param  string $rel_id
 * @param  string $rel_type
 * @return array
 */
function get_tags_in($rel_id, $rel_type)
{
    return _call_tags_method('relation', $rel_id, $rel_type);
}

/**
 * Helper function to call App_tags method
 * @param  string $method method to call
 * @param  mixed $params params
 * @return mixed
 */
function _call_tags_method($method, ...$params)
{
    $CI = &get_instance();

    if (!class_exists('app_tags', false)) {
        $CI->load->library('app_tags');
    }

    return $CI->app_tags->{$method}(...$params);
}

/**
 * Coma separated tags for input
 * @param  array $tag_names
 * @return string
 */
function prep_tags_input($tag_names)
{
    $tag_names = array_filter($tag_names, function ($value) {
        return $value !== '';
    });

    return implode(',', $tag_names);
}


/**
 * Function will render tags as html version to show to the user
 * @param  string $tags
 * @return string
 */
function render_tags($tags)
{
    $tags_html = '';
    if (!is_array($tags)) {
        $tags = explode(',', $tags);
    }
    $tags = array_filter($tags, function ($value) {
        return $value !== '';
    });

    if (count($tags) > 0) {
        $CI = &get_instance();

        $tags_html .= '<div class="tags-labels">';
        $i   = 0;
        $len = count($tags);
        foreach ($tags as $tag) {
            $tag_id  = 0;
            $tag_row = $CI->app_object_cache->get('tag-id-by-name-' . $tag);
            if (!$tag_row) {
                $tag_row = get_tag_by_name($tag);

                if ($tag_row) {
                    $CI->app_object_cache->add('tag-id-by-name-' . $tag, $tag_row->id);
                }
            }

            if ($tag_row) {
                $tag_id = is_object($tag_row) ? $tag_row->id : $tag_row;
            }

            $tags_html .= '<span class="label label-tag tag-id-' . $tag_id . '"><span class="tag">' . $tag . '</span><span class="hide">' . ($i != $len - 1 ? ', ' : '') . '</span></span>';
            $i++;
        }
        $tags_html .= '</div>';
    }

    return $tags_html;
}
