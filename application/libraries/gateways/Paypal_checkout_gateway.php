<?php

defined('BASEPATH') or exit('No direct script access allowed');


use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;

class Paypal_checkout_gateway extends App_gateway
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
        $this->setId('paypal_checkout');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Paypal Smart Checkout');

        /**
         * Add gateway settings
        */
        $this->setSettings(
            [
                [
                    'name'  => 'client_id',
                    'label' => 'Client ID',
                ],
                [
                    'name'      => 'secret',
                    'encrypted' => true,
                    'label'     => 'Secret',
                ],
               [
                    'name'             => 'payment_description',
                    'label'            => 'settings_paymentmethod_description',
                    'type'             => 'textarea',
                    'default_value'    => 'Payment for Invoice {invoice_number}',
                    'field_attributes' => ['maxlength' => 127],
                ],
                [
                    'name'          => 'currencies',
                    'label'         => 'settings_paymentmethod_currencies',
                    'default_value' => 'USD,CAD,EUR',
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
     * Determine and create the PHP Client Environment
     * @return object
     */
    public function environment()
    {
        $clientId     = $this->getSetting('client_id');
        $clientSecret = $this->decryptSetting('secret');

        if ($this->getSetting('test_mode_enabled') == '1') {
            return new SandboxEnvironment($clientId, $clientSecret);
        }

        return new ProductionEnvironment($clientId, $clientSecret);
    }

    /**
     * Creates the Paypal SDK Client
     * @return object (PayPalHttpClient)
     */
    public function client()
    {
        return new PayPalHttpClient($this->environment());
    }

    public function get_styling_button_params()
    {
        $data = hooks()->apply_filters('paypal_checkout_button_style_params', [
            'color' => 'blue',
        ]);

        return array_to_object($data);
    }

    public function get_order_create_data($invoice, $total)
    {
        $payer = $this->get_billing_info($invoice);

        if (count($payer) > 0) {
            $data['payer'] = $payer;
        }

        $data['application_context'] = [
            'payment_method' => [
                'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED',
            ],
        ];

        $data['purchase_units'] = [
            [
                'amount' => [
                         'value'         => number_format($total, 2, '.', ''),
                         'currency_code' => $invoice->currency_name,
                    ],
            ],
        ];

        $description = str_replace('{invoice_number}', format_invoice_number($invoice->id), $this->getSetting('payment_description'));

        if (!empty($description)) {
            $data['purchase_units'][0]['description'] = $description;
        }

        $data = hooks()->apply_filters('paypal_checkout_order_create_data', $data);

        return array_to_object($data);
    }

    private function get_billing_info($invoice)
    {
        $country = null;
        if ($invoice->billing_country) {
            $country = get_country($invoice->billing_country);
        }

        /*
            The highest level sub-division in a country, which is usually a province, state, or ISO-3166-2 subdivision. Format for postal delivery. For example, CA and not California. Value, by country, is:
            UK. A county.
            US. A state.
            Canada. A province.
            Japan. A prefecture.
            Switzerland. A kanton.
         */

        $admin_area_1 = null; // State

        if ($country) {
            if ($country->iso2 == 'UK') {
                $admin_area_1 = $invoice->billing_city;
            } elseif ($country->iso2 == 'US') {
                $admin_area_1 = $invoice->billing_state;
            }
        }

        $payer = [];

        $billing_address = [];

        if (!empty($invoice->billing_street)) {
            $billing_address['address_line_1'] = clear_textarea_breaks($invoice->billing_street); // street address
        }

        if (!empty($admin_area_1)) {
            $billing_address['admin_area_1'] = $admin_area_1;
        }

        if (!empty($invoice->billing_city)) {
            $billing_address['admin_area_2'] = $invoice->billing_city; // city
        }

        if (!empty($invoice->billing_zip)) {
            $billing_address['postal_code'] = $invoice->billing_zip; // postal code
        }

        if ($country) {
            $billing_address['country_code'] = $country->iso2; // country code
        }

        $name = [
                    'given_name' => (is_client_logged_in() ? $GLOBALS['contact']->firstname : null),
                    'surname'    => (is_client_logged_in() ? $GLOBALS['contact']->lastname : null),
                ];

        if (!empty($name['given_name'])) {
            $payer['name'] = $name;
        }

        $email_address = (is_client_logged_in() ? $GLOBALS['contact']->email  : null);

        if ($email_address) {
            $payer['email_address'] = $email_address;
        }

        if (count($billing_address) > 0) {
            $payer['address'] = $billing_address;
        }

        $data = hooks()->apply_filters('paypal_checkout_payer_data', $payer, $invoice);

        return $data;
    }

    /**
     * REQUIRED FUNCTION
     * @param  array $data
     * @return mixed
     */
    public function process_payment($data)
    {
        $redirectGatewayURI = 'gateways/paypal_checkout/payment/' . $data['invoiceid'] . '/' . $data['invoice']->hash;

        $redirectPath = $redirectGatewayURI . '?total=' . $data['amount'];

        redirect(site_url($redirectPath));
    }
}
