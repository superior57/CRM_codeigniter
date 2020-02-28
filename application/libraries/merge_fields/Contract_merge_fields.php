<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Contract_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
                [
                    'name'      => 'Contract ID',
                    'key'       => '{contract_id}',
                    'available' => [
                        'contract',
                    ],
                ],
                [
                    'name'      => 'Contract Subject',
                    'key'       => '{contract_subject}',
                    'available' => [
                        'contract',
                    ],
                ],
                [
                    'name'      => 'Contract Description',
                    'key'       => '{contract_description}',
                    'available' => [
                        'contract',
                    ],
                ],
                [
                    'name'      => 'Contract Date Start',
                    'key'       => '{contract_datestart}',
                    'available' => [
                        'contract',
                    ],
                ],
                [
                    'name'      => 'Contract Date End',
                    'key'       => '{contract_dateend}',
                    'available' => [
                        'contract',
                    ],
                ],
                [
                    'name'      => 'Contract Value',
                    'key'       => '{contract_contract_value}',
                    'available' => [
                        'contract',
                    ],
                ],
                [
                    'name'      => 'Contract Link',
                    'key'       => '{contract_link}',
                    'available' => [
                        'contract',
                    ],
                ],
            ];
    }

    /**
     * Merge field for contracts
     * @param  mixed $contract_id contract id
     * @return array
     */
    public function format($contract_id)
    {
        $fields = [];
        $this->ci->db->where('id', $contract_id);
        $contract = $this->ci->db->get(db_prefix().'contracts')->row();

        if (!$contract) {
            return $fields;
        }

        $currency = get_base_currency();

        $fields['{contract_id}']             = $contract->id;
        $fields['{contract_subject}']        = $contract->subject;
        $fields['{contract_description}']    = nl2br($contract->description);
        $fields['{contract_datestart}']      = _d($contract->datestart);
        $fields['{contract_dateend}']        = _d($contract->dateend);
        $fields['{contract_contract_value}'] = app_format_money($contract->contract_value, $currency);

        $fields['{contract_link}'] = site_url('contract/' . $contract->id . '/' . $contract->hash);

        $custom_fields = get_custom_fields('contracts');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($contract_id, $field['id'], 'contracts');
        }

        return hooks()->apply_filters('contract_merge_fields', $fields, [
        'id'       => $contract_id,
        'contract' => $contract,
     ]);
    }
}
