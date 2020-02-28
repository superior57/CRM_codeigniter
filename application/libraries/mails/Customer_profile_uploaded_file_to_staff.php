<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Customer_profile_uploaded_file_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $staff_email;

    protected $staffid;

    public $slug = 'new-customer-profile-file-uploaded-to-staff';

    public $rel_type = 'staff';

    public function __construct($staff_email, $staffid)
    {
        parent::__construct();

        $this->staff_email = $staff_email;
        $this->staffid    = $staffid;
    }

    public function build()
    {
        // Merge fields are set in clients_model.php in method send_notification_customer_profile_file_uploaded_to_responsible_staff
        $this->to($this->staff_email)
        ->set_rel_id($this->staffid);
    }
}
