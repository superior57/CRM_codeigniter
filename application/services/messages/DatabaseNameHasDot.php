<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractMessage;

class DatabaseNameHasDot extends AbstractMessage
{
    protected $alertClass = 'warning';

    public function isVisible()
    {
        return defined('APP_DB_NAME') && strpos(APP_DB_NAME, '.') !== false && is_admin();
    }

    public function getMessage()
    {
        ?>
        <h4><b>Database name (<?php echo APP_DB_NAME; ?>) change required.</b></h4>
        The system indicated that your database name contains <b>. (dot)</b>,<b> you can encounter upgrading errors when your database</b> name contains dot, it's highly recommended to change your database name to be without dot as example: <b><?php echo str_replace('.', '', APP_DB_NAME); ?></b>
        <hr />
        <ul>
            <li>1. Change the name to be without dot via cPanel/Command line or contact your hosting provider/server administrator to change the name. (use the best method that is suitable for you)</li>
            <li>2. After the name is changed navigate via ftp or cPanel to <b>application/config/app-config.php</b> and change the database name config constant to your new database name.</li>
            <li>3. Save the modified <b>app-config.php</b> file.</li>
        </ul>
        <br />
        <small>This message will disappear automatically once the database name won't contain dot.</small>
        <?php
    }
}
