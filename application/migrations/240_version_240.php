<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_240 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        $this->db->query('ALTER TABLE `' . db_prefix() . 'departments` CHANGE `imap_username` `imap_username` VARCHAR(191) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;');

        $this->db->query('ALTER TABLE `' . db_prefix() . 'subscriptions` ADD `in_test_environment` INT NULL DEFAULT NULL AFTER `date_subscribed`;');
        $this->db->query('ALTER TABLE `' . db_prefix() . 'subscriptions` ADD `terms` TEXT NULL DEFAULT NULL AFTER `date`;');
        $this->db->query('ALTER TABLE `' . db_prefix() . 'subscriptions` ADD `stripe_tax_id` VARCHAR(50) NULL AFTER `tax_id`;');

        $this->db->query("INSERT INTO `".db_prefix()."emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('subscriptions', 'subscription-payment-requires-action', 'english', 'Credit Card Authorization Required - SCA', 'Important: Confirm your subscription {subscription_name} payment', '<p>Hello {contact_firstname}</p>\r\n<p><strong>Your bank sometimes requires an additional step to make sure an online transaction was authorized.</strong><br /><br />Because of European regulation to protect consumers, many online payments now require two-factor authentication. Your bank ultimately decides when authentication is required to confirm a payment, but you may notice this step when you start paying for a service or when the cost changes.<br /><br />In order to pay the subscription <strong>{subscription_name}</strong>, you will need to&nbsp;confirm your payment by clicking on the follow link: <strong><a href=\"{subscription_authorize_payment_link}\">{subscription_authorize_payment_link}</a></strong><br /><br />To view the subscription, please click at the following link: <a href=\"{subscription_link}\"><span>{subscription_link}</span></a><br />or you can login in our dedicated area here: <a href=\"{crm_url}/login\">{crm_url}/login</a> in case you want to update your credit card or view the subscriptions you are subscribed.<br /><br />Best Regards,<br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0);");

        try {
            if (!empty($this->stripe_gateway->decryptSetting('api_secret_key'))) {
                $this->load->library('stripe_core');
                $this->load->model('subscriptions_model');

                $endpoints = $this->stripe_core->list_webhook_endpoints();
                foreach ($endpoints->data as $endpoint) {
                    if ($endpoint->url == site_url('gateways/stripe/webhook/' . $this->stripe_gateway->getSetting('webhook_key'))) {
                        $endpoint->delete();
                    } elseif ($endpoint->url == site_url('gateways/stripe_ideal/webhook/' . $this->stripe_ideal_gateway->getSetting('webhook_key'))) {
                        $endpoint->delete();
                    }
                }

                $subscriptionsWithTaxes = $this->subscriptions_model->get(['tax_id !=' => 0]);
                $stripeTaxes            = $this->stripe_core->get_tax_rates();

                foreach ($subscriptionsWithTaxes as $subscription) {
                    foreach ($stripeTaxes->data as $stripeTax) {
                        if ($stripeTax->display_name == $subscription['tax_name'] && number_format($stripeTax->percentage, get_decimal_places()) == number_format($subscription['tax_percent'], get_decimal_places())) {
                            $this->db->where('id', $subscription['id']);
                            $this->db->update('subscriptions', ['stripe_tax_id' => $stripeTax->id]);
                        } elseif (empty($stripeTax->display_name) || $stripeTax->display_name == 'Tax') {
                            if ($subscription['tax_percent'] == number_format($stripeTax->percentage, get_decimal_places())) {
                                $this->db->where('id', $subscription['id']);
                                $this->db->update('subscriptions', ['stripe_tax_id' => $stripeTax->id]);
                            }
                        }
                    }
                }

                $this->stripe_core->create_webhook();
            }
        } catch (Exception $e) {
        }

        try {
            if (!empty($this->stripe_ideal_gateway->decryptSetting('api_secret_key'))) {
                $this->stripe_ideal_gateway->create_webhook();
            }
        } catch (Exception $e) {
        }

        delete_option('paymentmethod_stripe_ideal_webhook_key');
        delete_option('paymentmethod_stripe_webhook_key');
    }
}
