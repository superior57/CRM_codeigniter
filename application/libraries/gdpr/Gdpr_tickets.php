<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_tickets
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($contact_id)
    {
        $this->ci->load->model('tickets_model');

        $this->ci->db->where('contactid', $contact_id);
        $tickets = $this->ci->db->get(db_prefix().'tickets')->result_array();

        $this->ci->db->where('show_on_client_portal', 1);
        $this->ci->db->where('fieldto', 'tickets');
        $this->ci->db->order_by('field_order', 'asc');
        $custom_fields = $this->ci->db->get(db_prefix().'customfields')->result_array();

        foreach ($tickets as $ticketKey => $ticket) {
            $this->ci->db->where('ticketid', $ticket['ticketid']);
            $tickets[$ticketKey]['replies'] = $this->ci->db->get(db_prefix().'ticket_replies')->result_array();

            $this->ci->db->where('departmentid', $ticket['department']);
            $dept = $this->ci->db->get(db_prefix().'departments')->row();

            if ($dept) {
                $tickets[$ticketKey]['department_name'] = $dept->name;
            }

            $this->ci->db->where('priorityid', $ticket['priority']);
            $priority = $this->ci->db->get(db_prefix().'tickets_priorities')->row();
            if ($priority) {
                $tickets[$ticketKey]['priority_name'] = $priority->name;
            }

            $this->ci->db->where('ticketstatusid', $ticket['status']);
            $status = $this->ci->db->get(db_prefix().'tickets_status')->row();
            if ($status) {
                $tickets[$ticketKey]['status_name'] = $status->name;
            }

            $this->ci->db->where('serviceid', $ticket['service']);
            $service = $this->ci->db->get(db_prefix().'services')->row();
            if ($service) {
                $tickets[$ticketKey]['service_name'] = $service->name;
            }

            $tickets[$ticketKey]['additional_fields'] = [];
            foreach ($custom_fields as $cf) {
                $tickets[$ticketKey]['additional_fields'][] = [
                    'name'  => $cf['name'],
                    'value' => get_custom_field_value($ticket['ticketid'], $cf['id'], 'tickets'),
                ];
            }
        }

        return $tickets;
    }
}
