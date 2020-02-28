<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Omnipay\Omnipay;

class Paypal_gateway extends App_gateway
{
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
        $this->setId('paypal');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Paypal');

        /**
         * Add gateway settings
        */
        $this->setSettings(
        [
            [
                'name'      => 'username',
                'encrypted' => true,
                'label'     => 'settings_paymentmethod_paypal_username',
                ],
            [
                'name'      => 'password',
                'encrypted' => true,
                'label'     => 'settings_paymentmethod_paypal_password',
                ],
            [
                'name'      => 'signature',
                'encrypted' => true,
                'label'     => 'settings_paymentmethod_paypal_signature',
                ],
             [
                'name'          => 'description_dashboard',
                'label'         => 'settings_paymentmethod_description',
                'type'          => 'textarea',
                'default_value' => 'Payment for Invoice {invoice_number}',
            ],
            [
                'name'          => 'currencies',
                'label'         => 'settings_paymentmethod_currencies',
                'default_value' => 'EUR,USD',
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
        // Process online for PayPal payment start
        $gateway = Omnipay::create('PayPal_Express');

        $gateway->setUsername($this->decryptSetting('username'));
        $gateway->setPassword($this->decryptSetting('password'));
        $gateway->setSignature($this->decryptSetting('signature'));

        $gateway->setTestMode($this->getSetting('test_mode_enabled'));

        $logoURL = payment_gateway_logo_url();

        if ($logoURL != '' && startsWith(site_url(), 'https://')) {
            $gateway->setlogoImageUrl(hooks()->apply_filters('paypal_logo_url', $logoURL));
        }

        $gateway->setbrandName(get_option('companyname'));

        $request_data = [
            'amount'      => number_format($data['amount'], 2, '.', ''),
            'returnUrl'   => site_url('gateways/paypal/complete_purchase?hash=' . $data['invoice']->hash . '&invoiceid=' . $data['invoiceid']),
            'cancelUrl'   => site_url('invoice/' . $data['invoiceid'] . '/' . $data['invoice']->hash),
            'currency'    => $data['invoice']->currency_name,
            'description' => str_replace('{invoice_number}', format_invoice_number($data['invoice']->id), $this->getSetting('description_dashboard')),
            ];

        try {
            $response = $gateway->purchase($request_data)->send();
            if ($response->isRedirect()) {
                $this->ci->session->set_userdata([
                    'online_payment_amount' => number_format($data['amount'], 2, '.', ''),
                    'currency'              => $data['invoice']->currency_name,
                    ]);
                // Add the token to database
                $this->ci->db->where('id', $data['invoiceid']);
                $this->ci->db->update(db_prefix().'invoices', [
                    'token' => $response->getTransactionReference(),
                ]);
                $response->redirect();
            } else {
                exit($response->getMessage());
            }
        } catch (\Exception $e) {
            echo $e->getMessage() . '<br />';
            exit('Sorry, there was an error processing your payment. Please try again later.');
        }
    }

    /**
     * Custom function to complete the payment after user is returned from paypal
     * @param  array $data
     * @return mixed
     */
    public function complete_purchase($data)
    {
        $gateway = Omnipay::create('PayPal_Express');
        $gateway->setUsername($this->decryptSetting('username'));
        $gateway->setPassword($this->decryptSetting('password'));
        $gateway->setSignature($this->decryptSetting('signature'));
        $gateway->setTestMode($this->getSetting('test_mode_enabled'));

        $response = $gateway->completePurchase([
            'transactionReference' => $data['token'],
            'payerId'              => $this->ci->input->get('PayerID'),
            'amount'               => $data['amount'],
            'currency'             => $data['currency'],
            ])->send();

        $paypalResponse = $response->getData();

        return $paypalResponse;
    }
}
