<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Announcements_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get announcements
     * @param  string $id    optional id
     * @param  array  $where perform where
     * @param  string $limit
     * @return mixed
     */
    public function get($id = '', $where = [], $limit = '')
    {
        $this->db->where($where);

        if (is_numeric($id)) {
            $this->db->where('announcementid', $id);

            return $this->db->get(db_prefix() . 'announcements')->row();
        }

        if (count($where) == 0 && $limit == '') {
            $announcements = $this->app_object_cache->get('all-user-announcements');
            if (!$announcements && !is_array($announcements)) {
                $this->_annoucements_query();
                $announcements = $this->db->get(db_prefix() . 'announcements')->result_array();
                $this->app_object_cache->add('all-user-announcements', $announcements);
            }
        } else {
            $this->_annoucements_query();

            if (is_numeric($limit)) {
                $this->db->limit($limit);
            }

            $announcements = $this->db->get(db_prefix() . 'announcements')->result_array();
        }

        return $announcements;
    }

    /**
     * Get total dismissed announcements for logged in user
     * @return mixed
     */
    public function get_total_undismissed_announcements()
    {
        if (!is_logged_in()) {
            return 0;
        }

        $staff  = is_client_logged_in() ? 0 : 1;
        $userid = is_client_logged_in() ? get_client_user_id() : get_staff_user_id();

        $sql = 'SELECT COUNT(*) as total_undismissed FROM ' . db_prefix() . 'announcements WHERE announcementid NOT IN (SELECT announcementid FROM ' . db_prefix() . 'dismissed_announcements WHERE staff=' . $staff . ' AND userid=' . $userid . ')';
        if ($staff == 1) {
            $sql .= ' AND showtostaff=1';
        } else {
            $sql .= ' AND showtousers=1';
        }

        return $this->db->query($sql)->row()->total_undismissed;
    }

    /**
     * @param $_POST array
     * @return Insert ID
     * Add new announcement calling this function
     */
    public function add($data)
    {
        $data['dateadded'] = date('Y-m-d H:i:s');

        if (isset($data['showname'])) {
            $data['showname'] = 1;
        } else {
            $data['showname'] = 0;
        }
        if (isset($data['showtostaff'])) {
            $data['showtostaff'] = 1;
        } else {
            $data['showtostaff'] = 0;
        }
        if (isset($data['showtousers'])) {
            $data['showtousers'] = 1;
        } else {
            $data['showtousers'] = 0;
        }
        $data['message'] = $data['message'];
        $data['userid']  = get_staff_full_name(get_staff_user_id());

        $data = hooks()->apply_filters('before_announcement_added', $data);

        $this->db->insert(db_prefix() . 'announcements', $data);
        $insert_id = $this->db->insert_id();

        hooks()->do_action('announcement_created', $insert_id);

        log_activity('New Announcement Added [' . $data['name'] . ']');

        return $insert_id;
    }

    /**
     * @param  $_POST array
     * @param  integer
     * @return boolean
     * This function updates announcement
     */
    public function update($data, $id)
    {
        $data['showname']    = isset($data['showname']) ? 1 : 0;
        $data['showtostaff'] = isset($data['showtostaff']) ? 1 : 0;
        $data['showtousers'] = isset($data['showtousers']) ? 1 : 0;

        $data['message'] = $data['message'];

        $data = hooks()->apply_filters('before_announcement_updated', $data, $id);

        $this->db->where('announcementid', $id);
        $this->db->update(db_prefix() . 'announcements', $data);
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('announcement_updated', $id);

            log_activity('Announcement Updated [' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  integer
     * @return boolean
     * Delete Announcement
     * All Dimissed announcements from database will be cleaned
     */
    public function delete($id)
    {
        hooks()->do_action('before_delete_announcement', $id);

        $this->db->where('announcementid', $id);
        $this->db->delete(db_prefix() . 'announcements');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('announcementid', $id);
            $this->db->delete(db_prefix() . 'dismissed_announcements');

            hooks()->do_action('announcement_deleted', $id);

            log_activity('Announcement Deleted [' . $id . ']');

            return true;
        }

        return false;
    }

    public function set_announcements_as_read_except_last_one($user_id, $staff = false)
    {
        $lastAnnouncement = $this->db->query('SELECT announcementid FROM ' . db_prefix() . 'announcements WHERE ' . (!$staff ? 'showtousers' : 'showtostaff') . ' = 1 AND announcementid = (SELECT MAX(announcementid) FROM ' . db_prefix() . 'announcements)')->row();
        if ($lastAnnouncement) {
            // Get all announcements and set it to read.
            $this->db->select('announcementid')
                ->from(db_prefix() . 'announcements')
                ->where((!$staff ? 'showtousers' : 'showtostaff'), 1)
                ->where('announcementid !=', $lastAnnouncement->announcementid);

            $announcements = $this->db->get()->result_array();
            foreach ($announcements as $announcement) {
                $this->db->insert(db_prefix() . 'dismissed_announcements', [
                        'announcementid' => $announcement['announcementid'],
                        'staff'          => (bool) $staff,
                        'userid'         => $user_id,
                    ]);
            }
        }
    }

    private function _annoucements_query()
    {
        if (is_client_logged_in()) {
            $this->db->where('showtousers', 1);
        } elseif (is_staff_logged_in()) {
            $this->db->where('showtostaff', 1);
        }
        $this->db->order_by('dateadded', 'desc');
    }
}
