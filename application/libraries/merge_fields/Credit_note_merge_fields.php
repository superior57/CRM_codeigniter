<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Credit_note_merge_fields extends App_merge_fields
{
    public function build()
    {
        return  [
                [
                    'name'      => 'Credit Note Number',
                    'key'       => '{credit_note_number}',
                    'available' => [
                        'credit_note',
                    ],
                ],
                [
                    'name'      => 'Date',
                    'key'       => '{credit_note_date}',
                    'available' => [
                        'credit_note',
                    ],
                ],
                [
                    'name'      => 'Status',
                    'key'       => '{credit_note_status}',
                    'available' => [
                        'credit_note',
                    ],
                ],
                [
                    'name'      => 'Total',
                    'key'       => '{credit_note_total}',
                    'available' => [
                        'credit_note',
                    ],
                ],
                [
                    'name'      => 'Subtotal',
                    'key'       => '{credit_note_subtotal}',
                    'available' => [
                        'credit_note',
                    ],
                ],
                [
                    'name'      => 'Credits Used',
                    'key'       => '{credit_note_credits_used}',
                    'available' => [
                        'credit_note',
                    ],
                ],
                [
                    'name'      => 'Credits Remaining',
                    'key'       => '{credit_note_credits_remaining}',
                    'available' => [
                        'credit_note',
                    ],
                ],
            ];
    }

    /**
 * Credit notes merge fields
 * @param  mixed $id credit note id
 * @return array
 */
    public function format($id)
    {
        $fields = [];

        if (!class_exists('credit_notes_model')) {
            $this->ci->load->model('credit_notes_model');
        }

        $credit_note = $this->ci->credit_notes_model->get($id);

        if (!$credit_note) {
            return $fields;
        }

        $fields['{credit_note_number}']            = format_credit_note_number($id);
        $fields['{credit_note_total}']             = app_format_money($credit_note->total, $credit_note->currency_name);
        $fields['{credit_note_subtotal}']          = app_format_money($credit_note->subtotal, $credit_note->currency_name);
        $fields['{credit_note_credits_remaining}'] = app_format_money($credit_note->remaining_credits, $credit_note->currency_name);
        $fields['{credit_note_credits_used}']      = app_format_money($credit_note->credits_used, $credit_note->currency_name);
        $fields['{credit_note_date}']              = _d($credit_note->date);
        $fields['{credit_note_status}']            = format_credit_note_status($credit_note->status, true);

        $custom_fields = get_custom_fields('credit_note');

        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($id, $field['id'], 'credit_note');
        }

        return hooks()->apply_filters('credit_note_merge_fields', $fields, [
            'id'          => $id,
            'credit_note' => $credit_note,
         ]);
    }
}
