<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_234 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
    	return($data) ;
        $eventTemplateMessage = 'Hi {staff_firstname}! <br /><br />This is a reminder for event <a href=\"{event_link}\">{event_title}</a> scheduled at {event_start_date}. <br /><br />Regards.';
        create_email_template('Upcoming Event - {event_title}', $eventTemplateMessage, 'staff', 'Event Notification (Calendar)', 'event-notification-to-staff');
    }
}
