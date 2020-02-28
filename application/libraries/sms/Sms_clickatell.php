<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Sms_clickatell extends App_sms
{
    private $api_key;

    private $requestURL = 'https://platform.clickatell.com/messages/http/send';

    public function __construct()
    {
        parent::__construct();

        $this->api_key = $this->get_option('clickatell', 'api_key');

        $this->add_gateway('clickatell', [
            'info'    => "<p>Clickatell SMS integration is one way messaging, means that your customers won't be able to reply to the SMS.</p><hr class='hr-10'>",
            'name'    => 'Clickatell',
            'options' => [
                [
                    'name'  => 'api_key',
                    'label' => 'API Key',
                ],
            ],
        ]);
    }

    public function send($number, $message)
    {
        try {
            $response = $this->client->request('GET', $this->requestURL, [
                'headers' => [
                    'X-Version' => '1',
                ],
                'query' => [
                    'apiKey'  => $this->api_key,
                    'to'      => $number,
                    'content' => $message,
                ],
            ]);

            $result = json_decode($response->getBody());
            $error  = false;

            if ($result) {
                if (isset($result->messages[0]->accepted) && $result->messages[0]->accepted == true) {
                    log_activity('SMS sent via Clickatell to ' . $number . ', Message: ' . $message);

                    return true;
                } elseif (isset($result->messages) && isset($result->error)) {
                    $error = $result->error;
                } elseif (isset($result->messages[0]->error) && $result->messages[0]->error != null) {
                    $error = $result->messages[0]->error;
                }
            }
        } catch (\Exception $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            $error    = $response['message'];
        }

        if ($error !== false && $error !== null) {
            $this->set_error($error);
        }

        return false;
    }
}
