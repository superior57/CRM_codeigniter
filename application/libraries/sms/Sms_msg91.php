<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Sms_msg91 extends App_sms
{
    private $auth_key;

    private $sender_id;

    private $requestURL = 'http://api.msg91.com/api/v2/sendsms';

    public function __construct()
    {
        parent::__construct();

        $this->sender_id = $this->get_option('msg91', 'sender_id');
        $this->auth_key  = $this->get_option('msg91', 'auth_key');

        $this->add_gateway('msg91', [
                'info'    => "<p>MSG91 SMS integration is one way messaging, means that your customers won't be able to reply to the SMS.</p><hr class='hr-10'>",
                'name'    => 'MSG91',
                'options' => [
                    [
                        'name'  => 'sender_id',
                        'label' => 'Sender ID',
                        'info'  => '<p><a href="https://help.msg91.com/article/40-what-is-a-sender-id-how-to-select-a-sender-id" target="_blank">https://help.msg91.com/article/40-what-is-a-sender-id-how-to-select-a-sender-id</a></p>',
                    ],
                     [
                        'name'  => 'auth_key',
                        'label' => 'Auth Key',
                    ],
                ],
            ]);
    }

    public function send($number, $message)
    {
        try {
            $response = $this->client->request('POST', $this->requestURL, [
        'body' => json_encode([
            'sender'  => empty($this->sender_id) ? get_option('companyname') : $this->sender_id,
            'route'   => 4,
            'country' => 0,
            'sms'     => [
                ['message' => urlencode($message), 'to' => [$number]],
            ],
        ]),
        'allow_redirects' => [
            'max' => 10,
        ],
        'headers' => [
            'authkey' => $this->auth_key,
        ],
        'version'        => CURL_HTTP_VERSION_1_1,
        'decode_content' => [CURLOPT_ENCODING => ''],
        ]);

            $result = json_decode($response->getBody());

            if ($result->type == 'success') {
                log_activity('SMS sent via MSG91 to ' . $number . ', Message: ' . $message);

                return true;
            }
            $this->set_error($result->message);
        } catch (\Exception $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            $this->set_error($response['message']);
        }

        return false;
    }
}
