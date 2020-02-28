<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractMessage;

class Iconv extends AbstractMessage
{
    public function isVisible()
    {
        return !extension_loaded('iconv');
    }

    public function getMessage()
    {
        return 'A required PHP extension is not loaded. You must to enable the <b>iconv</b> php extension in order everything to work properly. You can enable the <b>iconv</b> extension via php.ini, cPanel PHP extensions area or contact your hosting provider to enable this extension.';
    }
}
