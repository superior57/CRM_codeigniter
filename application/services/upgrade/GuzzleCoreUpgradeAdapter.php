<?php

namespace app\services\upgrade;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\upgrade\AbstractCurlAdapter;
use app\services\upgrade\Response;
use GuzzleHttp\Client;

class GuzzleCoreUpgradeAdapter extends AbstractCurlAdapter
{
    use Response;

    public function perform($zipFile)
    {
        $client = new Client(
            [
                'verify'          => false,
                'allow_redirects' => true,
                'sink'            => $zipFile,
            ]
        );

        try {
            $opts = [
                'form_params' => [
                    'base_url'      => site_url(),
                    'buyer_version' => $this->config->current_version,
                    'server_ip'     => $_SERVER['SERVER_ADDR'],
                    'php_version'   => PHP_VERSION,
                 ],
            ];

            $client->request('POST', $this->config->url, $opts);
        } catch (\Exception $e) {
            $error = '';
            if (method_exists(($e->getResponse()), 'getStatusCode')) {
                $statusCode = $e->getResponse()->getStatusCode();
                $error      = $this->getErrorByStatusCode($statusCode);
            }

            if ($error == '') {
                // Uknown error
                if ($response = $e->getResponse()) {
                    $error = $response->getBody()->getContents();
                    if (!$error) {
                        $error = $e->getMessage();
                    }
                } else {
                    $error = $e->getMessage();
                }
            }

            throw new \Exception($error);
        }

        $this->extract($zipFile);
    }
}
