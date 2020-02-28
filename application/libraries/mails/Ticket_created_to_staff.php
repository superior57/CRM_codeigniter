<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/mails/traits/TicketTemplate.php');

class Ticket_created_to_staff extends App_mail_template
{
    use TicketTemplate;

    protected $for = 'staff';

    protected $ticketid;

    protected $client_id;

    protected $contact_id;

    protected $staff;

    protected $ticket_attachments;

    public $slug = 'new-ticket-created-staff';

    public $rel_type = 'ticket';

    public function __construct($ticketid, $client_id, $contact_id, $staff, $ticket_attachments)
    {
        parent::__construct();

        $this->ticketid   = $ticketid;
        $this->client_id  = $client_id;
        $this->contact_id = $contact_id;
        $this->staff      = $staff;

        $this->ticket_attachments = $ticket_attachments;
    }

    public function build()
    {

        $this->add_ticket_attachments();

        $this->to($this->staff['email'])
        ->set_rel_id($this->ticketid)
        ->set_staff_id($this->staff['staffid'])
        ->set_merge_fields('client_merge_fields', $this->client_id, $this->contact_id)
        ->set_merge_fields('ticket_merge_fields', $this->slug, $this->ticketid);
    }
}
