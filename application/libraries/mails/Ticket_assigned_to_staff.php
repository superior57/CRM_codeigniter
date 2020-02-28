<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/mails/traits/TicketTemplate.php');

class Ticket_assigned_to_staff extends App_mail_template
{
    use TicketTemplate;

    protected $for = 'staff';

    protected $staff_email;

    protected $staffid;

    protected $ticketid;

    protected $client_id;

    protected $contact_id;

    public $slug = 'ticket-assigned-to-admin';

    public $rel_type = 'ticket';

    public function __construct($staff_email, $staffid, $ticketid, $client_id, $contact_id)
    {
        parent::__construct();

        $this->staff_email = $staff_email;
        $this->staffid     = $staffid;
        $this->ticketid    = $ticketid;
        $this->client_id   = $client_id;
        $this->contact_id  = $contact_id;
    }

    public function build()
    {

        $this->to($this->staff_email)
        ->set_rel_id($this->ticketid)
        ->set_staff_id($this->staffid)
        ->set_merge_fields('client_merge_fields', $this->client_id, $this->contact_id)
        ->set_merge_fields('ticket_merge_fields', $this->slug, $this->ticketid);
    }
}
