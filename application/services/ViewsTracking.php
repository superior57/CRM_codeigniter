<?php

namespace app\services;

defined('BASEPATH') or exit('No direct script access allowed');

class ViewsTracking
{
    public static function get($rel_type, $rel_id)
    {
        $CI = & get_instance();
        $CI->db->where('rel_id', $rel_id);
        $CI->db->where('rel_type', $rel_type);
        $CI->db->order_by('date', 'DESC');

        return $CI->db->get(db_prefix() . 'views_tracking')->result_array();
    }

    public static function create($rel_type, $rel_id)
    {
        $CI = & get_instance();
        if (!is_staff_logged_in()) {
            $CI->db->where('rel_id', $rel_id);
            $CI->db->where('rel_type', $rel_type);
            $CI->db->order_by('id', 'DESC');
            $CI->db->limit(1);
            $row = $CI->db->get(db_prefix() . 'views_tracking')->row();
            if ($row) {
                $dateFromDatabase = strtotime($row->date);
                $date1HourAgo     = strtotime('-1 hours');
                if ($dateFromDatabase >= $date1HourAgo) {
                    return false;
                }
            }
        } else {
            // Staff logged in, nothing to do here
            return false;
        }

        hooks()->do_action('before_insert_views_tracking', [
        'rel_id'   => $rel_id,
        'rel_type' => $rel_type,
        ]);

        $notifiedUsers     = [];
        $members           = [];
        $notification_data = [];
        if ($rel_type == 'invoice' || $rel_type == 'proposal' || $rel_type == 'estimate') {
            $responsible_column = 'sale_agent';

            if ($rel_type == 'invoice') {
                $table                    = db_prefix() . 'invoices';
                $notification_link        = 'invoices/list_invoices/' . $rel_id;
                $notification_description = 'not_customer_viewed_invoice';
                array_push($notification_data, format_invoice_number($rel_id));
                $canViewFunction = 'user_can_view_invoice';
            } elseif ($rel_type == 'estimate') {
                $table                    = db_prefix() . 'estimates';
                $notification_link        = 'estimates/list_estimates/' . $rel_id;
                $notification_description = 'not_customer_viewed_estimate';
                array_push($notification_data, format_estimate_number($rel_id));
                $canViewFunction = 'user_can_view_estimate';
            } else {
                $responsible_column       = 'assigned';
                $table                    = db_prefix() . 'proposals';
                $notification_description = 'not_customer_viewed_proposal';
                $notification_link        = 'proposals/list_proposals/' . $rel_id;
                array_push($notification_data, format_proposal_number($rel_id));
                $canViewFunction = 'user_can_view_proposal';
            }

            $notification_data = serialize($notification_data);

            $CI->db->select('addedfrom,' . $responsible_column)
                        ->where('id', $rel_id);

            $rel = $CI->db->get($table)->row();

            $CI->db->select('staffid')
                ->where('staffid', $rel->addedfrom)
                ->or_where('staffid', $rel->{$responsible_column});

            $members = $CI->db->get(db_prefix() . 'staff')->result_array();
        }

        $CI->db->insert(db_prefix() . 'views_tracking', [
                    'rel_id'   => $rel_id,
                    'rel_type' => $rel_type,
                    'date'     => date('Y-m-d H:i:s'),
                    'view_ip'  => $CI->input->ip_address(),
                    ]);

        $view_id = $CI->db->insert_id();
        if ($view_id) {
            foreach ($members as $member) {
                // E.q. had permissions create not don't have, so we must re-check this
                if(!$canViewFunction($rel_id, $member['staffid'])) {
                    continue;
                }
                $notification = [
                    'fromcompany'     => true,
                    'touserid'        => $member['staffid'],
                    'description'     => $notification_description,
                    'link'            => $notification_link,
                    'additional_data' => $notification_data,
                    ];
                if (is_client_logged_in()) {
                    unset($notification['fromcompany']);
                }
                $notified = add_notification($notification);
                if ($notified) {
                    array_push($notifiedUsers, $member['staffid']);
                }
            }
            pusher_trigger_notification($notifiedUsers);
        }
    }
}
