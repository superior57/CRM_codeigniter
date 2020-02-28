<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/mails/traits/TicketTemplate.php');

class Ticket_new_reply_to_staff extends App_mail_template
{
    use TicketTemplate;

    protected $for = 'staff';

    protected $ticket;

    protected $staff;

    protected $ticketid;

    protected $ticket_attachments;

    public $slug = 'ticket-reply-to-admin';

    public $rel_type = 'ticket';

    public function __construct($ticket, $staff, $ticket_attachments)
    {
        parent::__construct();

        $this->ticket             = $ticket;
        $this->staff              = $staff;
        $this->ticketid           = $ticket->ticketid;
        $this->ticket_attachments = $ticket_attachments;
    }

    public function build()
    {

        $this->add_ticket_attachments();

        $this->to($this->staff['email'])
        ->set_rel_id($this->ticket->ticketid)
        ->set_staff_id($this->staff['staffid'])
        ->set_merge_fields('client_merge_fields', $this->ticket->userid, $this->ticket->contactid)
        ->set_merge_fields('ticket_merge_fields', $this->slug, $this->ticket->ticketid);
    }
}
