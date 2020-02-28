<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/mails/traits/TicketTemplate.php');

class Ticket_new_reply_to_customer extends App_mail_template
{
    use TicketTemplate;

    protected $for = 'customer';

    protected $ticket;

    protected $email;

    protected $ticketid;

    protected $ticket_attachments;

    public $slug = 'ticket-reply';

    public $rel_type = 'ticket';

    public function __construct($ticket, $email, $ticket_attachments, $cc)
    {
        parent::__construct();

        $this->ticket             = $ticket;
        $this->email              = $email;
        $this->ticketid           = $ticket->ticketid;
        $this->ticket_attachments = $ticket_attachments;
        $this->cc                 = $cc;
    }

    public function build()
    {

        $this->add_ticket_attachments();

        $this->to($this->email)
        ->set_rel_id($this->ticket->ticketid)
        ->set_merge_fields('client_merge_fields', $this->ticket->userid, $this->ticket->contactid)
        ->set_merge_fields('ticket_merge_fields', $this->slug, $this->ticket->ticketid);
    }
}
