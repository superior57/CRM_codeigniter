<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Backup_module
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function make_backup_db($manual = false)
    {
        if ((get_option('auto_backup_enabled') == '1'
            && time() > (get_option('last_auto_backup') + get_option('auto_backup_every') * 24 * 60 * 60))
            || $manual == true) {
            $this->create_backup_directory();

            $manager = $this->get_backup_manager_name();

            if ($manager == 'backup_manager') {
                $configFileSystemProvider = new \BackupManager\Config\Config([
                'local' => [
                    'type' => 'Local',
                    'root' => BACKUPS_FOLDER,
                ],
            ]);
                // Only mysql is supported, not sure if this do the job
                if ($this->ci->db->dbdriver != 'mysqli') {
                    return $this->database_backup_codeigniter();
                }

                /**
                 * If port is configured in the hostname e.q. hostname:port
                 * @var string
                 */
                $port = '';
                if ($parsePort = parse_url(APP_DB_HOSTNAME, PHP_URL_PORT)) {
                    if (is_int($parsePort)) {
                        $port = $parsePort;
                    }
                }

                $configDatabase = new \BackupManager\Config\Config([
                'production' => [
                    'type'     => 'mysql',
                    'host'     => APP_DB_HOSTNAME,
                    'port'     => $port,
                    'user'     => APP_DB_USERNAME,
                    'pass'     => APP_DB_PASSWORD,
                    'database' => APP_DB_NAME,
                ],
            ]);

                // build providers
                $filesystems = new \BackupManager\Filesystems\FilesystemProvider($configFileSystemProvider);
                $filesystems->add(new \BackupManager\Filesystems\LocalFilesystem);
                $databases = new \BackupManager\Databases\DatabaseProvider($configDatabase);
                $databases->add(new \BackupManager\Databases\MysqlDatabase);
                $compressors = new \BackupManager\Compressors\CompressorProvider;
                $compressors->add(new \BackupManager\Compressors\GzipCompressor);
                $compressors->add(new \BackupManager\Compressors\NullCompressor);

                // build manager
                $manager = new \BackupManager\Manager($filesystems, $databases, $compressors);

                $backup_name = date('Y-m-d-H-i-s') . '_backup-v' . wordwrap($this->ci->app->get_current_db_version(), 1, '-', true) . '.sql';

                try {

                  /*
                      Restore example, not working
                      $manager->makeRestore()->run('local', '2018-06-12-12-02-28_backup.sql.gz', 'production', 'gzip');
                      die;
                   */

                    $manager->makeBackup()
                    ->run('production', [
                        new \BackupManager\Filesystems\Destination('local', $backup_name),
                    ], 'null');

                    log_activity('Database Backup [' . $backup_name . '.gz' . ']', null);

                    if ($manual == false) {
                        update_option('last_auto_backup', time());
                    }

                    $this->maybe_delete_old_backups();

                    return true;
                } catch (Exception $e) {
                    if (ENVIRONMENT !== 'production') {
                        log_activity('NEW BACKUP MANAGER ERROR [' . $e->getMessage() . ']');
                    }

                    return false;
                }
            } elseif ($manager == 'codeigniter') {
                return $this->database_backup_codeigniter($manual);
            }
        }
        return false;
    }

    public function get_backup_manager_name()
    {
        return defined('APP_DATABASE_BACKUP_MANAGER') ? APP_DATABASE_BACKUP_MANAGER : 'codeigniter';
    }

    public function create_backup_directory()
    {
        if (!is_dir(BACKUPS_FOLDER)) {
            mkdir(BACKUPS_FOLDER, 0755);
            $fp = fopen(rtrim(BACKUPS_FOLDER, '/') . '/' . 'index.html', 'w');
            fclose($fp);
            fopen(BACKUPS_FOLDER . '.htaccess', 'w');
            $fp = fopen(BACKUPS_FOLDER . '.htaccess', 'a+');
            if ($fp) {
                fwrite($fp, 'Order Deny,Allow' . PHP_EOL . 'Deny from all');
                fclose($fp);
            }
        }
    }

    private function database_backup_codeigniter($manual)
    {
        $this->handle_memory_limit_error();

        $this->ci->load->dbutil();

        $prefs = [
                'format'   => 'zip',
                'filename' => date('Y-m-d-H-i-s') . '_backup.sql',
            ];

        $backup           = @$this->ci->dbutil->backup($prefs);
        $backup_name      = unique_filename(BACKUPS_FOLDER, 'database_backup_' . date('Y-m-d-H-i-s') . '-v' . wordwrap($this->ci->app->get_current_db_version(), 1, '-', true) . '.zip');
        $save_backup_path = BACKUPS_FOLDER . $backup_name;
        $this->ci->load->helper('file');

        if (@write_file($save_backup_path, $backup)) {
            log_activity('Database Backup [' . $backup_name . ']', null);

            if ($manual == false) {
                update_option('last_auto_backup', time());
            }

            $this->maybe_delete_old_backups();

            return true;
        }

        return false;
    }

    private function maybe_delete_old_backups()
    {
        $delete_backups = get_option('delete_backups_older_then');
        // After write backup check for delete
        if ($delete_backups != '0') {
            $backups                 = list_files(BACKUPS_FOLDER);
            $backups_days_to_seconds = ($delete_backups * 24 * 60 * 60);
            foreach ($backups as $b) {
                if ($b == 'index.html') {
                    continue;
                }
                if ((time() - filectime(BACKUPS_FOLDER . $b)) > $backups_days_to_seconds) {
                    @unlink(BACKUPS_FOLDER . $b);
                }
            }
        }
    }

    private function handle_memory_limit_error()
    {
        register_shutdown_function(function () {
            $error = error_get_last();

            if (null !== $error) {
                if (strpos($error['message'], 'Allowed memory size of') !== false) {
                    echo '<h2>A a fatal error has been triggered during backup because of PHP memory limit.</h2>';
                    echo '<div style="font-size:18px;">';
                    echo '<p>Your current PHP memory limit is ' . ini_get('memory_limit') . ' which seems <b>too low</b> to process the database backup.</p>';
                    echo '<p>As a suggestion please try the following:</p>';
                    echo '<ul>';
                    echo '<li>Increase the PHP memory limit and try again to perform a database backup again.</li>';
                    echo '<li>Try the optional backup manager (improved one) by defining a constant in application/config/app-config.php at the bottom add: <pre><code>define(\'APP_DATABASE_BACKUP_MANAGER\', \'backup_manager\');</code></pre></li>';
                    echo '</ul>';
                    echo '</div>';
                } else {
                    trigger_error('Fatal error: ' . $error['message'] . 'in ' . $error['file'] . ' on line ' . $error['line'], E_USER_ERROR);
                }
            }
        });
    }
}
