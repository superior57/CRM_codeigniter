<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_210 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        add_option('receive_notification_on_new_ticket_replies', 1);

        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('contract', 'contract-signed-to-staff', 'english', 'Contract Signed (Sent to Staff)', 'Customer Signed a Contract', 'Hello&nbsp;{staff_firstname}&nbsp;{staff_lastname}<br /><br />A contract with subject&nbsp;<strong>{contract_subject} </strong>has been successfully signed by the customer.<br /><br />You can view and reply to the comment on the following link: <a href=\"{contract_link}\">{contract_subject}</a>&nbsp;or from the admin area.<br /><br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0);");

        $this->db->query('ALTER TABLE `tblcustomersgroups` CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;');
        $this->db->query('ALTER TABLE `tblcustomersgroups` ADD INDEX(`name`);');
        $this->db->query('ALTER TABLE `tblcontacts` ADD INDEX(`firstname`);');
        $this->db->query('ALTER TABLE `tblcontacts` ADD INDEX(`lastname`);');

        $this->db->query("ALTER TABLE `tbltaskchecklists` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

        update_option('update_info_message', '<div class="col-md-12">
        <div class="alert alert-success bold">
        <h4 class="bold">Hi! Thanks for updating Perfex CRM - You are using version 2.1.0</h4>
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
