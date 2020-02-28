<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Settings_model extends App_Model
{
    private $encrypted_fields = ['smtp_password'];

    public function __construct()
    {
        parent::__construct();
        $payment_gateways = $this->payment_modes_model->get_payment_gateways(true);
        foreach ($payment_gateways as $gateway) {
            $settings = $gateway['instance']->getSettings();
            foreach ($settings as $option) {
                if (isset($option['encrypted']) && $option['encrypted'] == true) {
                    array_push($this->encrypted_fields, $option['name']);
                }
            }
        }
    }

    /**
     * Update all settings
     * @param  array $data all settings
     * @return integer
     */
    public function update($data)
    {
        $original_encrypted_fields = [];
        foreach ($this->encrypted_fields as $ef) {
            $original_encrypted_fields[$ef] = get_option($ef);
        }
        $affectedRows = 0;
        $data         = hooks()->apply_filters('before_settings_updated', $data);

        if (isset($data['tags'])) {
            $tagsExists = false;
            foreach ($data['tags'] as $id => $name) {
                $this->db->where('name', $name);
                $this->db->where('id !=', $id);
                $tag = $this->db->get('tags')->row();
                if (!$tag) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'tags', ['name' => $name]);
                    $affectedRows += $this->db->affected_rows();
                } else {
                    $tagsExists = true;
                }
            }

            if ($tagsExists) {
                set_alert('warning', _l('tags_update_replace_warning'));

                return false;
            }

            return (bool) $affectedRows;
        }
        if (!isset($data['settings']['default_tax']) && isset($data['finance_settings'])) {
            $data['settings']['default_tax'] = [];
        }
        $all_settings_looped = [];
        foreach ($data['settings'] as $name => $val) {

                // Do not trim thousand separator option
            // There is an option of white space there and if will be trimmed wont work as configured
            if (is_string($val) && $name != 'thousand_separator') {
                $val = trim($val);
            }

            array_push($all_settings_looped, $name);

            $hook_data['name']  = $name;
            $hook_data['value'] = $val;
            $hook_data          = hooks()->apply_filters('before_single_setting_updated_in_loop', $hook_data);
            $name               = $hook_data['name'];
            $val                = $hook_data['value'];

            // Check if the option exists
            $this->db->where('name', $name);
            $exists = $this->db->count_all_results(db_prefix() . 'options');
            if ($exists == 0) {
                continue;
            }

            if ($name == 'default_contact_permissions') {
                $val = serialize($val);
            } elseif ($name == 'lead_unique_validation') {
                $val = json_encode($val);
            } elseif ($name == 'visible_customer_profile_tabs') {
                if ($val == '') {
                    $val = 'all';
                } else {
                    $tabs           = get_customer_profile_tabs();
                    $newVisibleTabs = [];
                    foreach ($tabs as $tabKey => $tab) {
                        $newVisibleTabs[$tabKey] = in_array($tabKey, $val);
                    }
                    $val = serialize($newVisibleTabs);
                }
            } elseif ($name == 'email_signature') {
                $val = html_entity_decode($val);

                if($val == strip_tags($val)) {
                    // not contains HTML, add break lines
                    $val = nl2br_save_html($val);
                }

            } elseif ($name == 'email_header' || $name == 'email_footer') {
                $val = html_entity_decode($val);
            } elseif ($name == 'default_tax') {
                $val = array_filter($val, function ($value) {
                    return $value !== '';
                });
                $val = serialize($val);
            } elseif ($name == 'company_info_format' || $name == 'customer_info_format' || $name == 'proposal_info_format' || strpos($name, 'sms_trigger_') !== false) {
                $val = strip_tags($val);
                $val = nl2br($val);
            } elseif (in_array($name, $this->encrypted_fields)) {
                // Check if not empty $val password
                // Get original
                // Decrypt original
                // Compare with $val password
                // If equal unset
                // If not encrypt and save
                if (!empty($val)) {
                    $or_decrypted = $this->encryption->decrypt($original_encrypted_fields[$name]);
                    if ($or_decrypted == $val) {
                        continue;
                    }
                    $val = $this->encryption->encrypt($val);
                }
            }

            $this->db->where('name', $name);
            $this->db->update(db_prefix() . 'options', [
                    'value' => $val,
                ]);

            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
                if ($name == 'save_last_order_for_tables') {
                    $this->db->query('DELETE FROM ' . db_prefix() . 'user_meta where meta_key like "%-table-last-order"');
                }
            }
        }

        // Contact permission default none
        if (!in_array('default_contact_permissions', $all_settings_looped)
                && in_array('customer_settings', $all_settings_looped)) {
            $this->db->where('name', 'default_contact_permissions');
            $this->db->update(db_prefix() . 'options', [
                'value' => serialize([]),
            ]);
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }
        } elseif (!in_array('visible_customer_profile_tabs', $all_settings_looped)
                && in_array('customer_settings', $all_settings_looped)) {
            $this->db->where('name', 'visible_customer_profile_tabs');
            $this->db->update(db_prefix() . 'options', [
                'value' => 'all',
            ]);
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }
        } elseif (!in_array('lead_unique_validation', $all_settings_looped)
                && in_array('_leads_settings', $all_settings_looped)) {
            $this->db->where('name', 'lead_unique_validation');
            $this->db->update(db_prefix() . 'options', [
                'value' => json_encode([]),
            ]);
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }
        }

        if (isset($data['custom_fields'])) {
            if (handle_custom_fields_post(0, $data['custom_fields'])) {
                $affectedRows++;
            }
        }



        return $affectedRows;
    }

    public function add_new_company_pdf_field($data)
    {
        $field = 'custom_company_field_' . trim($data['field']);
        $field = preg_replace('/\s+/', '_', $field);
        if (add_option($field, $data['value'])) {
            return true;
        }

        return false;
    }
}
