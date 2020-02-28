<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Subscriptions_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($where = [])
    {
        $this->select();
        $this->join();
        $this->db->where($where);

        return $this->db->get(db_prefix() . 'subscriptions')->result_array();
    }

    public function get_by_id($id, $where = [])
    {
        $this->select();
        $this->join();
        $this->db->where(db_prefix() . 'subscriptions.id', $id);
        $this->db->where($where);

        return $this->db->get(db_prefix() . 'subscriptions')->row();
    }

    public function get_by_hash($hash, $where = [])
    {
        $this->select();
        $this->join();
        $this->db->where('hash', $hash);
        $this->db->where($where);

        return $this->db->get(db_prefix() . 'subscriptions')->row();
    }

    public function get_child_invoices($id)
    {
        $this->db->select('id');
        $this->db->where('subscription_id', $id);
        $invoices = $this->db->get(db_prefix() . 'invoices')->result_array();
        $child    = [];

        if (!class_exists('Invoices_model')) {
            $this->load->model('invoices_model');
        }

        foreach ($invoices as $invoice) {
            $child[] = $this->invoices_model->get($invoice['id']);
        }

        return $child;
    }

    public function create($data)
    {
        if (isset($data['stripe_tax_id']) && !empty($data['stripe_tax_id'])) {
            $this->load->library('stripe_core');
            $stripe_tax = $this->stripe_core->retrieve_tax_rate($data['stripe_tax_id']);

            $this->db->where('name', $stripe_tax->display_name);
            $this->db->where('taxrate', $percentage = number_format($stripe_tax->percentage, get_decimal_places()));
            $dbTax = $this->db->get('taxes')->row();
            if (!$dbTax) {
                $insert_id = $this->db->insert('taxes', [
                    'name'    => $stripe_tax->display_name,
                    'taxrate' => $percentage,
                ]);
                $data['tax_id'] = $insert_id;
            } else {
                $data['tax_id'] = $dbTax->id;
            }
        } elseif (isset($data['stripe_tax_id']) && !$data['stripe_tax_id']) {
            $data['tax_id']        = 0;
            $data['stripe_tax_id'] = null;
        }

        $this->db->insert(db_prefix() . 'subscriptions', array_merge($data, [
                'created'      => date('Y-m-d H:i:s'),
                'hash'         => app_generate_hash(),
                'created_from' => get_staff_user_id(),
            ]));

        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        if (isset($data['stripe_tax_id']) && !empty($data['stripe_tax_id'])) {
            $this->load->library('stripe_core');
            $stripe_tax = $this->stripe_core->retrieve_tax_rate($data['stripe_tax_id']);

            $this->db->where('name', $stripe_tax->display_name);
            $this->db->where('taxrate', $percentage = number_format($stripe_tax->percentage, get_decimal_places()));
            $dbTax = $this->db->get('taxes')->row();
            if (!$dbTax) {
                $insert_id = $this->db->insert('taxes', [
                    'name'    => $stripe_tax->display_name,
                    'taxrate' => $percentage,
                ]);
                $data['tax_id'] = $insert_id;
            } else {
                $data['tax_id'] = $dbTax->id;
            }
        } elseif (isset($data['stripe_tax_id']) && !$data['stripe_tax_id']) {
            $data['tax_id']        = 0;
            $data['stripe_tax_id'] = null;
        }

        $this->db->where(db_prefix() . 'subscriptions.id', $id);
        $this->db->update(db_prefix() . 'subscriptions', $data);

        return $this->db->affected_rows() > 0;
    }

    private function select()
    {
        $this->db->select(db_prefix() . 'subscriptions.id as id, stripe_tax_id, terms, in_test_environment, date, next_billing_cycle, status, ' . db_prefix() . 'subscriptions.project_id as project_id, description, ' . db_prefix() . 'subscriptions.created_from as created_from, ' . db_prefix() . 'subscriptions.name as name, ' . db_prefix() . 'currencies.name as currency_name, ' . db_prefix() . 'currencies.symbol, currency, clientid, ends_at, date_subscribed, stripe_plan_id,stripe_subscription_id,quantity,hash,description_in_item,' . db_prefix() . 'taxes.name as tax_name, ' . db_prefix() . 'taxes.taxrate as tax_percent, tax_id, stripe_id as stripe_customer_id,' . get_sql_select_client_company());
    }

    private function join()
    {
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id=' . db_prefix() . 'subscriptions.currency');
        $this->db->join(db_prefix() . 'taxes', db_prefix() . 'taxes.id=' . db_prefix() . 'subscriptions.tax_id', 'left');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid=' . db_prefix() . 'subscriptions.clientid');
    }

    public function send_email_template($id, $cc = '', $template = 'subscription_send_to_customer')
    {
        $subscription = $this->get_by_id($id);

        $contact = $this->clients_model->get_contact(get_primary_contact_user_id($subscription->clientid));

        if (!$contact) {
            return false;
        }

        $sent = send_mail_template($template, $subscription, $contact, $cc);

        return $sent ? true : false;
    }

    public function delete($id, $simpleDelete = false)
    {
        $subscription = $this->get_by_id($id);

        if ($subscription->in_test_environment === '0') {
            if (!empty($subscription->stripe_subscription_id) && $simpleDelete == false) {
                return false;
            }
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'subscriptions');

        if ($this->db->affected_rows() > 0) {
            delete_tracked_emails($id, 'subscription');

            $this->db->where('subscription_id');
            $this->db->update('invoices', ['subscription_id' => 0]);

            return $subscription;
        }

        return false;
    }
}
