<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractMessage;

class ModSecurityEnabled extends AbstractMessage
{
    protected $alertClass = 'warning';

    public function isVisible()
    {
        if (!function_exists('apache_get_modules')) {
            return false;
        }

        $modules = @apache_get_modules();

        return is_array($modules) && in_array('mod_security', $modules) && is_admin();
    }

    public function getMessage()
    {
        ?>
        <h4><b>Mod Security Warning</b></h4>
        <hr class="hr-10" />
        <p>
            Mod Security is detected on your server, it's highly recommended to contact your hosting provider to disable mod security on your installation because most likely you will experience issues with updating email templates, sending e.q. invoices to email etc... The mod security PHP modules in most cases will block this data but request will contain HTML.
        </p>
        <?php
    }
}
