<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Instamojo_gateway extends App_gateway
{
    private $sandbox_endpoint = 'https://test.instamojo.com/api/1.1/';

    private $production_endpoint = 'https://www.instamojo.com/api/1.1/';

    public function __construct()
    {
        /**
         * Call App_gateway __construct function
         */
        parent::__construct();
        /**
         * REQUIRED
         * Gateway unique id
         * The ID must be alpha/alphanumeric
         */
        $this->setId('instamojo');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Instamojo');

        /**
         * Add gateway settings
        */
        $this->setSettings(
        [
            [
                'name'      => 'api_key',
                'encrypted' => true,
                'label'     => 'Private API Key',
                ],
            [
                'name'      => 'auth_token',
                'encrypted' => true,
                'label'     => 'Private Auth Token',
                ],
             [
                'name'          => 'description_dashboard',
                'label'         => 'settings_paymentmethod_description',
                'type'          => 'textarea',
                'default_value' => 'Payment for Invoice {invoice_number}',
            ],
            [
                'name'             => 'currencies',
                'label'            => 'settings_paymentmethod_currencies',
                'default_value'    => 'INR',
                'field_attributes' => ['disabled' => true],
                ],
            [
                'name'          => 'test_mode_enabled',
                'type'          => 'yes_no',
                'default_value' => 1,
                'label'         => 'settings_paymentmethod_testing_mode',
                ],
            ]
        );
    }

    /**
     * REQUIRED FUNCTION
     * @param  array $data
     * @return mixed
     */
    public function process_payment($data)
    {
        $gateway = $this->createApi();

        try {
            $request = [
                'purpose'      => str_replace('{invoice_number}', format_invoice_number($data['invoice']->id), $this->getSetting('description_dashboard')),
                'amount'       => number_format($data['amount'], 2, '.', ''),
                'redirect_url' => site_url('gateways/instamojo/redirect/' . $data['invoice']->id . '/' . $data['invoice']->hash),
                ];

            $buyer_name  = null;
            $email       = null;
            $phonenumber = null;

            if (is_client_logged_in()) {
                $contact    = $this->ci->clients_model->get_contact(get_contact_user_id());
                $buyer_name = $contact->firstname . ' ' . $contact->lastname;
                if ($contact->email) {
                    $email = $contact->email;
                }
                if ($contact->phonenumber) {
                    $phonenumber = $contact->phonenumber;
                }
            } else {
                $contacts = $this->ci->clients_model->get_contacts($data['invoice']->clientid);
                if (count($contacts) == 1) {
                    $contact    = $contacts[0];
                    $buyer_name = $contact['firstname'] . ' ' . $contact['lastname'];
                    if ($contact['email']) {
                        $email = $contact['email'];
                    }
                    if ($contact['phonenumber']) {
                        $phonenumber = $contact['phonenumber'];
                    }
                }
            }

            $request['buyer_name'] = $buyer_name;
            $request['email']      = $email;
            $request['phone']      = $phonenumber;

            $response = $gateway->paymentRequestCreate($request);
            redirect($response['longurl']);
            die;
        } catch (Exception $e) {
            $errors = json_decode($e->getMessage());

            if (is_array($errors)) {
                foreach ($errors as $err) {
                    set_alert('warning', $err[0]);

                    break;
                }
            } else {
                set_alert('warning', $errors);
            }

            redirect(site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash));
        }
    }

    public function createApi()
    {
        return new \Instamojo\Instamojo(
            $this->decryptSetting('api_key'),
            $this->decryptSetting('auth_token'),
            $this->getEndpoint()
        );
    }

    private function getEndpoint()
    {
        return $this->getSetting('test_mode_enabled') == '1' ? $this->sandbox_endpoint : $this->production_endpoint;
    }
}
