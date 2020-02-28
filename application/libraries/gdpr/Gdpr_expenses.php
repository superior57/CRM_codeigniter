<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Gdpr_expenses
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function export($customer_id)
    {
        if (!class_exists('expenses_model')) {
            $this->ci->load->model('expenses_model');
        }

        $this->ci->db->where('clientid', $customer_id);
        $expenses = $this->ci->db->get(db_prefix().'expenses')->result_array();

        $this->ci->db->where('fieldto', 'expenses');
        $this->ci->db->order_by('field_order', 'asc');
        $custom_fields = $this->ci->db->get(db_prefix().'customfields')->result_array();

        $this->ci->load->model('currencies_model');
        foreach ($expenses as $expensesKey => $expense) {
            $expenses[$expensesKey]['currency'] = $this->ci->currencies_model->get($expense['currency']);
            $expenses[$expensesKey]['category'] = $this->ci->expenses_model->get_category($expense['category']);
            $expenses[$expensesKey]['tax']      = get_tax_by_id($expense['tax']);
            $expenses[$expensesKey]['tax2']     = get_tax_by_id($expense['tax2']);

            $expenses[$expensesKey]['additional_fields'] = [];

            foreach ($custom_fields as $cf) {
                $expenses[$expensesKey]['additional_fields'][] = [
                    'name'  => $cf['name'],
                    'value' => get_custom_field_value($expense['id'], $cf['id'], 'expenses'),
                ];
            }
        }

        return $expenses;
    }
}
