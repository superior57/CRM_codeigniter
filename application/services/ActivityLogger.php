<?php

namespace app\services;

defined('BASEPATH') or exit('No direct script access allowed');

class ActivityLogger
{
    public static function log($description, $staffid = null)
    {
        $CI  = & get_instance();
        $log = [
        'description' => $description,
        'date'        => date('Y-m-d H:i:s'),
        ];
        if (!DEFINED('CRON')) {
            if ($staffid != null && is_numeric($staffid)) {
                $log['staffid'] = get_staff_full_name($staffid);
            } else {
                if (!is_client_logged_in()) {
                    if (is_staff_logged_in()) {
                        $log['staffid'] = get_staff_full_name(get_staff_user_id());
                    } else {
                        $log['staffid'] = null;
                    }
                } else {
                    $log['staffid'] = get_contact_full_name(get_contact_user_id());
                }
            }
        } else {
            // manually invoked cron
            if (is_staff_logged_in()) {
                $log['staffid'] = get_staff_full_name(get_staff_user_id());
            } else {
                $log['staffid'] = '[CRON]';
            }
        }

        $CI->db->insert(db_prefix() . 'activity_log', $log);
    }

    public static function getLast()
    {
        $CI = &get_instance();
        $CI->db->select('id');
        $CI->db->order_by('id', 'desc');
        $CI->db->limit(1);

        return $CI->db->get(db_prefix() . 'activity_log')->row();
    }
}
