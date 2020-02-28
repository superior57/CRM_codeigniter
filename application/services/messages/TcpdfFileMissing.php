<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractMessage;

class TcpdfFileMissing extends AbstractMessage
{
    protected $alertClass = 'warning';

    private $tcpdfFilePath;

    public function __construct()
    {
        $this->tcpdfFilePath = APPPATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';
    }

    public function isVisible()
    {
        return !file_exists($this->tcpdfFilePath) && is_admin();
    }

    public function getMessage()
    {
        ?>
        <h4 style="margin-top:15px;"><b>Missing TCPDF core file.</b></h4>
        <hr />
        <p>The <b>file responsible for generating PDF documents is missing in your installation</b>. The system was unable to determine if this file really exists, the file should be located in: <b><?php echo $this->tcpdfFilePath; ?></b></p>
        <p style="margin-top:15px;">This can happen because of <b>2 reasons</b>:</p>
        <ul style="margin-top:15px;">
          <li>
            1. Your hosting provider/server firewall <b>removed</b> the <b>tcpdf.php</b> file located in <b><?php echo $this->tcpdfFilePath; ?></b> because the firewall think that is malicious file, mostly happens because of not properly configured firewall rules. <br />
            You will need to contact your hosting provider to whitelist this file, after the file is whitelisted download the core files again and locate this file inside the zip folder, upload the file in: <b> <?php echo APPPATH . 'vendor/tecnickcom/tcpdf/'; ?></b>
        </li>
        <li><br />2. The file is not uploaded or is skipped during upload, you can download the core files again and locate this file inside the zip folder, after that upload the file in: <b> <?php echo APPPATH . 'vendor/tecnickcom/tcpdf/'; ?></b></li>
    </ul>
    <?php
    }
}
