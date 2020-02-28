<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Proposals_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
                [
                    'name'      => 'Proposal ID',
                    'key'       => '{proposal_id}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Proposal Number',
                    'key'       => '{proposal_number}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Subject',
                    'key'       => '{proposal_subject}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Proposal Total',
                    'key'       => '{proposal_total}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Proposal Subtotal',
                    'key'       => '{proposal_subtotal}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Open Till',
                    'key'       => '{proposal_open_till}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Proposal Assigned',
                    'key'       => '{proposal_assigned}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Company Name',
                    'key'       => '{proposal_proposal_to}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Address',
                    'key'       => '{proposal_address}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'City',
                    'key'       => '{proposal_city}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'State',
                    'key'       => '{proposal_state}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Zip Code',
                    'key'       => '{proposal_zip}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Country',
                    'key'       => '{proposal_country}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Email',
                    'key'       => '{proposal_email}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Phone',
                    'key'       => '{proposal_phone}',
                    'available' => [
                        'proposals',
                    ],
                ],
                [
                    'name'      => 'Proposal Link',
                    'key'       => '{proposal_link}',
                    'available' => [
                        'proposals',
                    ],
                ],
            ];
    }

    /**
 * Merge fields for proposals
 * @param  mixed $proposal_id proposal id
 * @return array
 */
    public function format($proposal_id)
    {
        $fields = [];
        $this->ci->db->where('id', $proposal_id);
        $this->ci->db->join(db_prefix() . 'countries', db_prefix() . 'countries.country_id=' . db_prefix() . 'proposals.country', 'left');
        $proposal = $this->ci->db->get(db_prefix() . 'proposals')->row();


        if (!$proposal) {
            return $fields;
        }

        if ($proposal->currency != 0) {
            $currency = get_currency($proposal->currency);
        } else {
            $currency = get_base_currency();
        }

        $fields['{proposal_id}']          = $proposal_id;
        $fields['{proposal_number}']      = format_proposal_number($proposal_id);
        $fields['{proposal_link}']        = site_url('proposal/' . $proposal_id . '/' . $proposal->hash);
        $fields['{proposal_subject}']     = $proposal->subject;
        $fields['{proposal_total}']       = app_format_money($proposal->total, $currency);
        $fields['{proposal_subtotal}']    = app_format_money($proposal->subtotal, $currency);
        $fields['{proposal_open_till}']   = _d($proposal->open_till);
        $fields['{proposal_proposal_to}'] = $proposal->proposal_to;
        $fields['{proposal_address}']     = $proposal->address;
        $fields['{proposal_email}']       = $proposal->email;
        $fields['{proposal_phone}']       = $proposal->phone;

        $fields['{proposal_city}']     = $proposal->city;
        $fields['{proposal_state}']    = $proposal->state;
        $fields['{proposal_zip}']      = $proposal->zip;
        $fields['{proposal_country}']  = $proposal->short_name;
        $fields['{proposal_assigned}'] = get_staff_full_name($proposal->assigned);

        $custom_fields = get_custom_fields('proposal');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($proposal_id, $field['id'], 'proposal');
        }

        return hooks()->apply_filters('proposal_merge_fields', $fields, [
        'id'       => $proposal_id,
        'proposal' => $proposal,
     ]);
    }
}
