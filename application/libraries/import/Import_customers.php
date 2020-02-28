<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once(APPPATH . 'libraries/import/App_import.php');

class Import_customers extends App_import
{
    protected $notImportableFields = [];

    private $countryFields = ['country', 'billing_country', 'shipping_country'];

    protected $requiredFields = ['firstname', 'lastname', 'email'];

    public function __construct()
    {
        $this->notImportableFields = hooks()->apply_filters('not_importable_clients_fields', ['userid', 'id', 'is_primary', 'password', 'datecreated', 'last_ip', 'last_login', 'last_password_change', 'active', 'new_pass_key', 'new_pass_key_requested', 'leadid', 'default_currency', 'profile_image', 'default_language', 'direction', 'show_primary_contact', 'invoice_emails', 'estimate_emails', 'project_emails', 'task_emails', 'contract_emails', 'credit_note_emails', 'ticket_emails', 'addedfrom', 'registration_confirmed', 'last_active_time', 'email_verified_at', 'email_verification_key', 'email_verification_sent_at']);

        if (get_option('company_is_required') == 1) {
            $this->requiredFields[] = 'company';
        }

        $this->addImportGuidelinesInfo('Duplicate email rows won\'t be imported.', true);

        $this->addImportGuidelinesInfo('Make sure you configure the default contact permission in <a href="' . admin_url('settings?group=clients') . '" target="_blank">Setup->Settings->Customers</a> to get the best results like auto assigning contact permissions and email notification settings based on the permission.');

        parent::__construct();
    }

    public function perform()
    {
        $this->initialize();

        $databaseFields      = $this->getImportableDatabaseFields();
        $totalDatabaseFields = count($databaseFields);

        foreach ($this->getRows() as $rowNumber => $row) {
            $insert    = [];
            $duplicate = false;

            for ($i = 0; $i < $totalDatabaseFields; $i++) {
                if (!isset($row[$i])) {
                    continue;
                }

                $row[$i] = $this->checkNullValueAddedByUser($row[$i]);

                if (in_array($databaseFields[$i], $this->requiredFields) && $row[$i] == '' && $databaseFields[$i] != 'company') {
                    $row[$i] = '/';
                } elseif (in_array($databaseFields[$i], $this->countryFields)) {
                    $row[$i] = $this->countryValue($row[$i]);
                } elseif ($databaseFields[$i] == 'email') {
                    $duplicate = $this->isDuplicateContact($row[$i]);
                } elseif ($databaseFields[$i] == 'stripe_id') {
                    if (empty($row[$i]) || (!empty($row[$i]) && !startsWith($row[$i], 'cus_'))) {
                        $row[$i] = null;
                    }
                }

                $insert[$databaseFields[$i]] = $row[$i];
            }

            if ($duplicate) {
                continue;
            }

            $insert = $this->trimInsertValues($insert);

            if (count($insert) > 0) {
                $this->incrementImported();

                $id = null;

                if (!$this->isSimulation()) {
                    $insert['datecreated']           = date('Y-m-d H:i:s');
                    $insert['donotsendwelcomeemail'] = true;

                    if ($this->ci->input->post('default_pass_all')) {
                        $insert['password'] = $this->ci->input->post('default_pass_all', false);
                    }

                    if ($this->shouldAddContactUnderCustomer($insert)) {
                        $this->addContactUnderCustomer($insert);

                        continue;
                    }

                    $insert['is_primary'] = 1;
                    $id                   = $this->ci->clients_model->add($insert, true);

                    if ($id) {
                        if ($this->ci->input->post('groups_in[]')) {
                            $this->insertCustomerGroups($this->ci->input->post('groups_in[]'), $id);
                        }

                        if (!has_permission('customers', '', 'view')) {
                            $assign['customer_admins']   = [];
                            $assign['customer_admins'][] = get_staff_user_id();
                            $this->ci->clients_model->assign_admins($assign, $id);
                        }
                    }
                } else {
                    $this->simulationData[$rowNumber] = $this->formatValuesForSimulation($insert);
                }

                $this->handleCustomFieldsInsert($id, $row, $i, $rowNumber, 'customers');
            }

            if ($this->isSimulation() && $rowNumber >= $this->maxSimulationRows) {
                break;
            }
        }
    }

    public function formatFieldNameForHeading($field)
    {
        if (strtolower($field) == 'title') {
            return 'Position';
        }

        return parent::formatFieldNameForHeading($field);
    }

    protected function email_formatSampleData()
    {
        return uniqid() . '@example.com';
    }

    protected function failureRedirectURL()
    {
        return admin_url('clients/import');
    }

    protected function afterSampleTableHeadingText($field)
    {
        $contactFields = [
            'firstname', 'lastname', 'email', 'contact_phonenumber', 'title',
        ];

        if (in_array($field, $contactFields)) {
            return '<br /><span class="text-info">' . _l('import_contact_field') . '</span>';
        }
    }

    private function insertCustomerGroups($groups, $customer_id)
    {
        foreach ($groups as $group) {
            $this->ci->db->insert(db_prefix().'customer_groups', [
                                                    'customer_id' => $customer_id,
                                                    'groupid'     => $group,
                                                ]);
        }
    }

    private function shouldAddContactUnderCustomer($data)
    {
        return (isset($data['company']) && $data['company'] != '' && $data['company'] != '/')
        && (total_rows(db_prefix().'clients', ['company' => $data['company']]) === 1);
    }

    private function addContactUnderCustomer($data)
    {
        $contactFields = $this->getContactFields();
        $this->ci->db->where('company', $data['company']);

        $existingCompany = $this->ci->db->get(db_prefix().'clients')->row();
        $tmpInsert       = [];

        foreach ($data as $key => $val) {
            foreach ($contactFields as $tmpContactField) {
                if (isset($data[$tmpContactField])) {
                    $tmpInsert[$tmpContactField] = $data[$tmpContactField];
                }
            }
        }
        $tmpInsert['donotsendwelcomeemail'] = true;

        if (isset($data['contact_phonenumber'])) {
            $tmpInsert['phonenumber'] = $data['contact_phonenumber'];
        }

        $this->ci->clients_model->add_contact($tmpInsert, $existingCompany->userid, true);
    }

    private function getContactFields()
    {
        return $this->ci->db->list_fields(db_prefix().'contacts');
    }

    private function isDuplicateContact($email)
    {
        return total_rows(db_prefix().'contacts', ['email' => $email]);
    }

    private function formatValuesForSimulation($values)
    {
        // ATM only country fields
        foreach ($this->countryFields as $country_field) {
            if (array_key_exists($country_field, $values)) {
                if (!empty($values[$country_field]) && is_numeric($values[$country_field])) {
                    $country = $this->getCountry(null, $values[$country_field]);
                    if ($country) {
                        $values[$country_field] = $country->short_name;
                    }
                }
            }
        }

        return $values;
    }

    private function getCountry($search = null, $id = null)
    {
        if ($search) {
            $this->ci->db->where('iso2', $search);
            $this->ci->db->or_where('short_name', $search);
            $this->ci->db->or_where('long_name', $search);
        } else {
            $this->ci->db->where('country_id', $id);
        }

        return  $this->ci->db->get(db_prefix().'countries')->row();
    }

    private function countryValue($value)
    {
        if ($value != '') {
            if (!is_numeric($value)) {
                $country = $this->getCountry($value);
                $value   = $country ? $country->country_id : 0;
            }
        } else {
            $value = 0;
        }

        return $value;
    }
}
