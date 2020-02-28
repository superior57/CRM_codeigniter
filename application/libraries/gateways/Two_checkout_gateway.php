<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Two_checkout_gateway extends App_gateway
{
    private $required_address_line_2_country_codes = 'CHN, JPN, RUS';

    private $required_state_country_codes = ' ARG, AUS, BGR, CAN, CHN, CYP, EGY, FRA, IND, IDN, ITA, JPN, MYS, MEX, NLD, PAN, PHL, POL, ROU, RUS, SRB, SGP, ZAF, ESP, SWE, THA, TUR, GBR, USA';

    private $required_zip_code_country_codes = 'ARG, AUS, BGR, CAN, CHN, CYP, EGY, FRA, IND, IDN, ITA, JPN, MYS, MEX, NLD, PAN, PHL, POL, ROU, RUS, SRB, SGP, ZAF, ESP, SWE, THA, TUR, GBR, USA';

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
        $this->setId('two_checkout');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('2Checkout');

        /**
         * Add gateway settings
        */
        $this->setSettings([
            [
                'name'  => 'account_number',
                'label' => 'paymentmethod_two_checkout_account_number',
            ],
            [
                'name'      => 'private_key',
                'label'     => 'paymentmethod_two_checkout_private_key',
                'encrypted' => true,
            ],
            [
                'name'  => 'publishable_key',
                'label' => 'paymentmethod_two_checkout_publishable_key',
            ],
            [
                'name'          => 'currencies',
                'label'         => 'settings_paymentmethod_currencies',
                'default_value' => 'USD,EUR',
            ],
            [
                'name'          => 'test_mode_enabled',
                'type'          => 'yes_no',
                'label'         => 'settings_paymentmethod_testing_mode',
                'default_value' => 1,
            ],
        ]);

        /**
         * Add ssl notice
         */
        hooks()->add_action('before_render_payment_gateway_settings', 'two_checkout_ssl_notice');

        $line_address_2_required                     = $this->required_address_line_2_country_codes;
        $this->required_address_line_2_country_codes = [];
        foreach (explode(', ', $line_address_2_required) as $cn_code) {
            array_push($this->required_address_line_2_country_codes, $cn_code);
        }
        $state_country_codes_required       = $this->required_state_country_codes;
        $this->required_state_country_codes = [];
        foreach (explode(', ', $state_country_codes_required) as $cn_code) {
            array_push($this->required_state_country_codes, $cn_code);
        }
        $zip_code_country_codes_required       = $this->required_zip_code_country_codes;
        $this->required_zip_code_country_codes = [];
        foreach (explode(', ', $zip_code_country_codes_required) as $cn_code) {
            array_push($this->required_zip_code_country_codes, $cn_code);
        }
    }

    public function process_payment($data)
    {
        $this->ci->session->set_userdata([
            'total_2checkout' => $data['amount'],
        ]);
        redirect(site_url('gateways/two_checkout/make_payment?invoiceid=' . $data['invoiceid'] . '&hash=' . $data['invoice']->hash));
    }

    public function finish_payment($data)
    {
        Twocheckout::privateKey($this->decryptSetting('private_key'));
        Twocheckout::sellerId($this->getSetting('account_number'));
        Twocheckout::sandbox($this->getSetting('test_mode_enabled') == '1');

        $billingAddress              = [];
        $billingAddress['name']      = $this->ci->input->post('billingName');
        $billingAddress['addrLine1'] = $this->ci->input->post('billingAddress1');

        if ($this->ci->input->post('billingAddress2')) {
            $billingAddress['addrLine2'] = $this->ci->input->post('billingAddress2');
        }
        $billingAddress['city'] = $this->ci->input->post('billingCity');

        if ($this->ci->input->post('billingState')) {
            $billingAddress['state'] = $this->ci->input->post('billingState');
        }
        if ($this->ci->input->post('billingPostcode')) {
            $billingAddress['zipCode'] = $this->ci->input->post('billingPostcode');
        }
        $billingAddress['country'] = $this->ci->input->post('billingCountry');
        $billingAddress['email']   = $this->ci->input->post('email');

        try {
            $charge = Twocheckout_Charge::auth([
                'sellerId'        => $this->getSetting('account_number'),
                'merchantOrderId' => $data['invoice']->id,
                'token'           => $this->ci->input->post('token'),
                'currency'        => $data['currency'],
                'total'           => number_format($data['amount'], 2, '.', ''),
                'billingAddr'     => $billingAddress,
            ]);

            return ['success' => true, 'charge' => $charge];
        } catch (Twocheckout_Error $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function get_required_address_2_by_country_code()
    {
        return $this->required_address_line_2_country_codes;
    }

    public function get_required_state_by_country_code()
    {
        return $this->required_state_country_codes;
    }

    public function get_required_zip_by_country_code()
    {
        return $this->required_zip_code_country_codes;
    }
}

function two_checkout_ssl_notice($gateway)
{
    if ($gateway['id'] == 'two_checkout') {
        echo '<p class="text-warning">' . _l('2checkout_usage_notice') . '</p>';
        echo '<p class="alert alert-warning bold">2Checkout payment gateway is deprecated and will be removed or replaced in future updates.</p>';
    }
}
