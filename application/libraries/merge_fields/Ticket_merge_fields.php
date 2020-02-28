<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Ticket_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
                [
                    'name'      => 'Ticket ID',
                    'key'       => '{ticket_id}',
                    'available' => [
                        'ticket',
                    ],
                ],
                [
                    'name'      => 'Ticket URL',
                    'key'       => '{ticket_url}',
                    'available' => [
                        'ticket',
                    ],
                ],
                [
                    'name'      => 'Department',
                    'key'       => '{ticket_department}',
                    'available' => [
                        'ticket',
                    ],
                ],
                [
                    'name'      => 'Department Email',
                    'key'       => '{ticket_department_email}',
                    'available' => [
                        'ticket',
                    ],
                ],
                [
                    'name'      => 'Date Opened',
                    'key'       => '{ticket_date}',
                    'available' => [
                        'ticket',
                    ],
                ],
                [
                    'name'      => 'Ticket Subject',
                    'key'       => '{ticket_subject}',
                    'available' => [
                        'ticket',
                    ],
                ],
                [
                    'name'      => 'Ticket Message',
                    'key'       => '{ticket_message}',
                    'available' => [
                        'ticket',
                    ],
                ],
                [
                    'name'      => 'Ticket Status',
                    'key'       => '{ticket_status}',
                    'available' => [
                        'ticket',
                    ],
                ],
                [
                    'name'      => 'Ticket Priority',
                    'key'       => '{ticket_priority}',
                    'available' => [
                        'ticket',
                    ],
                ],
                [
                    'name'      => 'Ticket Service',
                    'key'       => '{ticket_service}',
                    'available' => [
                        'ticket',
                    ],
                ],
            ];
    }

    /**
 * Merge fields for tickets
 * @param  string $template  template name, used to identify url
 * @param  mixed $ticket_id ticket id
 * @param  mixed $reply_id  reply id
 * @return array
 */
    public function format($template, $ticket_id, $reply_id = '')
    {
        $fields = [];

        $this->ci->db->where('ticketid', $ticket_id);
        $ticket = $this->ci->db->get(db_prefix().'tickets')->row();

        if (!$ticket) {
            return $fields;
        }

        // Replace contact firstname with the ticket name in case the ticket is not linked to any contact.
        // eq email or form imported.
        if ($ticket->name != null && $ticket->name != '') {
            $fields['{contact_firstname}'] = $ticket->name;
        }

        $fields['{ticket_priority}'] = '';
        $fields['{ticket_service}']  = '';


        $this->ci->db->where('departmentid', $ticket->department);
        $department = $this->ci->db->get(db_prefix().'departments')->row();

        if ($department) {
            $fields['{ticket_department}']       = $department->name;
            $fields['{ticket_department_email}'] = $department->email;
        }

        $languageChanged = false;
        if (!is_client_logged_in()
        && !empty($ticket->userid)
        && isset($GLOBALS['SENDING_EMAIL_TEMPLATE_CLASS'])
        && !$GLOBALS['SENDING_EMAIL_TEMPLATE_CLASS']->get_staff_id() // email to client
    ) {
            load_client_language($ticket->userid);
            $languageChanged = true;
        } else {
            if (isset($GLOBALS['SENDING_EMAIL_TEMPLATE_CLASS'])) {
                $sending_to_staff_id = $GLOBALS['SENDING_EMAIL_TEMPLATE_CLASS']->get_staff_id();
                if ($sending_to_staff_id) {
                    load_admin_language($sending_to_staff_id);
                    $languageChanged = true;
                }
            }
        }

        $fields['{ticket_status}'] = ticket_status_translate($ticket->status);

        $fields['{ticket_priority}'] = ticket_priority_translate($ticket->priority);


        $custom_fields = get_custom_fields('tickets');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($ticket_id, $field['id'], 'tickets');
        }

        if (!is_client_logged_in() && $languageChanged) {
            load_admin_language();
        } elseif (is_client_logged_in() && $languageChanged) {
            load_client_language();
        }

        $this->ci->db->where('serviceid', $ticket->service);
        $service = $this->ci->db->get(db_prefix().'services')->row();
        if ($service) {
            $fields['{ticket_service}'] = $service->name;
        }

        $fields['{ticket_id}'] = $ticket_id;

        $customerTemplates = [
        'new-ticket-opened-admin',
        'ticket-reply',
        'ticket-autoresponse',
        'auto-close-ticket',
    ];

        if (in_array($template, $customerTemplates)) {
            $fields['{ticket_url}'] = site_url('clients/ticket/' . $ticket_id);
        } else {
            $fields['{ticket_url}'] = admin_url('tickets/ticket/' . $ticket_id);
        }

        $reply = false;
        if ($template == 'ticket-reply-to-admin' || $template == 'ticket-reply') {
            $this->ci->db->where('ticketid', $ticket_id);
            $this->ci->db->limit(1);
            $this->ci->db->order_by('date', 'desc');
            $reply                      = $this->ci->db->get(db_prefix().'ticket_replies')->row();
            $fields['{ticket_message}'] = $reply->message;
        } else {
            $fields['{ticket_message}'] = $ticket->message;
        }

        $fields['{ticket_date}']    = _dt($ticket->date);
        $fields['{ticket_subject}'] = $ticket->subject;

        return hooks()->apply_filters('ticket_merge_fields', $fields, [
        'id'       => $ticket_id,
        'reply_id' => $reply_id,
        'template' => $template,
        'ticket'   => $ticket,
        'reply'    => $reply,
     ]);
    }
}
