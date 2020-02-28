<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_201 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        add_option('company_logo_dark', '');
        add_option('customers_register_require_confirmation', '0');
        add_option('allow_non_admin_staff_to_delete_ticket_attachments', '0');
        $this->db->query("ALTER TABLE `tblclients` ADD `registration_confirmed` INT NOT NULL DEFAULT '1' AFTER `stripe_id`;");

        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('client', 'client-registration-confirmed', 'english', 'Customer Registration Confirmed', 'Your registration is confirmed', '<p>Dear {contact_firstname} {contact_lastname}<br /><br />We just wanted to let you know that your registration at&nbsp;{companyname} is successfully confirmed and your account is now active.<br /><br />You can login at&nbsp;<a href=\"{crm_url}\">{crm_url}</a> with the email and password you provided during registration.<br /><br />Please contact us if you need any help.<br /><br />Kind Regards, <br />{email_signature}</p>\r\n<p><br />(This is an automated email, so please don\'t reply to this email address)</p>', '{companyname} | CRM', '', 0, 1, 0);");


        if (!table_exists('tblemailstracking')) {
            $this->db->query("CREATE TABLE `tblemailstracking` (
              `id` int(11) NOT NULL,
              `uid` varchar(32) NOT NULL,
              `rel_id` int(11) NOT NULL,
              `rel_type` varchar(40) NOT NULL,
              `date` datetime NOT NULL,
              `email` varchar(100) NOT NULL,
              `opened` tinyint(1) NOT NULL DEFAULT '0',
              `date_opened` datetime DEFAULT NULL,
              `subject` varchar(300) DEFAULT NULL
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

            $this->db->query('ALTER TABLE `tblemailstracking`
          ADD PRIMARY KEY (`id`);');
            $this->db->query('ALTER TABLE `tblemailstracking`
          MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');
        }

        $tracked_emails = $this->db->get(db_prefix().'emailstracking')->result_array();

        $this->db->empty_table(db_prefix().'emailstracking');
        $this->db->query('ALTER TABLE tblemailstracking AUTO_INCREMENT = 1');

        $this->db->query('ALTER TABLE `tblemailstracking` CHANGE `uid` `uid` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;');

        foreach ($tracked_emails as $tracked_email) {
            $this->db->insert(db_prefix().'emailstracking', [
                'uid'         => $tracked_email['uid'],
                'rel_id'      => $tracked_email['rel_id'],
                'rel_type'    => $tracked_email['rel_type'],
                'date'        => $tracked_email['date'],
                'email'       => $tracked_email['email'],
                'opened'      => $tracked_email['opened'],
                'date_opened' => $tracked_email['date_opened'],
                'subject'     => $tracked_email['subject'],
            ]);
        }

        update_option('update_info_message', '<div class="col-md-12">
        <div class="alert alert-success bold">
        <h4 class="bold">Hi! Thanks for updating Perfex CRM - You are using version 2.0.1</h4>
        <p>
        This window will reload automaticaly in 10 seconds and will try to clear your browser/cloudflare cache, however its recomended to clear your browser cache manually.
        </p>
        </div>
        </div>
        <script>
        setTimeout(function(){
            window.location.reload();
        },10000);
        </script>');
        if (file_exists(FCPATH . 'pipe.php')) {
            @chmod(FCPATH . 'pipe.php', 0755);
        }
    }
}
