<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Knowledge_base_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get article by id
     * @param  string $id   article ID
     * @param  string $slug if search by slug
     * @return mixed       if ID or slug passed return object else array
     */
    public function get($id = '', $slug = '')
    {
        $this->db->select('slug,articleid, articlegroup, subject,' . db_prefix() . 'knowledge_base.description,' . db_prefix() . 'knowledge_base.active as active_article,' . db_prefix() . 'knowledge_base_groups.active as active_group,name as group_name,staff_article');
        $this->db->from(db_prefix() . 'knowledge_base');
        $this->db->join(db_prefix() . 'knowledge_base_groups', db_prefix() . 'knowledge_base_groups.groupid = ' . db_prefix() . 'knowledge_base.articlegroup', 'left');
        $this->db->order_by('article_order', 'asc');
        if (is_numeric($id)) {
            $this->db->where('articleid', $id);
        }
        if ($slug != '') {
            $this->db->where('slug', $slug);
        }
        if ($this->input->get('groupid')) {
            $this->db->where('articlegroup', $this->input->get('groupid'));
        }
        if (is_numeric($id) || $slug != '') {
            return $this->db->get()->row();
        }

        return $this->db->get()->result_array();
    }

    /**
     * Get related artices based on article id
     * @param  mixed $current_id current article id
     * @return array
     */
    public function get_related_articles($current_id, $customers = true)
    {
        $total_related_articles = hooks()->apply_filters('total_related_articles', 5);

        $this->db->select('articlegroup');
        $this->db->where('articleid', $current_id);
        $article = $this->db->get(db_prefix() . 'knowledge_base')->row();

        $this->db->where('articlegroup', $article->articlegroup);
        $this->db->where('articleid !=', $current_id);
        $this->db->where('active', 1);
        if ($customers == true) {
            $this->db->where('staff_article', 0);
        } else {
            $this->db->where('staff_article', 1);
        }
        $this->db->limit($total_related_articles);

        return $this->db->get(db_prefix() . 'knowledge_base')->result_array();
    }

    /**
     * Add new article
     * @param array $data article data
     */
    public function add_article($data)
    {
        if (isset($data['disabled'])) {
            $data['active'] = 0;
            unset($data['disabled']);
        } else {
            $data['active'] = 1;
        }
        if (isset($data['staff_article'])) {
            $data['staff_article'] = 1;
        } else {
            $data['staff_article'] = 0;
        }
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['slug']        = slug_it($data['subject']);
        $this->db->like('slug', $data['slug']);
        $slug_total = $this->db->count_all_results(db_prefix() . 'knowledge_base');
        if ($slug_total > 0) {
            $data['slug'] .= '-' . ($slug_total + 1);
        }

        $data = hooks()->apply_filters('before_add_kb_article', $data);

        $this->db->insert(db_prefix() . 'knowledge_base', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Article Added [ArticleID: ' . $insert_id . ' GroupID: ' . $data['articlegroup'] . ']');
        }

        return $insert_id;
    }

    /**
     * Update article
     * @param  array $data article data
     * @param  mixed $id   articleid
     * @return boolean
     */
    public function update_article($data, $id)
    {
        if (isset($data['disabled'])) {
            $data['active'] = 0;
            unset($data['disabled']);
        } else {
            $data['active'] = 1;
        }

        if (isset($data['staff_article'])) {
            $data['staff_article'] = 1;
        } else {
            $data['staff_article'] = 0;
        }

        $this->db->where('articleid', $id);
        $this->db->update(db_prefix() . 'knowledge_base', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Article Updated [ArticleID: ' . $id . ']');

            return true;
        }

        return false;
    }

    public function update_kan_ban($data)
    {
        $affectedRows = 0;
        foreach ($data['order'] as $o) {
            $this->db->where('articleid', $o[0]);
            $this->db->update(db_prefix() . 'knowledge_base', [
                'article_order' => $o[1],
                'articlegroup'  => $data['groupid'],
            ]);
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }
        }
        if ($affectedRows > 0) {
            return true;
        }

        return false;
    }

    /**
     * Change article status
     * @param  mixed $id     article id
     * @param  boolean $status is active or not
     */
    public function change_article_status($id, $status)
    {
        $this->db->where('articleid', $id);
        $this->db->update(db_prefix() . 'knowledge_base', [
            'active' => $status,
        ]);
        log_activity('Article Status Changed [ArticleID: ' . $id . ' Status: ' . $status . ']');
    }

    public function update_groups_order()
    {
        $data = $this->input->post();
        foreach ($data['order'] as $group) {
            $this->db->where('groupid', $group[0]);
            $this->db->update(db_prefix() . 'knowledge_base_groups', [
                'group_order' => $group[1],
            ]);
        }
    }

    /**
     * Delete article from database and all article connections
     * @param  mixed $id article ID
     * @return boolean
     */
    public function delete_article($id)
    {
        $this->db->where('articleid', $id);
        $this->db->delete(db_prefix() . 'knowledge_base');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('articleid', $id);
            $this->db->delete(db_prefix() . 'knowedge_base_article_feedback');

            $this->db->where('rel_type', 'kb_article');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            log_activity('Article Deleted [ArticleID: ' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Get all KGB (Knowledge base groups)
     * @param  mixed $id Optional - KB Group
     * @param  mixed $active Optional - actve groups or not
     * @return mixed      array if not id passed else object
     */
    public function get_kbg($id = '', $active = '')
    {
        if (is_numeric($active)) {
            $this->db->where('active', $active);
        }
        if (is_numeric($id)) {
            $this->db->where('groupid', $id);

            return $this->db->get(db_prefix() . 'knowledge_base_groups')->row();
        }
        $this->db->order_by('group_order', 'asc');

        return $this->db->get(db_prefix() . 'knowledge_base_groups')->result_array();
    }

    /**
     * Add new knowledge base group/folder
     * @param array $data group data
     * @return boolean
     */
    public function add_group($data)
    {
        if (isset($data['disabled'])) {
            $data['active'] = 0;
            unset($data['disabled']);
        } else {
            $data['active'] = 1;
        }

        $data['group_slug'] = slug_it($data['name']);
        $this->db->like('group_slug', $data['group_slug']);
        $slug_total = $this->db->count_all_results(db_prefix() . 'knowledge_base_groups');
        if ($slug_total > 0) {
            $data['group_slug'] .= '-' . ($slug_total + 1);
        }

        $this->db->insert(db_prefix() . 'knowledge_base_groups', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Article Group Added [GroupID: ' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Get knowledge base group by id
     * @param  mixed $id groupid
     * @return object
     */
    public function get_kbg_by_id($id)
    {
        $this->db->where('groupid', $id);

        return $this->db->get(db_prefix() . 'knowledge_base_groups')->row();
    }

    /**
     * Update knowledge base group
     * @param  array $data group data
     * @param  mixed $id   groupid
     * @return boolean
     */
    public function update_group($data, $id)
    {
        if (isset($data['disabled'])) {
            $data['active'] = 0;
            unset($data['disabled']);
        } else {
            $data['active'] = 1;
        }
        $this->db->where('groupid', $id);
        $this->db->update(db_prefix() . 'knowledge_base_groups', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Article Group Updated [GroupID: ' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Change group status
     * @param  mixed $id     groupid id
     * @param  boolean $status is active or not
     */
    public function change_group_status($id, $status)
    {
        $this->db->where('groupid', $id);
        $this->db->update(db_prefix() . 'knowledge_base_groups', [
            'active' => $status,
        ]);
        log_activity('Article Status Changed [GroupID: ' . $id . ' Status: ' . $status . ']');
    }

    public function change_group_color($data)
    {
        $this->db->where('groupid', $data['group_id']);
        $this->db->update(db_prefix() . 'knowledge_base_groups', [
            'color' => $data['color'],
        ]);
    }

    /**
     * Delete knowledge base article
     * @param  mixed $id groupid
     * @return boolean
     */
    public function delete_group($id)
    {
        $current = $this->get_kbg_by_id($id);
        // Check if group already is using
        if (is_reference_in_table('articlegroup', db_prefix() . 'knowledge_base', $id)) {
            return [
                'referenced' => true,
            ];
        }
        $this->db->where('groupid', $id);
        $this->db->delete(db_prefix() . 'knowledge_base_groups');
        if ($this->db->affected_rows() > 0) {
            log_activity('Knowledge Base Group Deleted');

            return true;
        }

        return false;
    }

    /**
     * Add new article vote / Called from client area
     * @param mixed $articleid article id
     * @param boolean $bool
     */
    public function add_article_answer($articleid, $bool)
    {
        $bool = (bool) $bool;

        $ip = $this->input->ip_address();

        $this->db->where('ip', $ip)->where('articleid', $articleid)->order_by('date', 'desc')->limit(1);
        $answer = $this->db->get(db_prefix() . 'knowedge_base_article_feedback')->row();

        if ($answer) {
            $last_answer    = strtotime($answer->date);
            $minus_24_hours = strtotime('-24 hours');
            if ($last_answer >= $minus_24_hours) {
                return [
                    'success' => false,
                    'message' => _l('clients_article_only_1_vote_today'),
                ];
            }
        }
        $this->db->insert(db_prefix() . 'knowedge_base_article_feedback', [
            'answer'    => $bool,
            'ip'        => $ip,
            'articleid' => $articleid,
            'date'      => date('Y-m-d H:i:s'),
        ]);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return [
                'success' => true,
                'message' => _l('clients_article_voted_thanks_for_feedback'),
            ];
        }

        return [
            'success' => false,
        ];
    }
}
