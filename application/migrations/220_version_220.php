<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_220 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        add_option('google_client_id', '');
        add_option('enable_google_picker', 0);
        add_option('show_ticket_reminders_on_calendar', 1);

        $this->db->where('date', '0000-00-00');
        $this->db->update(db_prefix().'subscriptions', ['date' => null]);

        $this->db->query('ALTER TABLE `tblsubscriptions` ADD `description_in_item` BOOLEAN NOT NULL DEFAULT FALSE AFTER `description`;');

        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
            ('subscriptions', 'customer-subscribed-to-staff', 'english', 'Customer Subscribed to a Subscription (Sent to administrators and subscription creator)', 'Customer Subscribed to a Subscription', 'The customer <strong>{client_company}</strong> subscribed to a subscription with name&nbsp;<strong>{subscription_name}</strong><br /><br /><strong>ID</strong>:&nbsp;{subscription_id}<br /><strong>Subscription name</strong>:&nbsp;{subscription_name}<br /><strong>Subscription description</strong>:&nbsp;{subscription_description}<br /><br />You can view the subscription by clicking <a href=\"{subscription_link}\">here</a><br />\r\n<div style=\"text-align: center;\"><span style=\"font-size: 10pt;\">&nbsp;</span></div>\r\nBest Regards,<br />{email_signature}<br /><br /><span style=\"font-size: 10pt;\"><span style=\"color: #999999;\">You are receiving this email because you are either administrator or you are creator of the subscription.</span></span>', '{companyname} | CRM', '', 0, 1, 0);");

        $this->db->query('ALTER TABLE `tblcontacts` ADD `email_verified_at` DATETIME NULL AFTER `new_pass_key_requested`, ADD `email_verification_key` VARCHAR(32) NULL AFTER `email_verified_at`;');

        $this->db->query('ALTER TABLE `tblcontacts` ADD `email_verification_sent_at` DATETIME NULL AFTER `email_verification_key`;');

        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('client', 'contact-verification-email', 'english', 'Email Verification (Sent to Contact After Registration)', 'Verify Email Address', '<p>Hello&nbsp;{contact_firstname}<br /><br />Please click the button below to verify your email address.<br /><br /><a href=\"{email_verification_url}\">Verify Email Address</a><br /><br />If you did not create an account, no further action is required</p>\r\n<p><br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0);");

        $this->db->query('UPDATE tblcontacts SET email_verified_at = datecreated;');

        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('client', 'new-customer-profile-file-uploaded-to-staff', 'english', 'New Customer Profile File(s) Uploaded (Sent to Staff)', 'Customer Uploaded New File(s) in Profile', 'Hi!<br /><br />New file(s) is uploaded into the customer ({client_company}) profile by&nbsp;{contact_firstname}<br /><br />You can check the uploaded files into the admin area by clicking <a href=\"{customer_profile_files_admin_link}\">here</a> or at the following link:&nbsp;{customer_profile_files_admin_link}<br /><br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0);");

        if (active_clients_theme() != 'perfex') {
            xcopy(VIEWPATH . 'themes/perfex/views/verification_required.php', VIEWPATH . 'themes/' . active_clients_theme() . '/views/verification_required.php');
        }

        update_option('update_info_message', '<div class="col-md-12">
        <div class="alert alert-success bold">
        <h4 class="bold">Hi! Thanks for updating Perfex CRM - You are using version 2.2.0</h4>
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
