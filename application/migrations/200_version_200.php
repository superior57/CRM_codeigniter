<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_200 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        $this->db->where('name', 'clients_default_theme');
        $theme = $this->db->get(db_prefix().'options')->row()->value;

        if ($theme != 'perfex') {
            $defPath         = APPPATH . 'views/themes/perfex/';
            $activeThemePath = APPPATH . 'views/themes/' . $theme . '/';
            if (is_dir($defPath)) {
                @copy($defPath . 'views/contracthtml.php', $activeThemePath . 'views/contracthtml.php');
                @copy($defPath . 'views/consent.php', $activeThemePath . 'views/consent.php');
                @copy($defPath . 'views/credit_card.php', $activeThemePath . 'views/credit_card.php');
                @copy($defPath . 'views/gdpr.php', $activeThemePath . 'views/gdpr.php');
                @copy($defPath . 'views/privacy_policy.php', $activeThemePath . 'views/privacy_policy.php');
                @copy($defPath . 'views/subscriptionhtml.php', $activeThemePath . 'views/subscriptionhtml.php');
                @copy($defPath . 'views/subscriptions.php', $activeThemePath . 'views/subscriptions.php');
                @copy($defPath . 'views/terms_and_conditions.php', $activeThemePath . 'views/terms_and_conditions.php');

                @copy($defPath . 'template_parts/projects/project_summary.php', $activeThemePath . 'template_parts/projects/project_summary.php');

                if(!file_exists($activeThemePath.'template_parts/identity_confirmation_form.php')) {
                     @copy($defPath . 'template_parts/identity_confirmation_form.php', $activeThemePath . 'template_parts/identity_confirmation_form.php');
                }

                @mkdir($activeThemePath . 'template_parts/knowledge_base', 0755);
                if (is_dir($activeThemePath . 'template_parts/knowledge_base')) {
                    @copy($defPath . 'template_parts/knowledge_base/categories.php', $activeThemePath . 'template_parts/knowledge_base/categories.php');

                    @copy($defPath . 'template_parts/knowledge_base/category_articles_list.php', $activeThemePath . 'template_parts/knowledge_base/category_articles_list.php');

                    @copy($defPath . 'template_parts/knowledge_base/search.php', $activeThemePath . 'template_parts/knowledge_base/search.php');

                    @copy($defPath . 'template_parts/knowledge_base/search_results.php', $activeThemePath . 'template_parts/knowledge_base/search_results.php');
                }
            }
        }

        $this->db->query('ALTER TABLE `tbloptions` CHANGE `name` `name` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;');

        $this->db->where('slug', 'contract-expiration');
        $this->db->where('language', 'english');
        $this->db->update(db_prefix().'emailtemplates', ['name' => 'Contract Expiration Reminder (Sent to Customer Contacts)']);

        $this->db->where('name', 'paymentmethod_stripe_bitcoin_enabled');
        $this->db->delete(db_prefix().'options');

        add_option('e_sign_legal_text', 'By clicking on "Sign", I consent to be legally bound by this electronic representation of my signature.');
        add_option('after_subscription_payment_captured', 'send_invoice_and_receipt');
        add_option('show_subscriptions_in_customers_area', 1);
        add_option('show_pdf_signature_contract', 1);
        add_option('view_contract_only_logged_in', 0);
        add_option('calendar_only_assigned_tasks', 0);
        add_option('allow_staff_view_invoices_assigned', 0);
        add_option('allow_staff_view_estimates_assigned', 0);
        add_option('mail_engine', 'codeigniter');
        add_option('save_last_order_for_tables', 0);

        add_option('enable_gdpr', 0);
        add_option('gdpr_page_top_information_block', '');
        add_option('gdpr_lead_data_portability_allowed', '');
        add_option('gdpr_contact_data_portability_allowed', '');
        add_option('gdpr_consent_public_page_top_block', '');
        add_option('privacy_policy', '');
        add_option('terms_and_conditions', '');

        add_option('gdpr_show_terms_and_conditions_in_footer', 0);
        add_option('gdpr_contact_enable_right_to_be_forgotten', 0);
        add_option('gdpr_lead_enable_right_to_be_forgotten', 0);
        add_option('gdpr_on_forgotten_remove_invoices_credit_notes', 0);
        add_option('gdpr_on_forgotten_remove_estimates', 0);
        add_option('gdpr_enable_consent_for_contacts', 0);
        add_option('gdpr_enable_lead_public_form', 0);
        add_option('gdpr_enable_consent_for_leads', 0);
        add_option('show_gdpr_in_customers_menu', 1);
        add_option('show_gdpr_link_in_footer', 1);
        add_option('gdpr_lead_attachments_on_public_form', 0);
        add_option('gdpr_show_lead_custom_fields_on_public_form', 0);
        add_option('gdpr_data_portability_leads', 0);
        add_option('gdpr_data_portability_contacts', 0);
        add_option('gdpr_after_lead_converted_delete', 0);
        add_option('gdpr_enable_terms_and_conditions', 0);
        add_option('gdpr_enable_terms_and_conditions_lead_form', 0);
        add_option('gdpr_enable_terms_and_conditions_ticket_form', 0);

        $this->db->select('id,form_data');
        $forms = $this->db->get(db_prefix().'webtolead')->result_array();
        foreach ($forms as $form) {
            $data = $form['form_data'];
            if (!empty($data)) {
                $data = json_decode($data);
                if ($data) {
                    $modified = false;
                    foreach ($data as $key => $form_data) {
                        if (isset($form_data->type) && $form_data->type == 'email') {
                            $data[$key]->type    = 'text';
                            $data[$key]->subtype = 'email';
                            $modified            = true;
                        }
                        if (isset($form_data->type) && $form_data->type == 'color') {
                            $data[$key]->type    = 'text';
                            $data[$key]->subtype = 'color';
                            $modified            = true;
                        }
                        if (isset($form_data->type) && $form_data->type == 'datetime') {
                            $data[$key]->type = 'datetime-local';
                            $modified         = true;
                        }
                    }
                    if ($modified) {
                        $data = json_encode($data);
                        $this->db->where('id', $form['id']);
                        $this->db->update(db_prefix().'webtolead', ['form_data' => $data]);
                    }
                }
            }
        }

        $this->db->query("CREATE TABLE `tblusermeta` (
          `umeta_id` bigint(20) UNSIGNED NOT NULL,
          `staff_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
          `client_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
          `contact_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
          `meta_key` varchar(255) DEFAULT NULL,
          `meta_value` longtext
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query('ALTER TABLE `tblusermeta`
  ADD PRIMARY KEY (`umeta_id`);');

        $this->db->query('ALTER TABLE `tblusermeta`
  MODIFY `umeta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;');

        $this->db->select('staffid,dashboard_widgets_order,dashboard_widgets_visibility');
        $this->db->from(db_prefix().'staff');
        $staff = $this->db->get()->result_array();
        foreach ($staff as $member) {
            $this->db->insert(db_prefix().'usermeta', [
                'staff_id'   => $member['staffid'],
                'meta_key'   => 'dashboard_widgets_order',
                'meta_value' => $member['dashboard_widgets_order'],
            ]);

            $this->db->insert(db_prefix().'usermeta', [
                'staff_id'   => $member['staffid'],
                'meta_key'   => 'dashboard_widgets_visibility',
                'meta_value' => $member['dashboard_widgets_visibility'],
            ]);
        }

        $this->db->query('ALTER TABLE `tblstaff` DROP `dashboard_widgets_order`, DROP `dashboard_widgets_visibility`;');

        if(!table_exists('tblemailstracking')){
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

        $this->db->query('ALTER TABLE `tblestimates` ADD `signature` VARCHAR(40) NULL AFTER `acceptance_ip`;');
        $this->db->query('ALTER TABLE `tblproposals` ADD `signature` VARCHAR(40) NULL AFTER `acceptance_ip`;');
        $this->db->query('ALTER TABLE `tblcontracts` ADD `hash` VARCHAR(32) NULL AFTER `not_visible_to_client`;');

        $this->db->query('ALTER TABLE `tblcontracts` ADD `signature` VARCHAR(40) NULL AFTER `hash`;');

        $this->db->query('ALTER TABLE `tblcontracts` ADD `signed` BOOLEAN NOT NULL DEFAULT FALSE AFTER `hash`;');

        $this->db->query('ALTER TABLE `tblcontracts` ADD `acceptance_firstname` VARCHAR(50) NULL AFTER `signature`, ADD `acceptance_lastname` VARCHAR(50) NULL AFTER `acceptance_firstname`, ADD `acceptance_email` VARCHAR(100) NULL AFTER `acceptance_lastname`, ADD `acceptance_date` DATETIME NULL AFTER `acceptance_email`, ADD `acceptance_ip` VARCHAR(40) NULL AFTER `acceptance_date`;');

        $this->db->select('id');
        $contracts = $this->db->get(db_prefix().'contracts')->result_array();

        foreach ($contracts as $contract) {
            $this->db->where('id', $contract['id']);
            $this->db->update(db_prefix().'contracts', ['hash' => app_generate_hash()]);
        }

        $this->db->query('CREATE TABLE `tblcontractcomments` (
                `id` int(11) NOT NULL,
                `content` mediumtext,
                `contract_id` int(11) NOT NULL,
                `staffid` int(11) NOT NULL,
                `dateadded` datetime NOT NULL
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

        $this->db->query('ALTER TABLE `tblcontractcomments`
            ADD PRIMARY KEY (`id`);');

        $this->db->query('ALTER TABLE `tblcontractcomments`
            MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

        $this->db->query("ALTER TABLE `tblinvoices` ADD `cycles` INT NOT NULL DEFAULT '0' AFTER `recurring_ends_on`, ADD `total_cycles` INT NOT NULL DEFAULT '0' AFTER `cycles`;");

        $this->db->query("ALTER TABLE `tblexpenses` ADD `cycles` INT NOT NULL DEFAULT '0' AFTER `recurring_ends_on`, ADD `total_cycles` INT NOT NULL DEFAULT '0' AFTER `cycles`;");

        $this->db->query('ALTER TABLE `tblstafftasks` CHANGE `datefinished` `datefinished` VARCHAR(40) NULL;');

        $this->db->query("ALTER TABLE `tblstafftasks` ADD `cycles` INT NOT NULL DEFAULT '0' AFTER `recurring_ends_on`, ADD `total_cycles` INT NOT NULL DEFAULT '0' AFTER `cycles`;");

        $this->db->query("UPDATE tblstafftasks SET datefinished = NULL WHERE datefinished = '0000-00-00 00:00:00'");
        $this->db->query('ALTER TABLE `tblstafftasks` CHANGE `datefinished` `datefinished` DATETIME NULL DEFAULT NULL;');

        $this->db->query('ALTER TABLE `tblinvoices` DROP `recurring_ends_on`;');
        $this->db->query('ALTER TABLE `tblexpenses` DROP `recurring_ends_on`;');
        $this->db->query('ALTER TABLE `tblstafftasks` DROP `recurring_ends_on`;');

        $this->db->select('id');
        $this->db->where('recurring !=', 0);
        $recurring_invoices = $this->db->get(db_prefix().'invoices')->result_array();
        foreach ($recurring_invoices as $invoice) {
            $total_cycles = total_rows(db_prefix().'invoices', ['is_recurring_from' => $invoice['id']]);
            if ($total_cycles != 0) {
                $this->db->where('id', $invoice['id']);
                $this->db->update(db_prefix().'invoices', ['total_cycles' => $total_cycles]);
            }
        }

        $this->db->select('id');
        $this->db->where('recurring', 1);
        $recurring_expenses = $this->db->get(db_prefix().'expenses')->result_array();
        foreach ($recurring_expenses as $expense) {
            $total_cycles = total_rows(db_prefix().'expenses', ['recurring_from' => $expense['id']]);
            if ($total_cycles != 0) {
                $this->db->where('id', $expense['id']);
                $this->db->update(db_prefix().'expenses', ['total_cycles' => $total_cycles]);
            }
        }

        $this->db->query('ALTER TABLE `tblcontacts` ADD `ticket_emails` BOOLEAN NOT NULL DEFAULT TRUE AFTER `project_emails`;');

        $this->db->query("ALTER TABLE `tblinvoices` ADD `subscription_id` INT NOT NULL DEFAULT '0' AFTER `project_id`;");

        $visible_customer_tabs = get_option('visible_customer_profile_tabs');
        if ($visible_customer_tabs != 'all') {
            $visible_customer_tabs = unserialize($visible_customer_tabs);
            array_push($visible_customer_tabs, 'subscriptions');
            array_push($visible_customer_tabs, 'contacts');
            update_option('visible_customer_profile_tabs', serialize($visible_customer_tabs));
        }

        $this->db->query("INSERT INTO `tblpermissions` (`name`, `shortname`) VALUES ('Subscriptions', 'subscriptions');");

        $this->db->query('ALTER TABLE `tblclients` ADD `stripe_id` VARCHAR(40) NULL AFTER `show_primary_contact`;');

        $this->db->query('ALTER TABLE `tblemailqueue` ADD `engine` VARCHAR(40) NULL AFTER `id`;');
        $this->db->query('UPDATE tblemailqueue SET engine="codeigniter";');

        $this->db->query("ALTER TABLE `tblfiles` ADD `task_comment_id` INT NOT NULL DEFAULT '0' AFTER `contact_id`;");

        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('contract', 'contract-expiration-to-staff', 'english', 'Contract Expiration Reminder (Sent to staff members)', 'Contract Expiration Reminder', 'Hello&nbsp;{staff_firstname}&nbsp;{staff_lastname}<br /><br /><span style=\"font-size: 12pt;\">This is a reminder that the following contract will expire soon:</span><br /><br /><span style=\"font-size: 12pt;\"><strong>Subject:</strong> {contract_subject}</span><br /><span style=\"font-size: 12pt;\"><strong>Description:</strong> {contract_description}</span><br /><span style=\"font-size: 12pt;\"><strong>Date Start:</strong> {contract_datestart}</span><br /><span style=\"font-size: 12pt;\"><strong>Date End:</strong> {contract_dateend}</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0);");


        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('subscriptions', 'send-subscription', 'english', 'Send Subscription to Customer', 'Subscription Created', 'Hello&nbsp;{contact_firstname}&nbsp;{contact_lastname}<br /><br />We have prepared the subscription&nbsp;<strong>{subscription_name}</strong> for your company.<br /><br />Click <a href=\"{subscription_link}\">here</a> to review the subscription and subscribe.<br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
('subscriptions', 'subscription-payment-failed', 'english', 'Subscription Payment Failed', 'Your most recent invoice payment failed', 'Hello&nbsp;{contact_firstname}&nbsp;{contact_lastname}<br /><br br=\"\" />Unfortunately, your most recent invoice payment for&nbsp;<strong>{subscription_name}</strong> was declined.<br /><br /> This could be due to a change in your card number, your card expiring,<br /> cancellation of your credit card, or the card issuer not recognizing the<br /> payment and therefore taking action to prevent it.<br /><br /> Please update your payment information as soon as possible by logging in here:<br /><a href=\"{crm_url}\">{crm_url}</a><br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
('subscriptions', 'subscription-canceled', 'english', 'Subscription Canceled (Sent to customer primary contact)', 'Your subscription has been canceled', 'Hello&nbsp;{contact_firstname}&nbsp;{contact_lastname}<br /><br />Your subscription&nbsp;<strong>{subscription_name} </strong>has been canceled, if you have any questions don\'t hesitate to contact us.<br /><br />It was a pleasure doing business with you.<br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
('subscriptions', 'subscription-payment-succeeded', 'english', 'Subscription Payment Succeeded (Sent to customer primary contact)', 'Subscription  Payment Receipt - {subscription_name}', 'Hello&nbsp;{contact_firstname}&nbsp;{contact_lastname}<br /><br />This email is to let you know that we received your payment for subscription&nbsp;<strong>{subscription_name}&nbsp;</strong>of&nbsp;<strong><span>{payment_total}<br /><br /></span></strong>The invoice associated with it is now with status&nbsp;<strong>{invoice_status}<br /></strong><br />Thank you for your confidence.<br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0);");

        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('contract', 'contract-comment-to-client', 'english', 'New Comment  (Sent to Customer Contacts)', 'New Contract Comment', 'Dear {contact_firstname} {contact_lastname}<br /> <br />A new comment has been made on the following contract: <strong>{contract_subject}</strong><br /> <br />You can view and reply to the comment on the following link: <a href=\"{contract_link}\">{contract_subject}</a><br /> <br />Kind Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
('contract', 'contract-comment-to-admin', 'english', 'New Comment (Sent to Staff) ', 'New Contract Comment', 'Hi<br /> <br />A new comment has been made to the proposal <strong>{contract_subject}</strong><br /> <br />You can view and reply to the comment on the following link: <a href=\"{contract_link}\">{contract_subject}</a>&nbsp;or from the admin area.<br /> <br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0);");

        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
  ('gdpr', 'gdpr-removal-request', 'english', 'Removal Request From Contact (Sent to administrators)', 'Data Removal Request Received', 'Hello&nbsp;{staff_firstname}&nbsp;{staff_lastname}<br /><br />Data removal has been requested by&nbsp;{contact_firstname} {contact_lastname}<br /><br />You can review this request and take proper actions directly from the admin area.', '{companyname} | CRM', '', 0, 1, 1),
  ('gdpr', 'gdpr-removal-request-lead', 'english', 'Removal Request From Lead (Sent to administrators)', 'Data Removal Request Received', 'Hello&nbsp;{staff_firstname}&nbsp;{staff_lastname}<br /><br />Data removal has been requested by {lead_name}<br /><br />You can review this request and take proper actions directly from the admin area.<br /><br />To view the lead inside the admin area click here:&nbsp;<a href=\"{lead_link}\">{lead_link}</a>', '{companyname} | CRM', '', 0, 1, 1);");

        $this->db->query("CREATE TABLE `tblsubscriptions` (
                  `id` int(11) NOT NULL,
                  `name` varchar(300) NOT NULL,
                  `description` text,
                  `clientid` int(11) NOT NULL,
                  `currency` int(11) NOT NULL,
                  `tax_id` int(11) NOT NULL DEFAULT '0',
                  `stripe_plan_id` text,
                  `stripe_subscription_id` text NOT NULL,
                  `next_billing_cycle` bigint(20) DEFAULT NULL,
                  `ends_at` bigint(20) DEFAULT NULL,
                  `status` varchar(50) DEFAULT NULL,
                  `quantity` int(11) NOT NULL DEFAULT '1',
                  `project_id` int(11) NOT NULL DEFAULT '0',
                  `hash` varchar(32) NOT NULL,
                  `created` datetime NOT NULL,
                  `created_from` int(11) NOT NULL,
                  `date_subscribed` datetime DEFAULT NULL
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        $this->db->query('ALTER TABLE `tblsubscriptions` ADD `date` DATE NULL AFTER `clientid`;');

        $this->db->query('ALTER TABLE `tblsubscriptions`
              ADD PRIMARY KEY (`id`),
              ADD KEY `clientid` (`clientid`),
              ADD KEY `currency` (`currency`),
              ADD KEY `tax_id` (`tax_id`);');

        $this->db->query('ALTER TABLE `tblsubscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');


        $this->db->where('name', 'available_features');
        $projectSettings = $this->db->get(db_prefix().'projectsettings')->result_array();
        foreach ($projectSettings as $availableFeature) {
            @$setting = unserialize($availableFeature['value']);
            $modified = false;
            if (is_array($setting) && !array_key_exists('project_subscriptions', $setting)) {
                $setting['project_subscriptions'] = 1;
                $modified                         = true;
            }
            if ($modified) {
                $this->db->where('id', $availableFeature['id']);
                $this->db->update(db_prefix().'projectsettings', ['value' => serialize($setting)]);
            }
        }

        add_main_menu_item([
              'name'       => 'subscriptions',
              'permission' => 'subscriptions',
              'url'        => 'subscriptions',
              'icon'       => 'fa fa-repeat',
              'id'         => 'subscriptions',
              'order'      => 4,
        ]);

        $this->db->query('ALTER TABLE `tblinvoices` ADD `deleted_customer_name` VARCHAR(100) NULL AFTER `clientid`;');
        $this->db->query('ALTER TABLE `tblestimates` ADD `deleted_customer_name` VARCHAR(100) NULL AFTER `clientid`;');
        $this->db->query('ALTER TABLE `tblcreditnotes` ADD `deleted_customer_name` VARCHAR(100) NULL AFTER `clientid`;');

        $this->db->query('ALTER TABLE `tblknowledgebasegroups` ADD `group_slug` VARCHAR(300) NULL AFTER `name`;');
        $kb_groups = $this->db->get(db_prefix().'knowledgebasegroups')->result_array();
        foreach ($kb_groups as $group) {
            $slug = slug_it($group['name']);
            $this->db->where('groupid', $group['groupid']);
            $this->db->update(db_prefix().'knowledgebasegroups', ['group_slug' => $slug]);
        }

        $this->db->query('ALTER TABLE `tbltickets` DROP `ip`;');
        $this->db->query('ALTER TABLE `tblticketreplies` DROP `ip`;');

        $this->db->query('ALTER TABLE `tblleads` ADD `hash` VARCHAR(65) NULL AFTER `id`;');

        $this->db->query('ALTER TABLE `tbltickets` ADD INDEX(`contactid`);');

        $this->db->query('ALTER TABLE `tblknowledgebase` DROP `views`;');

        $this->db->query("CREATE TABLE `tblrequestsgdpr` (
  `id` int(11) NOT NULL,
  `clientid` int(11) NOT NULL DEFAULT '0',
  `contact_id` int(11) NOT NULL DEFAULT '0',
  `lead_id` int(11) NOT NULL DEFAULT '0',
  `request_type` varchar(200) DEFAULT NULL,
  `status` varchar(40) DEFAULT NULL,
  `request_date` datetime NOT NULL,
  `request_from` varchar(150) DEFAULT NULL,
  `description` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        $this->db->query('ALTER TABLE `tblrequestsgdpr`
  ADD PRIMARY KEY (`id`);');


        $this->db->query('ALTER TABLE `tblrequestsgdpr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

        $this->db->query('CREATE TABLE `tblconsentpurposes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `date_created` datetime NOT NULL,
  `last_updated` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

        $this->db->query("CREATE TABLE `tblconsents` (
  `id` int(11) NOT NULL,
  `action` varchar(10) NOT NULL,
  `date` datetime NOT NULL,
  `ip` varchar(40) NOT NULL,
  `contact_id` int(11) NOT NULL DEFAULT '0',
  `lead_id` int(11) NOT NULL DEFAULT '0',
  `description` text,
  `opt_in_purpose_description` text,
  `purpose_id` int(11) NOT NULL,
  `staff_name` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        $this->db->query('ALTER TABLE `tblconsentpurposes`
  ADD PRIMARY KEY (`id`);');

        $this->db->query('ALTER TABLE `tblconsents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purpose_id` (`purpose_id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `lead_id` (`lead_id`);');

        $this->db->query('ALTER TABLE `tblconsentpurposes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

        $this->db->query('ALTER TABLE `tblconsents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');
        add_setup_menu_item([
            'name'       => 'gdpr_short',
            'permission' => 'is_admin',
            'icon'       => '',
            'url'        => 'gdpr',
            'order'      => 4,
            'id'         => 'gdpr',
            ]);
        $this->db->query("ALTER TABLE `tblstafftasks` ADD `is_recurring_from` INT NULL AFTER `recurring`;");
        update_option('update_info_message', '<div class="col-md-12">
        <div class="alert alert-success bold">
        <h4 class="bold">Hi! Thanks for updating Perfex CRM - You are using version 2.0.0</h4>
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
         if (file_exists(FCPATH.'pipe.php')) {
            @chmod(FCPATH.'pipe.php', 0755);
        }
    }
}
