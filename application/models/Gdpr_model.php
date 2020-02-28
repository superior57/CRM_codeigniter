<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function add_request($data)
    {
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }
        $data['request_date'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'gdpr_requests', $data);

        return $this->db->insert_id();
    }

    public function add_removal_request($data)
    {
        $data['request_type'] = 'account_removal';

        return $this->add_request($data);
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'gdpr_requests', $data);

        return $this->db->affected_rows() > 0;
    }

    public function get_removal_requests()
    {
        $this->db->where('request_type', 'account_removal');
        $this->db->order_by('request_date', 'desc');

        return $this->db->get(db_prefix() . 'gdpr_requests')->result_array();
    }

    /**
     * Get consent purposes
     * @param  mixed $user_id contact id or lead id
     * @param  string $for     contact or lead
     * @return array
     */
    public function get_consent_purposes($user_id = null, $for = '')
    {
        $select = '*, (SELECT COUNT(*) FROM ' . db_prefix() . 'consents WHERE purpose_id=' . db_prefix() . 'consent_purposes.id) as total_usage';

        if ($user_id !== null && $for != '') {
            $column    = $for . '_id';
            $commonSQL = 'FROM ' . db_prefix() . 'consents WHERE ' . $column . '=' . $user_id . ' AND purpose_id=' . db_prefix() . 'consent_purposes.id ORDER by date DESC LIMIT 1';

            $select .= ', (SELECT CASE WHEN action="opt-in" THEN 1 ELSE 0 END ' . $commonSQL . ') as consent_given';

            $select .= ', (SELECT CASE WHEN action="opt-out" THEN 1 ELSE 0 END ' . $commonSQL . ') as last_action_is_opt_out';

            $select .= ', (SELECT date ' . $commonSQL . ') as consent_last_updated';

            $select .= ', (SELECT opt_in_purpose_description ' . $commonSQL . ') as opt_in_purpose_description';
        }

        $this->db->select($select);
        $this->db->order_by('name', 'desc');

        $purposes = $this->db->get(db_prefix() . 'consent_purposes')->result_array();

        return $purposes;
    }

    public function get_consent_purpose($id)
    {
        $select = '*, (SELECT COUNT(*) FROM ' . db_prefix() . 'consents WHERE purpose_id=' . db_prefix() . 'consent_purposes.id) as total_usage';

        $this->db->select($select);
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'consent_purposes')->row();
    }

    public function add_consent_purpose($data)
    {
        $data['date_created'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'consent_purposes', $data);

        return $this->db->insert_id();
    }

    public function update_consent_purpose($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'consent_purposes', $data);

        $updated = $this->db->affected_rows() > 0;
        if ($updated) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'consent_purposes', ['last_updated' => date('Y-m-d H:i:s')]);
        }

        return $updated;
    }

    public function delete_consent_purpose($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'consent_purposes');

        $this->db->where('purpose_id', $id);
        $this->db->delete(db_prefix() . 'consents');

        return $this->db->affected_rows() > 0;
    }

    public function add_consent($data)
    {
        $data['date'] = isset($data['date']) ? $data['date'] : date('Y-m-d H:i:s');
        $data['ip']   = isset($data['ip']) ? $data['ip'] : $this->input->ip_address();
        $this->db->insert(db_prefix() . 'consents', $data);

        return $this->db->insert_id();
    }

    public function get_consents($where = [])
    {
        $this->db->select(db_prefix() . 'consents.*, ' . db_prefix() . 'consent_purposes.name as purpose_name');
        $this->db->where($where);
        $this->db->join(db_prefix() . 'consent_purposes', db_prefix() . 'consent_purposes.id=' . db_prefix() . 'consents.purpose_id');
        $this->db->order_by('date', 'desc');
        $consents = $this->db->get(db_prefix() . 'consents')->result_array();

        return $consents;
    }
}
