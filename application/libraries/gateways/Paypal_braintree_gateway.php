<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Omnipay\Omnipay;

class Paypal_braintree_gateway extends App_gateway
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
        $this->setId('paypal_braintree');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Braintree');

        /**
         * Add gateway settings
        */
        $this->setSettings([
            [
                'name'      => 'merchant_id',
                'encrypted' => true,
                'label'     => 'paymentmethod_braintree_merchant_id',
            ],
            [
                'name'  => 'api_public_key',
                'label' => 'paymentmethod_braintree_public_key',
            ],
            [
                'name'      => 'api_private_key',
                'encrypted' => true,
                'label'     => 'paymentmethod_braintree_private_key',
            ],
            [
                'name'          => 'currencies',
                'label'         => 'settings_paymentmethod_currencies',
                'default_value' => 'USD',
            ],
            [
                'name'          => 'paypal_enabled',
                'type'          => 'yes_no',
                'default_value' => 1,
                'label'         => 'payment_gateway_enable_paypal',
            ],
            [
                'name'          => 'test_mode_enabled',
                'type'          => 'yes_no',
                'default_value' => 1,
                'label'         => 'settings_paymentmethod_testing_mode',
            ],
        ]);
    }

    public function process_payment($data)
    {
        $redUrl = site_url('gateways/braintree/make_payment?invoiceid='
            . $data['invoiceid']
            . '&total=' . $data['amount']
            . '&hash=' . $data['invoice']->hash);

        redirect($redUrl);
    }

    public function fetch_payment($transaction_id)
    {
        $gateway = Omnipay::create('Braintree');
        $gateway->setMerchantId($this->decryptSetting('merchant_id'));
        $gateway->setPrivateKey($this->decryptSetting('api_private_key'));
        $gateway->setPublicKey($this->getSetting('api_public_key'));
        $gateway->setTestMode($this->getSetting('test_mode_enabled'));

        return $gateway->find(['transactionReference' => $transaction_id])->send();
    }

    public function generate_token()
    {
        $gateway = Omnipay::create('Braintree');
        $gateway->setMerchantId($this->decryptSetting('merchant_id'));
        $gateway->setPrivateKey($this->decryptSetting('api_private_key'));
        $gateway->setPublicKey($this->getSetting('api_public_key'));
        $gateway->setTestMode($this->getSetting('test_mode_enabled'));

        return $gateway->clientToken()->send()->getToken();
    }

    public function finish_payment($data)
    {
        // Process online for PayPal payment start
        $gateway = Omnipay::create('Braintree');
        $gateway->setMerchantId($this->decryptSetting('merchant_id'));
        $gateway->setPrivateKey($this->decryptSetting('api_private_key'));
        $gateway->setPublicKey($this->getSetting('api_public_key'));
        $gateway->setTestMode($this->getSetting('test_mode_enabled'));

        $response = $gateway->purchase([
            'amount'   => number_format($data['amount'], 2, '.', ''),
            'currency' => $data['currency'],
            'token'    => $data['payment_method_nonce'],
            ])->send();

        return $response;
    }
}
