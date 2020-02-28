<?php

defined('BASEPATH') or exit('No direct script access allowed');

trait TicketTemplate
{
    protected function _subject()
    {
        /**
         * IMPORTANT
         * Do not change/remove this line, this is used for email piping so the software can recognize the ticket id.
         */
        if (substr($this->template->subject, 0, 10) != '[Ticket ID') {
            return '[Ticket ID: ' . $this->ticketid . '] ' . $this->template->subject;
        }

        return parent::_subject();
    }

    protected function _reply_to()
    {
        $default = parent::_reply_to();

        // Should be loaded?
        if (!class_exists('tickets_model')) {
            $this->ci->load->model('tickets_model');
        }

        $ticket = $this->get_ticket_for_mail();

        if (!empty($ticket->department_email) && valid_email($ticket->department_email)) {
            return $ticket->department_email;
        }

        return $default;
    }

    protected function _from()
    {
        $default = parent::_from();

        $ticket = $this->get_ticket_for_mail();

        if (!empty($ticket->department_email)
            && $ticket->dept_email_from_header == 1
            && valid_email($ticket->department_email)) {
            return [
                'fromname'  => $default['fromname'],
                'fromemail' => $ticket->department_email,
            ];
        }

        return $default;
    }

    private function get_ticket_for_mail()
    {
        $this->ci->db->select(db_prefix() . 'departments.email as department_email, email_from_header as dept_email_from_header')
            ->where('ticketid', $this->ticketid)
            ->join(db_prefix() . 'departments', db_prefix() . 'departments.departmentid=' . db_prefix() . 'tickets.department', 'left');

        return $this->ci->db->get(db_prefix() . 'tickets')->row();
    }

    private function add_ticket_attachments()
    {
        foreach ($this->ticket_attachments as $attachment) {
            $this->add_attachment([
                    'attachment' => get_upload_path_by_type('ticket') . $this->ticketid . '/' . $attachment['file_name'],
                    'filename'   => $attachment['file_name'],
                    'type'       => $attachment['filetype'],
                    'read'       => true,
                ]);
        }
    }
}
