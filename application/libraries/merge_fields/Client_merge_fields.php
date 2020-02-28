<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Client_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
                [
                    'name'      => 'Contact Firstname',
                    'key'       => '{contact_firstname}',
                    'available' => [
                        'client',
                        'ticket',
                        'invoice',
                        'estimate',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                       'templates' => [
                        'gdpr-removal-request',
                        'contract-expiration',
                         'send-contract',
                          'contract-comment-to-client',
                         'task-added-attachment-to-contacts',
                         'task-commented-to-contacts',
                         'task-status-change-to-contacts',

                    ],
                ],
                [
                    'name'      => 'Contact Lastname',
                    'key'       => '{contact_lastname}',
                    'available' => [
                        'client',
                        'ticket',
                        'invoice',
                        'estimate',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                          'templates' => [
                        'gdpr-removal-request',
                         'contract-expiration',
                          'send-contract',
                           'contract-comment-to-client',
                           'task-added-attachment-to-contacts',
                           'task-commented-to-contacts',
                           'task-status-change-to-contacts',
                    ],
                ],
                [
                    'name'      => 'Contact Phone Number',
                    'key'       => '{contact_phonenumber}',
                    'available' => [
                        'client',
                        'ticket',
                        'invoice',
                        'estimate',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                        'templates' => [
                        'gdpr-removal-request',
                        'contract-expiration',
                         'send-contract',
                          'contract-comment-to-client',
                    ],
                ],
                [
                    'name'      => 'Contact Email',
                    'key'       => '{contact_email}',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                       'templates' => [
                        'gdpr-removal-request',
                        'contract-expiration',
                         'send-contract',
                          'contract-comment-to-client',
                    ],
                ],
                   [
                    'name'      => 'Set New Password URL',
                    'key'       => '{set_password_url}',
                    'available' => [
                    ],
                    'templates' => [
                        'contact-set-password',
                    ],
                ],
                [
                    'name'      => 'Email Verification URL',
                    'key'       => '{email_verification_url}',
                    'available' => [
                    ],
                    'templates' => [
                        'contact-verification-email',
                    ],
                ],
                [
                    'name'      => 'Reset Password URL',
                    'key'       => '{reset_password_url}',
                    'available' => [
                    ],
                    'templates' => [
                        'contact-forgot-password',
                    ],
                ],
                [
                    'name'      => is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1' ? 'Contact Public Consent URL' : '',
                    'key'       => is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1' ? '{contact_public_consent_url}' : '',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                          'templates' => [
                        'gdpr-removal-request',
                        'contract-expiration',
                        'send-contract',
                         'contract-comment-to-client',

                    ],
                ],
                [
                    'name'      => 'Client Company',
                    'key'       => '{client_company}',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'contract',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                          'templates' => [
                        'gdpr-removal-request',
                    ],
                ],
                [
                    'name'      => 'Client Phone Number',
                    'key'       => '{client_phonenumber}',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'contract',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                          'templates' => [
                        'gdpr-removal-request',
                    ],
                ],
                [
                    'name'      => 'Client Country',
                    'key'       => '{client_country}',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'contract',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                          'templates' => [
                        'gdpr-removal-request',
                    ],
                ],
                [
                    'name'      => 'Client City',
                    'key'       => '{client_city}',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'contract',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                ],
                [
                    'name'      => 'Client Zip',
                    'key'       => '{client_zip}',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'contract',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                ],
                [
                    'name'      => 'Client State',
                    'key'       => '{client_state}',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'contract',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                ],
                [
                    'name'      => 'Client Address',
                    'key'       => '{client_address}',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'contract',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                ],
                [
                    'name'      => 'Client Vat Number',
                    'key'       => '{client_vat_number}',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'contract',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                ],
                [
                    'name'      => 'Client ID',
                    'key'       => '{client_id}',
                    'available' => [
                        'client',
                        'invoice',
                        'estimate',
                        'ticket',
                        'contract',
                        'project',
                        'credit_note',
                        'subscriptions',
                    ],
                ],
                [
                    'name'      => 'Password',
                    'key'       => '{password}',
                    'available' => [
                    ],
                    'templates' => [
                        'new-client-created',
                    ],
                ],
                [
                    'name'      => 'Statement From',
                    'key'       => '{statement_from}',
                    'available' => [

                    ],
                    'templates' => [
                        'client-statement',
                    ],
                ],
                [
                    'name'      => 'Statement To',
                    'key'       => '{statement_to}',
                    'available' => [

                    ],
                    'templates' => [
                        'client-statement',
                    ],
                ],
                [
                    'name'      => 'Statement Balance Due',
                    'key'       => '{statement_balance_due}',
                    'available' => [

                    ],
                    'templates' => [
                        'client-statement',
                    ],
                ],
                [
                    'name'      => 'Statement Amount Paid',
                    'key'       => '{statement_amount_paid}',
                    'available' => [

                    ],
                    'templates' => [
                        'client-statement',
                    ],
                ],
                [
                    'name'      => 'Statement Invoiced Amount',
                    'key'       => '{statement_invoiced_amount}',
                    'available' => [

                    ],
                    'templates' => [
                        'client-statement',
                    ],
                ],
                [
                    'name'      => 'Statement Beginning Balance',
                    'key'       => '{statement_beginning_balance}',
                    'available' => [

                    ],
                    'templates' => [
                        'client-statement',
                    ],
                ],
                [
                    'name'      => 'Customer Files Admin Link',
                    'key'       => '{customer_profile_files_admin_link}',
                    'available' => [

                    ],
                    'templates' => [
                        'new-customer-profile-file-uploaded-to-staff',
                    ],
                ],
            ];
    }

    /**
     * Merge fields for Contacts and Customers
     * @param  mixed $client_id
     * @param  string $contact_id
     * @param  string $password   password is used when sending welcome email, only 1 time
     * @return array
     */
    public function format($client_id, $contact_id = '', $password = '')
    {
        $fields = [];

        if ($contact_id == '') {
            $contact_id = get_primary_contact_user_id($client_id);
        }

        $fields['{contact_firstname}']                 = '';
        $fields['{contact_lastname}']                  = '';
        $fields['{contact_email}']                     = '';
        $fields['{contact_phonenumber}']               = '';
        $fields['{client_company}']                    = '';
        $fields['{client_phonenumber}']                = '';
        $fields['{client_country}']                    = '';
        $fields['{client_city}']                       = '';
        $fields['{client_zip}']                        = '';
        $fields['{client_state}']                      = '';
        $fields['{client_address}']                    = '';
        $fields['{password}']                          = '';
        $fields['{client_vat_number}']                 = '';
        $fields['{contact_public_consent_url}']        = '';
        $fields['{email_verification_url}']            = '';
        $fields['{customer_profile_files_admin_link}'] = '';

        if ($client_id == '') {
            return $fields;
        }

        $client = $this->ci->clients_model->get($client_id);

        if (!$client) {
            return $fields;
        }

        $this->ci->db->where('userid', $client_id);
        $this->ci->db->where('id', $contact_id);
        $contact = $this->ci->db->get(db_prefix().'contacts')->row();

        if ($contact) {
            $fields['{contact_firstname}']          = $contact->firstname;
            $fields['{contact_lastname}']           = $contact->lastname;
            $fields['{contact_email}']              = $contact->email;
            $fields['{contact_phonenumber}']        = $contact->phonenumber;
            $fields['{contact_public_consent_url}'] = contact_consent_url($contact->id);
            $fields['{email_verification_url}']     = site_url('verification/verify/' . $contact->id . '/' . $contact->email_verification_key);
        }

        if (!empty($client->vat)) {
            $fields['{client_vat_number}'] = $client->vat;
        }

        $fields['{customer_profile_files_admin_link}'] = admin_url('clients/client/' . $client->userid . '?group=attachments');
        $fields['{client_company}']                    = $client->company;
        $fields['{client_phonenumber}']                = $client->phonenumber;
        $fields['{client_country}']                    = get_country_short_name($client->country);
        $fields['{client_city}']                       = $client->city;
        $fields['{client_zip}']                        = $client->zip;
        $fields['{client_state}']                      = $client->state;
        $fields['{client_address}']                    = $client->address;
        $fields['{client_id}']                         = $client_id;

        if ($password != '') {
            $fields['{password}'] = $password;
        }

        $custom_fields = get_custom_fields('customers');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($client_id, $field['id'], 'customers');
        }

        $custom_fields = get_custom_fields('contacts');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($contact_id, $field['id'], 'contacts');
        }

        return hooks()->apply_filters('client_contact_merge_fields', $fields, [
        'customer_id' => $client_id,
        'contact_id'  => $contact_id,
        'customer'    => $client,
        'contact'     => $contact,
    ]);
    }

    /**
 * Statement merge fields
 * @param  array $statement
 * @return array
 */
    public function statement($statement)
    {
        $fields = [];

        $fields['{statement_from}']              = _d($statement['from']);
        $fields['{statement_to}']                = _d($statement['to']);
        $fields['{statement_balance_due}']       = app_format_money($statement['balance_due'], $statement['currency']->name);
        $fields['{statement_amount_paid}']       = app_format_money($statement['amount_paid'], $statement['currency']->name);
        $fields['{statement_invoiced_amount}']   = app_format_money($statement['invoiced_amount'], $statement['currency']->name);
        $fields['{statement_beginning_balance}'] = app_format_money($statement['beginning_balance'], $statement['currency']->name);

        return hooks()->apply_filters('client_statement_merge_fields', $fields, [
            'statement' => $statement,
         ]);
    }

    /**
     * Password merge fields
     * @param  array $data
     * @param  string $type  template type
     * @return array
     */
    public function password($data, $type)
    {
        $fields['{reset_password_url}'] = '';
        $fields['{set_password_url}']   = '';

        if ($type == 'forgot') {
            $fields['{reset_password_url}'] = site_url('authentication/reset_password/0/' . $data['userid'] . '/' . $data['new_pass_key']);
        } elseif ($type == 'set') {
            $fields['{set_password_url}'] = site_url('authentication/set_password/0/' . $data['userid'] . '/' . $data['new_pass_key']);
        }

        return $fields;
    }
}
