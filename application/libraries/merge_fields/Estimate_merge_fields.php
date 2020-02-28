<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Estimate_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
                [
                    'name'      => 'Estimate Link',
                    'key'       => '{estimate_link}',
                    'available' => [
                        'estimate',
                    ],
                ],
                [
                    'name'      => 'Estimate Number',
                    'key'       => '{estimate_number}',
                    'available' => [
                        'estimate',
                    ],
                ],
                [
                    'name'      => 'Reference no.',
                    'key'       => '{estimate_reference_no}',
                    'available' => [
                        'estimate',
                    ],
                ],
                [
                    'name'      => 'Estimate Expiry Date',
                    'key'       => '{estimate_expirydate}',
                    'available' => [
                        'estimate',
                    ],
                ],
                [
                    'name'      => 'Estimate Date',
                    'key'       => '{estimate_date}',
                    'available' => [
                        'estimate',
                    ],
                ],
                [
                    'name'      => 'Estimate Status',
                    'key'       => '{estimate_status}',
                    'available' => [
                        'estimate',
                    ],
                ],
                [
                    'name'      => 'Estimate Sale Agent',
                    'key'       => '{estimate_sale_agent}',
                    'available' => [
                        'estimate',
                    ],
                ],
                [
                    'name'      => 'Estimate Total',
                    'key'       => '{estimate_total}',
                    'available' => [
                        'estimate',
                    ],
                ],
                [
                    'name'      => 'Estimate Subtotal',
                    'key'       => '{estimate_subtotal}',
                    'available' => [
                        'estimate',
                    ],
                ],
            ];
    }

    /**
     * Merge fields for estimates
     * @param  mixed $estimate_id estimate id
     * @return array
     */
    public function format($estimate_id)
    {
        $fields = [];
        $this->ci->db->where('id', $estimate_id);
        $estimate = $this->ci->db->get(db_prefix().'estimates')->row();

        if (!$estimate) {
            return $fields;
        }

        $currency = get_currency($estimate->currency);

        $fields['{estimate_sale_agent}']   = get_staff_full_name($estimate->sale_agent);
        $fields['{estimate_total}']        = app_format_money($estimate->total, $currency);
        $fields['{estimate_subtotal}']     = app_format_money($estimate->subtotal, $currency);
        $fields['{estimate_link}']         = site_url('estimate/' . $estimate_id . '/' . $estimate->hash);
        $fields['{estimate_number}']       = format_estimate_number($estimate_id);
        $fields['{estimate_reference_no}'] = $estimate->reference_no;
        $fields['{estimate_expirydate}']   = _d($estimate->expirydate);
        $fields['{estimate_date}']         = _d($estimate->date);
        $fields['{estimate_status}']       = format_estimate_status($estimate->status, '', false);

        $custom_fields = get_custom_fields('estimate');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($estimate_id, $field['id'], 'estimate');
        }

        return hooks()->apply_filters('estimate_merge_fields', $fields, [
        'id'       => $estimate_id,
        'estimate' => $estimate,
     ]);
    }
}
