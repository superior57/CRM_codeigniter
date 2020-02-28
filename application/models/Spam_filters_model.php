<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Spam_filters_model extends App_Model
{
    private $table;

    public function __construct()
    {
        $this->table = db_prefix().'spam_filters';
        parent::__construct();
    }

    public function get($rel_type)
    {
        $this->db->where('rel_type', $rel_type);

        return $this->db->get($this->table)->result_array();
    }

    public function add($data, $type)
    {
        $data['rel_type'] = $type;
        $this->db->insert($this->table, $data);
        $insert_id = $this->db->insert_id();

        return $insert_id ? $insert_id : false;
    }

    public function edit($data)
    {
        $this->db->where('id', $data['id']);
        unset($data['id']);
        $this->db->update($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function delete($id, $type)
    {
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        if ($this->db->affected_rows() > 0) {
            log_activity('Spam Filter Deleted');

            return true;
        }

        return false;
    }

    public function check($email, $subject, $message, $rel_type)
    {
        $status       = false;
        $spam_filters = $this->get($rel_type);

        foreach ($spam_filters as $filter) {
            $type  = $filter['type'];
            $value = $filter['value'];
            if ($type == 'sender') {
                if (strtolower($value) == strtolower($email)) {
                    $status = 'Blocked Sender';
                }
            }
            if ($type == 'subject') {
                if (strpos('x' . strtolower($subject), strtolower($value))) {
                    $status = 'Blocked Subject';
                }
            }
            if ($type == 'phrase') {
                if (strpos('x' . strtolower($message), strtolower($value))) {
                    $status = 'Blocked Phrase';
                }
            }
        }

        return $status;
    }
}
