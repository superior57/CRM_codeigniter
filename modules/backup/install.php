<?php

defined('BASEPATH') or exit('No direct script access allowed');

add_option('auto_backup_enabled', '0');
add_option('auto_backup_every', '7');
add_option('last_auto_backup', '');
add_option('delete_backups_older_then', '0');

$CI->load->library('backup/backup_module');
$CI->backup_module->create_backup_directory();
