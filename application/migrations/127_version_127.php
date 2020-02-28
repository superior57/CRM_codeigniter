<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Version_127 extends CI_Migration
{
    function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        die('<h2>You can only upgrade from version 1.2.7 or bigger, upgrading from version below 1.2.7 is not supported, contact us for further assistance if it\'s needed, we may take a look at this and upgrade you manually if you have a lot data.</h2>');

        $this->db->query("ALTER TABLE  `tblemailtemplates` CHANGE  `plaintext`  `plaintext` INT( 11 ) NOT NULL DEFAULT  '0';");

        if ($this->session->has_userdata('update_encryption_key')) {
            $enc = $this->session->userdata('update_encryption_key');
        } else {
            $enc = $this->config->item('encryption_key');
        }
        $base = $this->config->item('base_url');

        $db_name  = $this->db->database;
        $hostname = $this->db->hostname;
        $username = $this->db->username;
        $password = $this->db->password;
        $sess_driver = $this->config->item('sess_driver');
        $sess_save_path = $this->config->item('sess_save_path');

        $new_config_file = '<?php defined(\'BASEPATH\') OR exit(\'No direct script access allowed\');
/*
|--------------------------------------------------------------------------
| Base Site URL
|--------------------------------------------------------------------------
|
| URL to your CodeIgniter root. Typically this will be your base URL,
| WITH a trailing slash:
|
|   http://example.com/
|
| If this is not set tshen CodeIgniter will try guess the protocol, domain
| and path to your installation. However, you should always configure this
| explicitly and nevessr rely on auto-guessing, especially in production
| environments.
|
*/

define(\'APP_BASE_URL\',\'' . $base . '\');

/*
|--------------------------------------------------------------------------
| Encryption Key
| IMPORTANT: Dont change this EVER
|--------------------------------------------------------------------------
|
| If you use the Encryption class, you must set an encryption key.
| See the user guide for more info.
|
| http://codeigniter.com/user_guide/libraries/encryption.html
|
*/

define(\'APP_ENC_KEY\',\'' . $enc . '\');

/* Database credentials */

/* The hostname of your database server. */
define(\'APP_DB_HOSTNAME\',\'' . $hostname . '\');
/* The username used to connect to the database */
define(\'APP_DB_USERNAME\',\'' . $username . '\');
/* The password used to connect to the database */
define(\'APP_DB_PASSWORD\',\'' . $password . '\');
/* The name of the database you want to connect to */
define(\'APP_DB_NAME\',\'' . $db_name . '\');

/* Session Handler */

define(\'SESS_DRIVER\',\'' . $sess_driver . '\');
define(\'SESS_SAVE_PATH\',\'' . $sess_save_path . '\');';

        $fp = fopen(APPPATH . 'config/app-config.php', 'w');
        if ($fp) {
            fwrite($fp, $new_config_file);
            fclose($fp);

                $fp = fopen(APPPATH . 'config/database.php', 'w+');
                if ($fp) {
                    $update_old_db_config = '<?php defined(\'BASEPATH\') OR exit(\'No direct script access allowed\');
include_once(APPPATH.\'config/app-config.php\');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the \'Database Connection\'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|   [\'dsn\']      The full DSN string describe a connection to the database.
|   [\'hostname\'] The hostname of your database server.
|   [\'username\'] The username used to connect to the database
|   [\'password\'] The password used to connect to the database
|   [\'database\'] The name of the database you want to connect to
|   [\'dbdriver\'] The database driver. e.g.: mysqli.
|           Currently supported:
|                cubrid, ibase, mssql, mysql, mysqli, oci8,
|                odbc, pdo, postgre, sqlite, sqlite3, sqlsrv
|   [\'dbprefix\'] You can add an optional prefix, which will be added
|                to the table name when using the  Query Builder class
|   [\'pconnect\'] TRUE/FALSE - Whether to use a persistent connection
|   [\'db_debug\'] TRUE/FALSE - Whether database errors should be displayed.
|   [\'cache_on\'] TRUE/FALSE - Enables/disables query caching
|   [\'cachedir\'] The path to the folder where cache files should be stored
|   [\'char_set\'] The character set used in communicating with the database
|   [\'dbcollat\'] The character collation used in communicating with the database
|                NOTE: For MySQL and MySQLi databases, this setting is only used
|                as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|                (and in table creation queries made with DB Forge).
|                There is an incompatibility in PHP with mysql_real_escape_string() which
|                can make your site vulnerable to SQL injection if you are using a
|                multi-byte character set and are running versions lower than these.
|                Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|   [\'swap_pre\'] A default table prefix that should be swapped with the dbprefix
|   [\'encrypt\']  Whether or not to use an encrypted connection.
|
|           \'mysql\' (deprecated), \'sqlsrv\' and \'pdo/sqlsrv\' drivers accept TRUE/FALSE
|           \'mysqli\' and \'pdo/mysql\' drivers accept an array with the following options:
|
|               \'ssl_key\'    - Path to the private key file
|               \'ssl_cert\'   - Path to the public key certificate file
|               \'ssl_ca\'     - Path to the certificate authority file
|               \'ssl_capath\' - Path to a directory containing trusted CA certificats in PEM format
|               \'ssl_cipher\' - List of *allowed* ciphers to be used for the encryption, separated by colons (\':\')
|               \'ssl_verify\' - TRUE/FALSE; Whether verify the server certificate or not (\'mysqli\' only)
|
|   [\'compress\'] Whether or not to use client compression (MySQL only)
|   [\'stricton\'] TRUE/FALSE - forces \'Strict Mode\' connections
|                           - good for ensuring strict SQL while developing
|   [\'ssl_options\'] Used to set various SSL options that can be used when making SSL connections.
|   [\'failover\'] array - A array with 0 or more data for connections if the main should fail.
|   [\'save_queries\'] TRUE/FALSE - Whether to "save" all executed queries.
|               NOTE: Disabling this will also effectively disable both
|               $this->db->last_query() and profiling of DB queries.
|               When you run a query, with this setting set to TRUE (default),
|               CodeIgniter will store the SQL statement for debugging purposes.
|               However, this may cause high memory usage, especially if you run
|               a lot of SQL queries ... disable this to avoid that problem.
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the \'default\' group).
|
| The $query_builder variables lets you determine whether or not to load
| the query builder class.
*/
$active_group = \'default\';
$query_builder = TRUE;

global $app_db_encrypt;
$db_encrypt = false;
if (defined(\'APP_DB_ENCRYPT\')) {
    // For php 7+
    $db_encrypt = APP_DB_ENCRYPT;
} elseif (!is_null($app_db_encrypt)) {
    $db_encrypt = $app_db_encrypt;
}

$db[\'default\'] = array(
    \'dsn\'   => \'\',
    \'hostname\' => APP_DB_HOSTNAME,
    \'username\' => APP_DB_USERNAME,
    \'password\' => APP_DB_PASSWORD,
    \'database\' => APP_DB_NAME,
    \'dbdriver\' => defined(\'APP_DB_DRIVER\') ? APP_DB_DRIVER : \'mysqli\',
    \'dbprefix\' => db_prefix(),
    \'pconnect\' => FALSE,
    \'db_debug\' => (ENVIRONMENT !== \'production\'),
    \'cache_on\' => FALSE,
    \'cachedir\' => \'\',
    \'char_set\' => defined(\'APP_DB_CHARSET\') ? APP_DB_CHARSET : \'utf8\',
    \'dbcollat\' => defined(\'APP_DB_COLLATION\') ? APP_DB_COLLATION : \'utf8_general_ci\',
    \'swap_pre\' => \'\',
    \'encrypt\' => $db_encrypt,
    \'compress\' => FALSE,
    \'stricton\' => FALSE,
    \'failover\' => array(),
    \'save_queries\' => TRUE
);';
                    fwrite($fp, $update_old_db_config);
                    fclose($fp);
                }
            }

        add_option('default_task_priority', 2);
        add_option('dropbox_app_key', '');
        add_option('auto_assign_customer_admin_after_lead_convert', 1);

        $this->db->query("ALTER TABLE  `tblinvoices` ADD  `number_format` INT NOT NULL DEFAULT  '0' AFTER  `prefix`;");

        $invoices = $this->db->get(db_prefix().'invoices')->result_array();
        foreach ($invoices as $invoice) {
            $this->db->where('id', $invoice['id']);
            $this->db->update(db_prefix().'invoices', array(
                'number_format' => get_option('invoice_number_format')
            ));
        }

        $this->db->query("ALTER TABLE  `tblestimates` ADD  `number_format` INT NOT NULL DEFAULT  '0' AFTER  `prefix`");

        $estimates = $this->db->get(db_prefix().'estimates')->result_array();
        foreach ($estimates as $estimate) {
            $this->db->where('id', $estimate['id']);
            $this->db->update(db_prefix().'estimates', array(
                'number_format' => get_option('estimate_number_format')
            ));
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `tblfiles` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `rel_id` int(11) NOT NULL,
                  `rel_type` varchar(20) NOT NULL,
                  `file_name` varchar(600) NOT NULL,
                  `filetype` varchar(40) DEFAULT NULL,
                  `visible_to_customer` int(11) NOT NULL DEFAULT '0',
                  `attachment_key` varchar(32) DEFAULT NULL,
                  `external` varchar(40) DEFAULT NULL,
                  `external_link` text,
                  `thumbnail_link` text COMMENT 'For external usage',
                  `staffid` int(11) NOT NULL,
                  `contact_id` int(11) DEFAULT '0',
                  `dateadded` datetime NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `rel_id` (`rel_id`),
                  KEY `rel_type` (`rel_type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
        $lead_attachments = $this->db->get(db_prefix().'leadattachments')->result_array();

        foreach ($lead_attachments as $at) {
            $this->db->insert(db_prefix().'files', array(
                'staffid' => $at['addedfrom'],
                'dateadded' => $at['dateadded'],
                'rel_id' => $at['leadid'],
                'attachment_key' => md5(uniqid(rand(), true) . $at['leadid'] . 'lead' . time()),
                'rel_type' => 'lead',
                'file_name' => $at['file_name'],
                'filetype' => $at['filetype']
            ));
        }

        $this->db->query("DROP TABLE tblleadattachments");

        $expenses = $this->db->get(db_prefix().'expenses')->result_array();
        foreach ($expenses as $expense) {
            if (!empty($expense['attachment'])) {
                $this->db->insert(db_prefix().'files', array(
                    'staffid' => $expense['addedfrom'],
                    'dateadded' => $expense['dateadded'],
                    'rel_id' => $expense['id'],
                    'attachment_key' => md5(uniqid(rand(), true) . $expense['id'] . 'expense' . time()),
                    'rel_type' => 'expense',
                    'file_name' => $expense['attachment'],
                    'filetype' => $expense['filetype']
                ));
            }
        }

        $this->db->query("ALTER TABLE  `tblexpenses` DROP  `attachment` ,
DROP  `filetype` ;");

        $contract_attachments = $this->db->get(db_prefix().'contractattachments')->result_array();
        foreach ($contract_attachments as $at) {
            $this->db->insert(db_prefix().'files', array(
                'staffid' => 1,
                'dateadded' => $at['dateadded'],
                'rel_id' => $at['contractid'],
                'attachment_key' => md5(uniqid(rand(), true) . $at['contractid'] . 'contract' . time()),
                'rel_type' => 'contract',
                'file_name' => $at['file_name'],
                'filetype' => $at['filetype']
            ));
        }

        $this->db->query("DROP TABLE tblcontractattachments");

        $client_attachments = $this->db->get(db_prefix().'clientattachments')->result_array();
        foreach ($client_attachments as $at) {
            $this->db->insert(db_prefix().'files', array(
                'staffid' => 1,
                'dateadded' => $at['datecreated'],
                'rel_id' => $at['clientid'],
                'attachment_key' => md5(uniqid(rand(), true) . $at['clientid'] . 'customer' . time()),
                'rel_type' => 'customer',
                'file_name' => $at['file_name'],
                'filetype' => $at['filetype']
            ));
        }

        $this->db->query("DROP TABLE tblclientattachments");


        $sales_attachments = $this->db->get(db_prefix().'salesattachments')->result_array();
        foreach ($sales_attachments as $at) {
            $this->db->insert(db_prefix().'files', array(
                'staffid' => 1,
                'dateadded' => $at['datecreated'],
                'rel_id' => $at['rel_id'],
                'rel_type' => $at['rel_type'],
                'file_name' => $at['file_name'],
                'attachment_key' => md5(uniqid(rand(), true) . $at['rel_id'] . $at['rel_type'] . time()),
                'filetype' => $at['filetype'],
                'attachment_key' => $at['attachment_key'],
                'visible_to_customer' => $at['visible_to_customer']
            ));
        }

        $this->db->query("DROP TABLE tblsalesattachments");

        $newsfeed_attachments = $this->db->get(db_prefix().'postattachments')->result_array();
        foreach ($newsfeed_attachments as $at) {
            $this->db->insert(db_prefix().'files', array(
                'staffid' => 1,
                'dateadded' => $at['datecreated'],
                'rel_id' => $at['postid'],
                'rel_type' => 'newsfeed_post',
                'attachment_key' => md5(uniqid(rand(), true) . $at['postid'] . 'newsfeed_post' . time()),
                'file_name' => $at['filename'],
                'filetype' => $at['filetype']
            ));
        }

        $this->db->query("DROP TABLE tblpostattachments");


        $tasks_attachments = $this->db->get(db_prefix().'stafftasksattachments')->result_array();
        foreach ($tasks_attachments as $at) {
            $this->db->insert(db_prefix().'files', array(
                'staffid' => 1,
                'dateadded' => $at['dateadded'],
                'rel_id' => $at['taskid'],
                'contact_id' => $at['contact_id'],
                'attachment_key' => md5(uniqid(rand(), true) . $at['taskid'] . 'task' . time()),
                'rel_type' => 'task',
                'file_name' => $at['file_name'],
                'filetype' => $at['filetype']
            ));
        }

        $this->db->query("DROP TABLE tblstafftasksattachments");

        $this->db->query("ALTER TABLE `tblclients` ADD `active` INT NOT NULL DEFAULT '1' AFTER `datecreated`;");

        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
                ('staff', 'new-staff-created', 'english', 'New Staff Created (Welcome Email)', 'You are added as staff member', 'Hello&nbsp;{staff_firstname}&nbsp;{staff_lastname}<br /><br />You are added as member on our CRM.<br />You can login at {admin_url}<br /><br />Please use the following&nbsp;logic credentials:<br /><br />Email:&nbsp;{staff_email}<br />Password:&nbsp;{password}<br /><br />Best Regards,<br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0);");


        $this->db->like('name', 'custom_company_field_', 'after');
        $cfields = $this->db->get(db_prefix().'options')->result_array();
        $i      = 0;
        foreach ($cfields as $field) {
            $cfields[$i]['label'] = str_replace('custom_company_field_', '', $field['name']);
            $cfields[$i]['label'] = str_replace('_', ' ', $cfields[$i]['label']);
            $cfields[$i]['label'] = $cfields[$i]['label'];
            $i++;
        }
        foreach($cfields as $f){
            $this->db->insert(db_prefix().'customfields',array(
                    'fieldto'=>'company',
                    'name'=>$f['label'],
                    'slug' => slug_it('company_' . $f['label'], array(
                        'separator' => '_'
                    )),
                    'type'=>'input',
                    'show_on_pdf'=>1,
                    'show_on_table'=>1,
                    'show_on_client_portal'=>1,
                    'disalow_client_to_edit'=>0,
                ));
                $insert_id = $this->db->insert_id();
                $this->db->insert(db_prefix().'customfieldsvalues',array(
                    'relid'=>0,
                    'fieldid'=>$insert_id,
                    'fieldto'=>'company',
                    'value'=>$f['value'],

                ));

                $this->db->where('id',$f['id']);
                $this->db->delete(db_prefix().'options');
        }


        $this->db->query("CREATE TABLE IF NOT EXISTS `tblitemstax` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `itemid` int(11) NOT NULL,
              `rel_id` int(11) NOT NULL,
              `rel_type` varchar(20) NOT NULL,
              `taxrate` decimal(11,2) NOT NULL,
              `taxname` varchar(100) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

        $taxes_invoices = $this->db->get(db_prefix().'invoiceitemstaxes')->result_array();
        foreach($taxes_invoices as $t){
            $this->db->insert(db_prefix().'itemstax',array(
                'rel_id'=>$t['invoice_id'],
                'rel_type'=>'invoice',
                'itemid'=>$t['itemid'],
                'taxrate'=>$t['taxrate'],
                'taxname'=>$t['taxname'],
                ));
        }

        $this->db->query("DROP TABLE tblinvoiceitemstaxes");

        $taxes_invoices = $this->db->get(db_prefix().'estimateitemstaxes')->result_array();
        foreach($taxes_invoices as $t){
            $this->db->insert(db_prefix().'itemstax',array(
                'rel_id'=>$t['estimate_id'],
                'rel_type'=>'estimate',
                'itemid'=>$t['itemid'],
                'taxrate'=>$t['taxrate'],
                'taxname'=>$t['taxname'],
                ));
        }

        $this->db->query("DROP TABLE tblestimateitemstaxes");

        $this->db->query("ALTER TABLE `tblstaff` ADD `email_signature` TEXT NULL AFTER `is_not_staff`;");

        $this->db->where('name','invoice_year');
        $this->db->delete(db_prefix().'options');

        $this->db->where('name','estimate_year');
        $this->db->delete(db_prefix().'options');

        $this->db->query("ALTER TABLE `tblinvoices` DROP `year`;");
        $this->db->query("ALTER TABLE `tblestimates` DROP `year`;");


        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
        ('client', 'contact-forgot-password', 'english', 'Forgot Password', 'Create New Password', '<h2>Create a new password</h2>\r\nForgot your password?<br /> To create a new password, just follow this link:<br /> <br /> <big><strong>{reset_password_url}</strong></big><br /> <br /> You received this email, because it was requested by a {companyname}&nbsp;user. This is part of the procedure to create a new password on the system. If you DID NOT request a new password then please ignore this email and your password will remain the same. <br /><br /> {email_signature}', '{companyname} | CRM', '', 0, 1, 0),
        ('client', 'contact-password-reseted', 'english', 'Password Reset - Confirmation', 'Your password has been changed', '<strong>You have changed your password.<br /></strong><br /> Please, keep it in your records so you don''t forget it.<br /> <br /> Your email address for login is: {contact_email}<br />If this wasnt you, please contact us.<br /><br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
        ('client', 'contact-set-password', 'english', 'Set New Password', 'Set new password on {companyname} ', '<h2>Setup your new password on {companyname}</h2>\r\nPlease use the following link to setup your new password.<br /><br />Keep it in your records so you don''t forget it.<br /><br /> Please set your new password in 48 hours. After that you wont be able to set your password. <br /><br />You can login at: {crm_url}<br /> Your email address for login: {contact_email}<br /> <br /><big><strong>{set_password_url}</strong></big><br /> <br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0),
        ('staff', 'staff-forgot-password', 'english', 'Forgot Password', 'Create New Password', '<h2>Create a new password</h2>\r\nForgot your password?<br /> To create a new password, just follow this link:<br /> <br /> <big><strong>{reset_password_url}</strong></big><br /> <br /> You received this email, because it was requested by a {companyname}&nbsp;user. This is part of the procedure to create a new password on the system. If you DID NOT request a new password then please ignore this email and your password will remain the same. <br /><br /> {email_signature}', '{companyname} | CRM', '', 0, 1, 0),
        ('staff', 'staff-password-reseted', 'english', 'Password Reset - Confirmation', 'Your password has been changed', '<strong>You have changed your password.<br /></strong><br /> Please, keep it in your records so you don''t forget it.<br /> <br /> Your email address for login is: {staff_email}<br /> If this wasnt you, please contact us.<br /><br />{email_signature}', '{companyname} | CRM', '', 0, 1, 0);");

        $this->db->query("INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('project', 'assigned-to-project', 'english', 'New Project Created (Sent to Customer Contacts)', 'New Project Created', '<p>Hello&nbsp;{contact_firstname}</p>\r\n<p>New project is assigned to your company.<br />Project Name:&nbsp;{project_name}</p>\r\n<p>You can view the project on the following link:{project_link}</p>\r\n<p>We are looking forward hearing from you.</p>\r\n<p>{email_signature}</p>', '{companyname} | CRM', NULL, 0, 1, 0);");

          update_option('update_info_message', '<div class="col-md-12">
            <div class="alert alert-success bold">
                <h4 class="bold">Hi! Thanks for updating Perfex CRM - You are using version 1.2.7</h4>
                <p>
                    This window will reload automaticaly in 10 seconds and will try to clear your browser cache, however its recomended to clear your browser cache manually.
                </p>
            </div>
        </div>
        <script>
            setTimeout(function(){
                window.location.reload();
            },10000);
        </script>
        ');

    }
}
