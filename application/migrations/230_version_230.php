<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_230 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
        app_init_customer_profile_tabs();
    }

    public function up()
    {
        update_option('setup_menu_active', '[]');
        update_option('aside_menu_active', '[]');

        $this->db->where('name', 'aside_menu_inactive');
        $this->db->or_where('name', 'setup_menu_inactive');
        $this->db->delete('tbloptions');

        if (table_exists('tblticketsspamcontrol')) {
            $this->db->query('RENAME TABLE `tblticketsspamcontrol` TO `tblspamfilters`;');
        }

        if (!$this->db->field_exists('rel_type', 'tblspamfilters')) {
            $this->db->query('ALTER TABLE `tblspamfilters` ADD `rel_type` VARCHAR(10) NOT NULL AFTER `type`;');
            $this->db->update('tblspamfilters', ['rel_type' => 'tickets']);
        }

        if (!table_exists('tblmodules')) {
            $this->db->query('CREATE TABLE `tblmodules` (
                          `id` int(11) NOT NULL,
                          `module_name` varchar(55) NOT NULL,
                          `installed_version` varchar(11) NOT NULL,
                          `active` tinyint(1) NOT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

            $this->db->query('ALTER TABLE `tblmodules`  ADD PRIMARY KEY (`id`);');
            $this->db->query('ALTER TABLE `tblmodules`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');
        }

        if (!table_exists('tblcreditnoterefunds')) {
            $this->db->query('CREATE TABLE `tblcreditnoterefunds` (
                          `id` int(11) NOT NULL,
                          `credit_note_id` int(11) NOT NULL,
                          `staff_id` int(11) NOT NULL,
                          `refunded_on` date NOT NULL,
                          `payment_mode` varchar(40) NOT NULL,
                          `note` text,
                          `amount` decimal(15,2) NOT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

            $this->db->query('ALTER TABLE `tblcreditnoterefunds` ADD PRIMARY KEY (`id`);');
            $this->db->query('ALTER TABLE `tblcreditnoterefunds` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');
        }

        $this->db->query('ALTER TABLE `tblcontacts` CHANGE `lastname` `lastname` VARCHAR(191) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;');
        $this->db->query('ALTER TABLE `tblcontacts` CHANGE `firstname` `firstname` VARCHAR(191) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;');

        $this->db->query('ALTER TABLE `tblcontacts` ADD INDEX(`email`);');

        $this->db->query('ALTER TABLE `tblcustomfields` CHANGE `fieldto` `fieldto` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;');
        $this->db->query('ALTER TABLE `tblcustomfieldsvalues` CHANGE `fieldto` `fieldto` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;');

        $this->db->query("ALTER TABLE `tblleads` CHANGE `assigned` `assigned` INT(11) NOT NULL DEFAULT '0';");

        $visible_customer_profile_tabs = get_option('visible_customer_profile_tabs');
        if ($visible_customer_profile_tabs != 'all' && get_option('230_cp_tabs_processed') === '') {
            $visible_customer_profile_tabs = unserialize($visible_customer_profile_tabs);

            $tabs = get_customer_profile_tabs();
            $opt  = [];

            foreach ($tabs as $tabKey => $tab) {
                $opt[$tabKey] = in_array($tabKey, $visible_customer_profile_tabs);
            }

            update_option('visible_customer_profile_tabs', serialize($opt));
            add_option('230_cp_tabs_processed', 'true', 0);
        }

        @$this->db->query('ALTER TABLE `tblprojects` CHANGE `name` `name` VARCHAR(191) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;');

        $databaseName = APP_DB_NAME;
        $myISAMTables = $this->db->query("SELECT TABLE_NAME,
                             ENGINE
                            FROM information_schema.TABLES
                            WHERE TABLE_SCHEMA = '$databaseName' and ENGINE = 'myISAM'")->result_array();

        foreach ($myISAMTables as $table) {
            $tableName = $table['TABLE_NAME'];
            $this->db->query("ALTER TABLE $tableName ENGINE=InnoDB;");
        }

        if (file_exists(VIEWPATH . 'themes/perfex/scripts.php')) {
            @unlink(VIEWPATH . 'themes/perfex/scripts.php');
        }

        $themes = get_all_client_themes();

        foreach ($themes as $theme) {
            if ($theme != 'perfex') {
                if (!file_exists(VIEWPATH . 'themes/' . $theme . '/functions.php')) {
                    copy(VIEWPATH . 'themes/perfex/functions.php', VIEWPATH . 'themes/' . $theme . '/functions.php');
                }
            }
        }

        $this->db->where('country_id', 130);
        $this->db->update('tblcountries', [
            'short_name' => 'North Macedonia',
            'long_name'  => 'Republic of North Macedonia',
        ]);

        // Because the modules won't be loaded
        $this->app_modules->initialize();

        foreach (uninstallable_modules() as $module) {
            $this->app_modules->activate($module);
        }

        // Maybe is misssed from 201_version_201? Not sure why
        if (total_rows('tblemailtemplates', ['slug' => 'client-registration-confirmed']) == 0) {
            $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('client', 'client-registration-confirmed', 'english', 'Customer Registration Confirmed', 'Your registration is confirmed', '<p>Dear {contact_firstname} {contact_lastname}<br /><br />We just wanted to let you know that your registration at&nbsp;{companyname} is successfully confirmed and your account is now active.<br /><br />You can login at&nbsp;<a href=\"{crm_url}\">{crm_url}</a> with the email and password you provided during registration.<br /><br />Please contact us if you need any help.<br /><br />Kind Regards, <br />{email_signature}</p>\r\n<p><br />(This is an automated email, so please don\'t reply to this email address)</p>', '{companyname} | CRM', '', 0, 1, 0);");
        }

        // Moved to third_party
        if (file_exists(APPPATH . 'helpers/simple_html_dom_helper.php')) {
            @unlink(APPPATH . 'helpers/simple_html_dom_helper.php');
        }
    }
}
