<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Check_emails extends CI_Controller
{
    public function track($uid)
    {
        $this->db->where('opened', 0);
        $this->db->where('uid', $uid);
        $tracking = $this->db->get(db_prefix().'tracked_mails')->row();

        // Perhaps already tracked?
        if ($tracking) {
            $this->db->where('id', $tracking->id);
            $this->db->update(db_prefix().'tracked_mails', [
                'date_opened' => date('Y-m-d H:i:s'),
                'opened'      => 1,
            ]);
        }
    }
}
