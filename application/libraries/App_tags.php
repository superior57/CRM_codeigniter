<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_tags
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function get($name_or_id)
    {
        $this->ci->db->where('name', $name_or_id);
        $this->ci->db->or_where('id', $name_or_id);

        return $this->ci->db->get(db_prefix().'tags')->row();
    }

    public function create($data)
    {
        $this->ci->db->insert(db_prefix().'tags', $data);

        return $this->ci->db->insert_id();
    }

    public function save($tags, $rel_id, $rel_type)
    {
        $affectedRows = 0;
        if ($tags == '') {
            if ($this->relation_delete($rel_id, $rel_type)) {
                $affectedRows++;
            }
        } else {
            $tags_array = [];

            if (!is_array($tags)) {
                $tags = explode(',', $tags);
            }

            foreach ($tags as $tag) {
                $tag = trim($tag);
                if ($tag != '') {
                    array_push($tags_array, $tag);
                }
            }

            // Check if there is removed tags
            $current_tags = get_tags_in($rel_id, $rel_type);

            foreach ($current_tags as $tag) {
                if (!in_array($tag, $tags_array)) {
                    $tag = $this->get($tag);

                    $this->ci->db->where('rel_id', $rel_id);
                    $this->ci->db->where('rel_type', $rel_type);
                    $this->ci->db->where('tag_id', $tag->id);
                    $this->ci->db->delete(db_prefix().'taggables');
                    if ($this->ci->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }

            // Insert new ones
            $order = 1;
            foreach ($tags_array as $tag) {

             // Double quotes not allowed
                $tag = str_replace('"', '\'', $tag);

                $tag_row = $this->get($tag);

                if ($tag_row) {
                    $tag_id = $tag_row->id;
                } else {
                    $tag_id = $this->create(['name' => $tag]);
                    hooks()->do_action('new_tag_created', $tag_id);
                }

                if ($this->is_related($tag_id, $rel_id, $rel_type)) {
                    $this->ci->db->insert(
                    db_prefix().'taggables',
                    [
                        'tag_id'    => $tag_id,
                        'rel_id'    => $rel_id,
                        'rel_type'  => $rel_type,
                        'tag_order' => $order,
                    ]
                );
                    if ($this->ci->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
                $order++;
            }
        }

        return ($affectedRows > 0 ? true : false);
    }

    public function is_related($tag_id, $rel_id, $rel_type)
    {
        return total_rows(db_prefix().'taggables', ['tag_id' => $tag_id, 'rel_id' => $rel_id, 'rel_type' => $rel_type]) == 0;
    }

    public function relation($rel_id, $rel_type)
    {
        $this->ci->db->where('rel_id', $rel_id);
        $this->ci->db->where('rel_type', $rel_type);
        $this->ci->db->order_by('tag_order', 'ASC');
        $tags = $this->ci->db->get(db_prefix().'taggables')->result_array();

        $tag_names = [];
        foreach ($tags as $tag) {
            $tag_row = $this->get($tag['tag_id']);
            if ($tag_row) {
                array_push($tag_names, $tag_row->name);
            }
        }

        return $tag_names;
    }

    public function relation_delete($rel_id, $rel_type)
    {
        $this->ci->db->where('rel_id', $rel_id);
        $this->ci->db->where('rel_type', $rel_type);
        $this->ci->db->delete(db_prefix().'taggables');

        return $this->ci->db->affected_rows() > 0;
    }

    public function all()
    {
        $tags = $this->ci->app_object_cache->get('db-tags-array');

        if (!$tags && !is_array($tags)) {
            $this->ci->db->order_by('name', 'ASC');
            $tags = $this->ci->db->get(db_prefix().'tags')->result_array();
            $this->ci->app_object_cache->add('db-tags-array', $tags);
        }

        return $tags;
    }

    public function flat()
    {
        return $this->extract('name');
    }

    public function ids()
    {
        return $this->extract('id');
    }

    private function extract($key)
    {
        $tmp_tags = [];
        $tags     = $this->all();
        foreach ($tags as $tag) {
            array_push($tmp_tags, $tag[$key]);
        }
        $tags = $tmp_tags;

        return $tags;
    }
}
