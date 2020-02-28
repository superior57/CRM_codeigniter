<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_232 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        add_option('lead_unique_validation', '["email"]');
        add_option('last_upgrade_copy_data', '');

        if (!$this->db->field_exists('created_at', 'creditnote_refunds')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'creditnote_refunds` ADD `created_at` DATETIME NULL DEFAULT NULL AFTER `amount`;');
            $this->db->update('creditnote_refunds', ['created_at'=>date('Y-m-d H:i:s')]);
        }

        if (!$this->db->field_exists('decimal_separator', 'currencies')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'currencies` ADD `decimal_separator` VARCHAR(5) NULL AFTER `name`, ADD `thousand_separator` VARCHAR(5) NULL AFTER `decimal_separator`, ADD `placement` VARCHAR(10) NULL AFTER `thousand_separator`;');
        }

        $this->db->update(db_prefix() . 'currencies', [
            'decimal_separator'  => get_option('decimal_separator'),
            'thousand_separator' => get_option('thousand_separator'),
            'placement'          => get_option('currency_placement'),
        ]);

        $this->db->where('name', 'di');
        $this->db->update('options', ['autoload'=>1]);
    }
}
