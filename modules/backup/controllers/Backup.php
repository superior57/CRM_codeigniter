<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Backup extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_admin()) {
            access_denied('Backup');
        }
    }

    public function download($attachment)
    {
        $this->load->helper('download');

        $path = $this->get_path($attachment);

        if (file_exists($path)) {
            force_download($path, null);
        } else {
            set_alert('warning', 'Could not download backup file.');
            redirect(admin_url('backup'));
        }
    }

    /* Database back up functions */
    public function index()
    {
        $data['title'] = _l('utility_backup');
        $this->load->view('backup', $data);
    }

    public function make_backup_db()
    {
        hooks()->do_action('before_make_backup');

        if (!is_really_writable(BACKUPS_FOLDER)) {
            show_error('/backups folder is not writable. You need to change the permissions to 755');
        }

        $this->load->library('backup/backup_module');
        $success = $this->backup_module->make_backup_db(true);

        if ($success) {
            set_alert('success', _l('backup_success'));
        }

        redirect(admin_url('backup'));
    }

    public function update_auto_backup_options()
    {
        hooks()->do_action('before_update_backup_options');

        if ($this->input->post()) {
            $_post     = $this->input->post();
            $updated_1 = update_option('auto_backup_enabled', $_post['settings']['auto_backup_enabled']);
            $updated_2 = update_option('auto_backup_every', $this->input->post('auto_backup_every'));
            $updated_3 = update_option('delete_backups_older_then', $this->input->post('delete_backups_older_then'));
            if ($updated_2 || $updated_1 || $updated_3) {
                set_alert('success', _l('auto_backup_options_updated'));
            }
        }
        redirect(admin_url('backup'));
    }

    public function delete($backup)
    {
        $path = $this->get_path($backup);

        if (unlink($path)) {
            set_alert('success', _l('backup_delete'));
        }

        redirect(admin_url('backup'));
    }

    private function get_path($name)
    {
        $name = BACKUPS_FOLDER . $name;

        if (file_exists($name . '.zip')) {
            // Codeigniter backup
            $path = $name . '.zip';
        } elseif (file_exists($name . '.sql.gz')) {
            // Prior to 2.3.2
            // From backup_manager
            $path = $name . '.sql.gz';
        } elseif (file_exists($name . '.sql')) {
            // Since 2.3.2
            $path = $name . '.sql';
        } else {
            $path = $name;
        }

        return $path;
    }
}
