<?php

namespace app\services\upgrade;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\upgrade\AbstractCurlAdapter;
use app\services\upgrade\Response;

class CurlCoreUpgradeAdapter extends AbstractCurlAdapter
{
    use Response;

    public function perform($zipFile)
    {
        $zipResource = fopen($zipFile, 'w+');

        // Get The Zip File From Server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config->url);
        curl_setopt($ch, CURLOPT_USERAGENT, get_instance()->agent->agent_string());
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FILE, $zipResource);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'base_url'      => site_url(),
            'buyer_version' => $this->config->current_version,
            'server_ip'     => $_SERVER['SERVER_ADDR'],
            'php_version'   => PHP_VERSION,
        ]);

        $success = curl_exec($ch);
        if (!$success) {
            $error = $this->getErrorByStatusCode(curl_getinfo($ch, CURLINFO_HTTP_CODE));

            if ($error == '') {
                // Uknown error
                $error = curl_error($ch);
            }

            throw new \Exception($error);
        }

        curl_close($ch);

        $this->extract($zipFile);
    }
}
