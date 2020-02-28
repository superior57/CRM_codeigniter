<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Estimates_model extends App_Model
{
    private $statuses;

    private $shipping_fields = ['shipping_street', 'shipping_city', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];

    public function __construct()
    {
        parent::__construct();

        $this->statuses = hooks()->apply_filters('before_set_estimate_statuses', [
            1,
            2,
            5,
            3,
            4,
        ]);
    }

    /**
     * Get unique sale agent for estimates / Used for filters
     * @return array
     */
    public function get_sale_agents()
    {
        return $this->db->query("SELECT DISTINCT(sale_agent) as sale_agent, CONCAT(firstname, ' ', lastname) as full_name FROM " . db_prefix() . 'estimates JOIN ' . db_prefix() . 'staff on ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'estimates.sale_agent WHERE sale_agent != 0')->result_array();
    }

    /**
     * Get estimate/s
     * @param  mixed $id    estimate id
     * @param  array  $where perform where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*,' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'estimates.id as id, ' . db_prefix() . 'currencies.name as currency_name');
        $this->db->from(db_prefix() . 'estimates');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'estimates.currency', 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'estimates.id', $id);
            $estimate = $this->db->get()->row();
            if ($estimate) {
                $estimate->attachments                           = $this->get_attachments($id);
                $estimate->visible_attachments_to_customer_found = false;
                foreach ($estimate->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $estimate->visible_attachments_to_customer_found = true;

                        break;
                    }
                }
                $estimate->items = get_items_by_type('estimate', $id);

                if ($estimate->project_id != 0) {
                    $this->load->model('projects_model');
                    $estimate->project_data = $this->projects_model->get($estimate->project_id);
                }
                $estimate->client = $this->clients_model->get($estimate->clientid);
                if (!$estimate->client) {
                    $estimate->client          = new stdClass();
                    $estimate->client->company = $estimate->deleted_customer_name;
                }
            }

            return $estimate;
        }
        $this->db->order_by('number,YEAR(date)', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Get estimate statuses
     * @return array
     */
    public function get_statuses()
    {
        return $this->statuses;
    }

    public function clear_signature($id)
    {
        $this->db->select('signature');
        $this->db->where('id', $id);
        $estimate = $this->db->get(db_prefix() . 'estimates')->row();

        if ($estimate) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'estimates', ['signature' => null]);

            if (!empty($estimate->signature)) {
                unlink(get_upload_path_by_type('estimate') . $id . '/' . $estimate->signature);
            }

            return true;
        }

        return false;
    }

    /**
     * Function that will perform estimates pipeline query
     * @param  mixed  $status
     * @param  string  $search
     * @param  integer $page
     * @param  array   $sort
     * @param  boolean $count
     * @return array
     */
    public function do_kanban_query($status, $search = '', $page = 1, $sort = [], $count = false)
    {
        $default_pipeline_order      = get_option('default_estimates_pipeline_sort');
        $default_pipeline_order_type = get_option('default_estimates_pipeline_sort_type');
        $limit                       = get_option('estimates_pipeline_limit');

        $fields_client    = $this->db->list_fields(db_prefix() . 'clients');
        $fields_estimates = $this->db->list_fields(db_prefix() . 'estimates');

        $has_permission_view = has_permission('estimates', '', 'view');
        $noPermissionQuery = get_estimates_where_sql_for_staff(get_staff_user_id());

        $this->db->select(db_prefix() . 'estimates.id,status,invoiceid,' . get_sql_select_client_company() . ',total,currency,symbol,'.db_prefix().'currencies.name as currency_name,date,expirydate,clientid');
        $this->db->from(db_prefix() . 'estimates');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'estimates.clientid', 'left');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'estimates.currency = ' . db_prefix() . 'currencies.id');
        $this->db->where('status', $status);

        if (!$has_permission_view) {
            $this->db->where($noPermissionQuery);
        }

        if ($search != '') {
            if (!startsWith($search, '#')) {
                $where = '(';
                $i     = 0;
                foreach ($fields_client as $f) {
                    $where .= db_prefix() . 'clients.' . $f . ' LIKE "%' . $search . '%"';
                    $where .= ' OR ';
                    $i++;
                }
                $i = 0;
                foreach ($fields_estimates as $f) {
                    $where .= db_prefix() . 'estimates.' . $f . ' LIKE "%' . $search . '%"';
                    $where .= ' OR ';

                    $i++;
                }
                $where = substr($where, 0, -4);
                $where .= ')';
                $this->db->where($where);
            } else {
                $this->db->where(db_prefix() . 'estimates.id IN
                (SELECT rel_id FROM ' . db_prefix() . 'taggables WHERE tag_id IN
                (SELECT id FROM ' . db_prefix() . 'tags WHERE name="' . strafter($search, '#') . '")
                AND ' . db_prefix() . 'taggables.rel_type=\'estimate\' GROUP BY rel_id HAVING COUNT(tag_id) = 1)
                ');
            }
        }

        if (isset($sort['sort_by']) && $sort['sort_by'] && isset($sort['sort']) && $sort['sort']) {
            $this->db->order_by(db_prefix() . 'estimates.' . $sort['sort_by'], $sort['sort']);
        } else {
            $this->db->order_by(db_prefix() . 'estimates.' . $default_pipeline_order, $default_pipeline_order_type);
        }

        if ($count == false) {
            if ($page > 1) {
                $page--;
                $position = ($page * $limit);
                $this->db->limit($limit, $position);
            } else {
                $this->db->limit($limit);
            }
        }

        if ($count == false) {
            return $this->db->get()->result_array();
        }

        return $this->db->count_all_results();
    }

    /**
     * Convert estimate to invoice
     * @param  mixed $id estimate id
     * @return mixed     New invoice ID
     */
    public function convert_to_invoice($id, $client = false, $draft_invoice = false)
    {
        // Recurring invoice date is okey lets convert it to new invoice
        $_estimate = $this->get($id);

        $new_invoice_data = [];
        if ($draft_invoice == true) {
            $new_invoice_data['save_as_draft'] = true;
        }
        $new_invoice_data['clientid']   = $_estimate->clientid;
        $new_invoice_data['project_id'] = $_estimate->project_id;
        $new_invoice_data['number']     = get_option('next_invoice_number');
        $new_invoice_data['date']       = _d(date('Y-m-d'));
        $new_invoice_data['duedate']    = _d(date('Y-m-d'));
        if (get_option('invoice_due_after') != 0) {
            $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }
        $new_invoice_data['show_quantity_as'] = $_estimate->show_quantity_as;
        $new_invoice_data['currency']         = $_estimate->currency;
        $new_invoice_data['subtotal']         = $_estimate->subtotal;
        $new_invoice_data['total']            = $_estimate->total;
        $new_invoice_data['adjustment']       = $_estimate->adjustment;
        $new_invoice_data['discount_percent'] = $_estimate->discount_percent;
        $new_invoice_data['discount_total']   = $_estimate->discount_total;
        $new_invoice_data['discount_type']    = $_estimate->discount_type;
        $new_invoice_data['sale_agent']       = $_estimate->sale_agent;
        // Since version 1.0.6
        $new_invoice_data['billing_street']   = clear_textarea_breaks($_estimate->billing_street);
        $new_invoice_data['billing_city']     = $_estimate->billing_city;
        $new_invoice_data['billing_state']    = $_estimate->billing_state;
        $new_invoice_data['billing_zip']      = $_estimate->billing_zip;
        $new_invoice_data['billing_country']  = $_estimate->billing_country;
        $new_invoice_data['shipping_street']  = clear_textarea_breaks($_estimate->shipping_street);
        $new_invoice_data['shipping_city']    = $_estimate->shipping_city;
        $new_invoice_data['shipping_state']   = $_estimate->shipping_state;
        $new_invoice_data['shipping_zip']     = $_estimate->shipping_zip;
        $new_invoice_data['shipping_country'] = $_estimate->shipping_country;

        if ($_estimate->include_shipping == 1) {
            $new_invoice_data['include_shipping'] = 1;
        }

        $new_invoice_data['show_shipping_on_invoice'] = $_estimate->show_shipping_on_estimate;
        $new_invoice_data['terms']                    = get_option('predefined_terms_invoice');
        $new_invoice_data['clientnote']               = get_option('predefined_clientnote_invoice');
        // Set to unpaid status automatically
        $new_invoice_data['status']    = 1;
        $new_invoice_data['adminnote'] = '';

        $this->load->model('payment_modes_model');
        $modes = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);
        $temp_modes = [];
        foreach ($modes as $mode) {
            if ($mode['selected_by_default'] == 0) {
                continue;
            }
            $temp_modes[] = $mode['id'];
        }
        $new_invoice_data['allowed_payment_modes'] = $temp_modes;
        $new_invoice_data['newitems']              = [];
        $custom_fields_items                       = get_custom_fields('items');
        $key                                       = 1;
        foreach ($_estimate->items as $item) {
            $new_invoice_data['newitems'][$key]['description']      = $item['description'];
            $new_invoice_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_invoice_data['newitems'][$key]['qty']              = $item['qty'];
            $new_invoice_data['newitems'][$key]['unit']             = $item['unit'];
            $new_invoice_data['newitems'][$key]['taxname']          = [];
            $taxes                                                  = get_estimate_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_invoice_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_invoice_data['newitems'][$key]['rate']  = $item['rate'];
            $new_invoice_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_invoice_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $this->load->model('invoices_model');
        $id = $this->invoices_model->add($new_invoice_data);
        if ($id) {
            // Customer accepted the estimate and is auto converted to invoice
            if (!is_staff_logged_in()) {
                $this->db->where('rel_type', 'invoice');
                $this->db->where('rel_id', $id);
                $this->db->delete(db_prefix() . 'sales_activity');
                $this->invoices_model->log_invoice_activity($id, 'invoice_activity_auto_converted_from_estimate', true, serialize([
                    '<a href="' . admin_url('estimates/list_estimates/' . $_estimate->id) . '">' . format_estimate_number($_estimate->id) . '</a>',
                ]));
            }
            // For all cases update addefrom and sale agent from the invoice
            // May happen staff is not logged in and these values to be 0
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'invoices', [
                'addedfrom'  => $_estimate->addedfrom,
                'sale_agent' => $_estimate->sale_agent,
            ]);

            // Update estimate with the new invoice data and set to status accepted
            $this->db->where('id', $_estimate->id);
            $this->db->update(db_prefix() . 'estimates', [
                'invoiced_date' => date('Y-m-d H:i:s'),
                'invoiceid'     => $id,
                'status'        => 4,
            ]);


            if (is_custom_fields_smart_transfer_enabled()) {
                $this->db->where('fieldto', 'estimate');
                $this->db->where('active', 1);
                $cfEstimates = $this->db->get(db_prefix() . 'customfields')->result_array();
                foreach ($cfEstimates as $field) {
                    $tmpSlug = explode('_', $field['slug'], 2);
                    if (isset($tmpSlug[1])) {
                        $this->db->where('fieldto', 'invoice');
                        $this->db->where('slug LIKE "invoice_' . $tmpSlug[1] . '%" AND type="' . $field['type'] . '" AND options="' . $field['options'] . '" AND active=1');
                        $cfTransfer = $this->db->get(db_prefix() . 'customfields')->result_array();

                        // Don't make mistakes
                        // Only valid if 1 result returned
                        // + if field names similarity is equal or more then CUSTOM_FIELD_TRANSFER_SIMILARITY%
                        if (count($cfTransfer) == 1 && ((similarity($field['name'], $cfTransfer[0]['name']) * 100) >= CUSTOM_FIELD_TRANSFER_SIMILARITY)) {
                            $value = get_custom_field_value($_estimate->id, $field['id'], 'estimate', false);

                            if ($value == '') {
                                continue;
                            }

                            $this->db->insert(db_prefix() . 'customfieldsvalues', [
                                'relid'   => $id,
                                'fieldid' => $cfTransfer[0]['id'],
                                'fieldto' => 'invoice',
                                'value'   => $value,
                            ]);
                        }
                    }
                }
            }

            if ($client == false) {
                $this->log_estimate_activity($_estimate->id, 'estimate_activity_converted', false, serialize([
                    '<a href="' . admin_url('invoices/list_invoices/' . $id) . '">' . format_invoice_number($id) . '</a>',
                ]));
            }

            hooks()->do_action('estimate_converted_to_invoice', ['invoice_id' => $id, 'estimate_id' => $_estimate->id]);
        }

        return $id;
    }

    /**
     * Copy estimate
     * @param  mixed $id estimate id to copy
     * @return mixed
     */
    public function copy($id)
    {
        $_estimate                       = $this->get($id);
        $new_estimate_data               = [];
        $new_estimate_data['clientid']   = $_estimate->clientid;
        $new_estimate_data['project_id'] = $_estimate->project_id;
        $new_estimate_data['number']     = get_option('next_estimate_number');
        $new_estimate_data['date']       = _d(date('Y-m-d'));
        $new_estimate_data['expirydate'] = null;

        if ($_estimate->expirydate && get_option('estimate_due_after') != 0) {
            $new_estimate_data['expirydate'] = _d(date('Y-m-d', strtotime('+' . get_option('estimate_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }

        $new_estimate_data['show_quantity_as'] = $_estimate->show_quantity_as;
        $new_estimate_data['currency']         = $_estimate->currency;
        $new_estimate_data['subtotal']         = $_estimate->subtotal;
        $new_estimate_data['total']            = $_estimate->total;
        $new_estimate_data['adminnote']        = $_estimate->adminnote;
        $new_estimate_data['adjustment']       = $_estimate->adjustment;
        $new_estimate_data['discount_percent'] = $_estimate->discount_percent;
        $new_estimate_data['discount_total']   = $_estimate->discount_total;
        $new_estimate_data['discount_type']    = $_estimate->discount_type;
        $new_estimate_data['terms']            = $_estimate->terms;
        $new_estimate_data['sale_agent']       = $_estimate->sale_agent;
        $new_estimate_data['reference_no']     = $_estimate->reference_no;
        // Since version 1.0.6
        $new_estimate_data['billing_street']   = clear_textarea_breaks($_estimate->billing_street);
        $new_estimate_data['billing_city']     = $_estimate->billing_city;
        $new_estimate_data['billing_state']    = $_estimate->billing_state;
        $new_estimate_data['billing_zip']      = $_estimate->billing_zip;
        $new_estimate_data['billing_country']  = $_estimate->billing_country;
        $new_estimate_data['shipping_street']  = clear_textarea_breaks($_estimate->shipping_street);
        $new_estimate_data['shipping_city']    = $_estimate->shipping_city;
        $new_estimate_data['shipping_state']   = $_estimate->shipping_state;
        $new_estimate_data['shipping_zip']     = $_estimate->shipping_zip;
        $new_estimate_data['shipping_country'] = $_estimate->shipping_country;
        if ($_estimate->include_shipping == 1) {
            $new_estimate_data['include_shipping'] = $_estimate->include_shipping;
        }
        $new_estimate_data['show_shipping_on_estimate'] = $_estimate->show_shipping_on_estimate;
        // Set to unpaid status automatically
        $new_estimate_data['status']     = 1;
        $new_estimate_data['clientnote'] = $_estimate->clientnote;
        $new_estimate_data['adminnote']  = '';
        $new_estimate_data['newitems']   = [];
        $custom_fields_items             = get_custom_fields('items');
        $key                             = 1;
        foreach ($_estimate->items as $item) {
            $new_estimate_data['newitems'][$key]['description']      = $item['description'];
            $new_estimate_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_estimate_data['newitems'][$key]['qty']              = $item['qty'];
            $new_estimate_data['newitems'][$key]['unit']             = $item['unit'];
            $new_estimate_data['newitems'][$key]['taxname']          = [];
            $taxes                                                   = get_estimate_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_estimate_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_estimate_data['newitems'][$key]['rate']  = $item['rate'];
            $new_estimate_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_estimate_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $id = $this->add($new_estimate_data);
        if ($id) {
            $custom_fields = get_custom_fields('estimate');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($_estimate->id, $field['id'], 'estimate', false);
                if ($value == '') {
                    continue;
                }

                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'estimate',
                    'value'   => $value,
                ]);
            }

            $tags = get_tags_in($_estimate->id, 'estimate');
            handle_tags_save($tags, $id, 'estimate');

            log_activity('Copied Estimate ' . format_estimate_number($_estimate->id));

            return $id;
        }

        return false;
    }

    /**
     * Performs estimates totals status
     * @param  array $data
     * @return array
     */
    public function get_estimates_total($data)
    {
        $statuses            = $this->get_statuses();
        $has_permission_view = has_permission('estimates', '', 'view');
        $this->load->model('currencies_model');
        if (isset($data['currency'])) {
            $currencyid = $data['currency'];
        } elseif (isset($data['customer_id']) && $data['customer_id'] != '') {
            $currencyid = $this->clients_model->get_customer_default_currency($data['customer_id']);
            if ($currencyid == 0) {
                $currencyid = $this->currencies_model->get_base_currency()->id;
            }
        } elseif (isset($data['project_id']) && $data['project_id'] != '') {
            $this->load->model('projects_model');
            $currencyid = $this->projects_model->get_currency($data['project_id'])->id;
        } else {
            $currencyid = $this->currencies_model->get_base_currency()->id;
        }

        $currency = get_currency($currencyid);
        $where  = '';
        if (isset($data['customer_id']) && $data['customer_id'] != '') {
            $where = ' AND clientid=' . $data['customer_id'];
        }

        if (isset($data['project_id']) && $data['project_id'] != '') {
            $where .= ' AND project_id=' . $data['project_id'];
        }

        if (!$has_permission_view) {
            $where .= ' AND ' . get_estimates_where_sql_for_staff(get_staff_user_id());
        }

        $sql = 'SELECT';
        foreach ($statuses as $estimate_status) {
            $sql .= '(SELECT SUM(total) FROM ' . db_prefix() . 'estimates WHERE status=' . $estimate_status;
            $sql .= ' AND currency =' . $currencyid;
            if (isset($data['years']) && count($data['years']) > 0) {
                $sql .= ' AND YEAR(date) IN (' . implode(', ', $data['years']) . ')';
            } else {
                $sql .= ' AND YEAR(date) = ' . date('Y');
            }
            $sql .= $where;
            $sql .= ') as "' . $estimate_status . '",';
        }

        $sql     = substr($sql, 0, -1);
        $result  = $this->db->query($sql)->result_array();
        $_result = [];
        $i       = 1;
        foreach ($result as $key => $val) {
            foreach ($val as $status => $total) {
                $_result[$i]['total']  = $total;
                $_result[$i]['symbol'] = $currency->symbol;
                $_result[$i]['currency_name'] = $currency->name;
                $_result[$i]['status'] = $status;
                $i++;
            }
        }
        $_result['currencyid'] = $currencyid;

        return $_result;
    }

    /**
     * Insert new estimate to database
     * @param array $data invoiec data
     * @return mixed - false if not insert, estimate ID if succes
     */
    public function add($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');

        $data['addedfrom'] = get_staff_user_id();

        $data['prefix'] = get_option('estimate_prefix');

        $data['number_format'] = get_option('estimate_number_format');

        $save_and_send = isset($data['save_and_send']);

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data['hash'] = app_generate_hash();
        $tags         = isset($data['tags']) ? $data['tags'] : '';

        $items = [];
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        $data = $this->map_shipping_columns($data);

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        if (isset($data['shipping_street'])) {
            $data['shipping_street'] = trim($data['shipping_street']);
            $data['shipping_street'] = nl2br($data['shipping_street']);
        }

        $hook = hooks()->apply_filters('before_estimate_added', [
            'data'  => $data,
            'items' => $items,
        ]);

        $data  = $hook['data'];
        $items = $hook['items'];

        $this->db->insert(db_prefix() . 'estimates', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update next estimate number in settings
            $this->db->where('name', 'next_estimate_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            handle_tags_save($tags, $insert_id, 'estimate');

            foreach ($items as $key => $item) {
                if ($itemid = add_new_sales_item_post($item, $insert_id, 'estimate')) {
                    _maybe_insert_post_item_tax($itemid, $item, $insert_id, 'estimate');
                }
            }

            update_sales_total_tax_column($insert_id, 'estimate', db_prefix() . 'estimates');
            $this->log_estimate_activity($insert_id, 'estimate_activity_created');

            hooks()->do_action('after_estimate_added', $insert_id);

            if ($save_and_send === true) {
                $this->send_estimate_to_client($insert_id, '', true, '', true);
            }

            return $insert_id;
        }

        return false;
    }

    /**
     * Get item by id
     * @param  mixed $id item id
     * @return object
     */
    public function get_estimate_item($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'itemable')->row();
    }

    /**
     * Update estimate data
     * @param  array $data estimate data
     * @param  mixed $id   estimateid
     * @return boolean
     */
    public function update($data, $id)
    {
        $affectedRows = 0;

        $data['number'] = trim($data['number']);

        $original_estimate = $this->get($id);

        $original_status = $original_estimate->status;

        $original_number = $original_estimate->number;

        $original_number_formatted = format_estimate_number($id);

        $save_and_send = isset($data['save_and_send']);

        $items = [];
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
        }

        $newitems = [];
        if (isset($data['newitems'])) {
            $newitems = $data['newitems'];
            unset($data['newitems']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'estimate')) {
                $affectedRows++;
            }
        }

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        $data['shipping_street'] = trim($data['shipping_street']);
        $data['shipping_street'] = nl2br($data['shipping_street']);

        $data = $this->map_shipping_columns($data);

        $hook = hooks()->apply_filters('before_estimate_updated', [
            'data'          => $data,
            'items'         => $items,
            'newitems'      => $newitems,
            'removed_items' => isset($data['removed_items']) ? $data['removed_items'] : [],
        ], $id);

        $data                  = $hook['data'];
        $items                 = $hook['items'];
        $newitems              = $hook['newitems'];
        $data['removed_items'] = $hook['removed_items'];

        // Delete items checked to be removed from database
        foreach ($data['removed_items'] as $remove_item_id) {
            $original_item = $this->get_estimate_item($remove_item_id);
            if (handle_removed_sales_item_post($remove_item_id, 'estimate')) {
                $affectedRows++;
                $this->log_estimate_activity($id, 'invoice_estimate_activity_removed_item', false, serialize([
                        $original_item->description,
                    ]));
            }
        }

        unset($data['removed_items']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'estimates', $data);

        if ($this->db->affected_rows() > 0) {
            // Check for status change
            if ($original_status != $data['status']) {
                $this->log_estimate_activity($original_estimate->id, 'not_estimate_status_updated', false, serialize([
                    '<original_status>' . $original_status . '</original_status>',
                    '<new_status>' . $data['status'] . '</new_status>',
                ]));
                if ($data['status'] == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'estimates', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
            }
            if ($original_number != $data['number']) {
                $this->log_estimate_activity($original_estimate->id, 'estimate_activity_number_changed', false, serialize([
                    $original_number_formatted,
                    format_estimate_number($original_estimate->id),
                ]));
            }
            $affectedRows++;
        }

        foreach ($items as $key => $item) {
            $original_item = $this->get_estimate_item($item['itemid']);

            if (update_sales_item_post($item['itemid'], $item, 'item_order')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'unit')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'rate')) {
                $this->log_estimate_activity($id, 'invoice_estimate_activity_updated_item_rate', false, serialize([
                        $original_item->rate,
                        $item['rate'],
                    ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'qty')) {
                $this->log_estimate_activity($id, 'invoice_estimate_activity_updated_qty_item', false, serialize([
                        $item['description'],
                        $original_item->qty,
                        $item['qty'],
                    ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'description')) {
                $this->log_estimate_activity($id, 'invoice_estimate_activity_updated_item_short_description', false, serialize([
                        $original_item->description,
                        $item['description'],
                    ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'long_description')) {
                $this->log_estimate_activity($id, 'invoice_estimate_activity_updated_item_long_description', false, serialize([
                        $original_item->long_description,
                        $item['long_description'],
                    ]));
                $affectedRows++;
            }

            if (isset($item['custom_fields'])) {
                if (handle_custom_fields_post($item['itemid'], $item['custom_fields'])) {
                    $affectedRows++;
                }
            }

            if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                if (delete_taxes_from_item($item['itemid'], 'estimate')) {
                    $affectedRows++;
                }
            } else {
                $item_taxes        = get_estimate_item_taxes($item['itemid']);
                $_item_taxes_names = [];
                foreach ($item_taxes as $_item_tax) {
                    array_push($_item_taxes_names, $_item_tax['taxname']);
                }

                $i = 0;
                foreach ($_item_taxes_names as $_item_tax) {
                    if (!in_array($_item_tax, $item['taxname'])) {
                        $this->db->where('id', $item_taxes[$i]['id'])
                            ->delete(db_prefix() . 'item_tax');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                    $i++;
                }
                if (_maybe_insert_post_item_tax($item['itemid'], $item, $id, 'estimate')) {
                    $affectedRows++;
                }
            }
        }

        foreach ($newitems as $key => $item) {
            if ($new_item_added = add_new_sales_item_post($item, $id, 'estimate')) {
                _maybe_insert_post_item_tax($new_item_added, $item, $id, 'estimate');
                $this->log_estimate_activity($id, 'invoice_estimate_activity_added_item', false, serialize([
                    $item['description'],
                ]));
                $affectedRows++;
            }
        }

        if ($affectedRows > 0) {
            update_sales_total_tax_column($id, 'estimate', db_prefix() . 'estimates');
        }

        if ($save_and_send === true) {
            $this->send_estimate_to_client($id, '', true, '', true);
        }

        if ($affectedRows > 0) {
            hooks()->do_action('after_estimate_updated', $id);

            return true;
        }

        return false;
    }

    public function mark_action_status($action, $id, $client = false)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'estimates', [
            'status' => $action,
        ]);

        $notifiedUsers = [];

        if ($this->db->affected_rows() > 0) {
            $estimate = $this->get($id);
            if ($client == true) {
                $this->db->where('staffid', $estimate->addedfrom);
                $this->db->or_where('staffid', $estimate->sale_agent);
                $staff_estimate = $this->db->get(db_prefix() . 'staff')->result_array();

                $invoiceid = false;
                $invoiced  = false;

                $contact_id = !is_client_logged_in()
                    ? get_primary_contact_user_id($estimate->clientid)
                    : get_contact_user_id();

                if ($action == 4) {
                    if (get_option('estimate_auto_convert_to_invoice_on_client_accept') == 1) {
                        $invoiceid = $this->convert_to_invoice($id, true);
                        $this->load->model('invoices_model');
                        if ($invoiceid) {
                            $invoiced = true;
                            $invoice  = $this->invoices_model->get($invoiceid);
                            $this->log_estimate_activity($id, 'estimate_activity_client_accepted_and_converted', true, serialize([
                                '<a href="' . admin_url('invoices/list_invoices/' . $invoiceid) . '">' . format_invoice_number($invoice->id) . '</a>',
                            ]));
                        }
                    } else {
                        $this->log_estimate_activity($id, 'estimate_activity_client_accepted', true);
                    }

                    // Send thank you email to all contacts with permission estimates
                    $contacts = $this->clients_model->get_contacts($estimate->clientid, ['active' => 1, 'estimate_emails' => 1]);

                    foreach ($contacts as $contact) {
                        send_mail_template('estimate_accepted_to_customer', $estimate, $contact);
                    }

                    foreach ($staff_estimate as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_estimate_customer_accepted',
                            'link'            => 'estimates/list_estimates/' . $id,
                            'additional_data' => serialize([
                                format_estimate_number($estimate->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }

                        send_mail_template('estimate_accepted_to_staff', $estimate, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    hooks()->do_action('estimate_accepted', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                } elseif ($action == 3) {
                    foreach ($staff_estimate as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_estimate_customer_declined',
                            'link'            => 'estimates/list_estimates/' . $id,
                            'additional_data' => serialize([
                                format_estimate_number($estimate->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                        // Send staff email notification that customer declined estimate
                        send_mail_template('estimate_declined_to_staff', $estimate, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    $this->log_estimate_activity($id, 'estimate_activity_client_declined', true);
                    hooks()->do_action('estimate_declined', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                }
            } else {
                if ($action == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'estimates', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
                // Admin marked estimate
                $this->log_estimate_activity($id, 'estimate_activity_marked', false, serialize([
                    '<status>' . $action . '</status>',
                ]));

                return true;
            }
        }

        return false;
    }

    /**
     * Get estimate attachments
     * @param  mixed $estimate_id
     * @param  string $id          attachment id
     * @return mixed
     */
    public function get_attachments($estimate_id, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $estimate_id);
        }
        $this->db->where('rel_type', 'estimate');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     *  Delete estimate attachment
     * @param   mixed $id  attachmentid
     * @return  boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('estimate') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Estimate Attachment Deleted [EstimateID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('estimate') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('estimate') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('estimate') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Delete estimate items and all connections
     * @param  mixed $id estimateid
     * @return boolean
     */
    public function delete($id, $simpleDelete = false)
    {
        if (get_option('delete_only_on_last_estimate') == 1 && $simpleDelete == false) {
            if (!is_last_estimate($id)) {
                return false;
            }
        }
        $estimate = $this->get($id);
        if (!is_null($estimate->invoiceid) && $simpleDelete == false) {
            return [
                'is_invoiced_estimate_delete_error' => true,
            ];
        }
        hooks()->do_action('before_estimate_deleted', $id);

        $number = format_estimate_number($id);

        $this->clear_signature($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'estimates');

        if ($this->db->affected_rows() > 0) {
            if (get_option('estimate_number_decrement_on_delete') == 1 && $simpleDelete == false) {
                $current_next_estimate_number = get_option('next_estimate_number');
                if ($current_next_estimate_number > 1) {
                    // Decrement next estimate number to
                    $this->db->where('name', 'next_estimate_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update(db_prefix() . 'options');
                }
            }

            if (total_rows(db_prefix() . 'proposals', [
                'estimate_id' => $id,
            ]) > 0) {
                $this->db->where('estimate_id', $id);
                $estimate = $this->db->get(db_prefix() . 'proposals')->row();
                $this->db->where('id', $estimate->id);
                $this->db->update(db_prefix() . 'proposals', [
                    'estimate_id'    => null,
                    'date_converted' => null,
                ]);
            }

            delete_tracked_emails($id, 'estimate');

            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="estimate" AND rel_id="' . $id . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'estimate');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_type', 'estimate');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            $this->db->where('rel_type', 'estimate');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'taggables');

            $this->db->where('rel_type', 'estimate');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'estimate');
            $this->db->delete(db_prefix() . 'itemable');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'estimate');
            $this->db->delete(db_prefix() . 'item_tax');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'estimate');
            $this->db->delete(db_prefix() . 'sales_activity');

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'estimate');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            // Get related tasks
            $this->db->where('rel_type', 'estimate');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            if ($simpleDelete == false) {
                log_activity('Estimates Deleted [Number: ' . $number . ']');
            }

            return true;
        }

        return false;
    }

    /**
     * Set estimate to sent when email is successfuly sended to client
     * @param mixed $id estimateid
     */
    public function set_estimate_sent($id, $emails_sent = [])
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'estimates', [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ]);
        $this->log_estimate_activity($id, 'invoice_estimate_activity_sent_to_client', false, serialize([
            '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
        ]));
        // Update estimate status to sent
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'estimates', [
            'status' => 2,
        ]);
    }

    /**
     * Send expiration reminder to customer
     * @param  mixed $id estimate id
     * @return boolean
     */
    public function send_expiry_reminder($id)
    {
        $estimate        = $this->get($id);
        $estimate_number = format_estimate_number($estimate->id);
        set_mailing_constant();
        $pdf              = estimate_pdf($estimate);
        $attach           = $pdf->Output($estimate_number . '.pdf', 'S');
        $emails_sent      = [];
        $sms_sent         = false;
        $sms_reminder_log = [];

        // For all cases update this to prevent sending multiple reminders eq on fail
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'estimates', [
            'is_expiry_notified' => 1,
        ]);

        $contacts = $this->clients_model->get_contacts($estimate->clientid, ['active' => 1, 'estimate_emails' => 1]);

        foreach ($contacts as $contact) {
            $template = mail_template('estimate_expiration_reminder', $estimate, $contact);

            $merge_fields = $template->get_merge_fields();

            $template->add_attachment([
                    'attachment' => $attach,
                    'filename'   => str_replace('/', '-', $estimate_number . '.pdf'),
                    'type'       => 'application/pdf',
                ]);

            if ($template->send()) {
                array_push($emails_sent, $contact['email']);
            }

            if (can_send_sms_based_on_creation_date($estimate->datecreated)
                && $this->app_sms->trigger(SMS_TRIGGER_ESTIMATE_EXP_REMINDER, $contact['phonenumber'], $merge_fields)) {
                $sms_sent = true;
                array_push($sms_reminder_log, $contact['firstname'] . ' (' . $contact['phonenumber'] . ')');
            }
        }

        if (count($emails_sent) > 0 || $sms_sent) {
            if (count($emails_sent) > 0) {
                $this->log_estimate_activity($id, 'not_expiry_reminder_sent', false, serialize([
                    '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
                ]));
            }

            if ($sms_sent) {
                $this->log_estimate_activity($id, 'sms_reminder_sent_to', false, serialize([
                   implode(', ', $sms_reminder_log),
                ]));
            }

            return true;
        }

        return false;
    }

    /**
     * Send estimate to client
     * @param  mixed  $id        estimateid
     * @param  string  $template  email template to sent
     * @param  boolean $attachpdf attach estimate pdf or not
     * @return boolean
     */
    public function send_estimate_to_client($id, $template_name = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $estimate = $this->get($id);

        if ($template_name == '') {
            if ($estimate->sent == 0) {
                $template_name = 'estimate_send_to_customer';
            } else {
                $template_name = 'estimate_send_to_customer_already_sent';
            }
        }

        $estimate_number = format_estimate_number($estimate->id);

        $emails_sent = [];
        $sent        = false;
        $sent_to     = $this->input->post('sent_to');
        if ($manually === true) {
            $sent_to  = [];
            $contacts = $this->clients_model->get_contacts($estimate->clientid, ['active' => 1, 'estimate_emails' => 1]);
            foreach ($contacts as $contact) {
                array_push($sent_to, $contact['id']);
            }
        }

        $status_now          = $estimate->status;
        $status_auto_updated = false;
        if (is_array($sent_to) && count($sent_to) > 0) {
            $i = 0;
            // Auto update status to sent in case when user sends the estimate is with status draft
            if ($status_now == 1) {
                $this->db->where('id', $estimate->id);
                $this->db->update(db_prefix() . 'estimates', [
                    'status' => 2,
                ]);
                $status_auto_updated = true;
            }

            if ($attachpdf) {
                $_pdf_estimate = $this->get($estimate->id);
                set_mailing_constant();
                $pdf = estimate_pdf($_pdf_estimate);

                $attach = $pdf->Output($estimate_number . '.pdf', 'S');
            }

            foreach ($sent_to as $contact_id) {
                if ($contact_id != '') {

                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $contact = $this->clients_model->get_contact($contact_id);

                    $template = mail_template($template_name, $estimate, $contact, $cc);

                    if ($attachpdf) {
                        $hook = hooks()->apply_filters('send_estimate_to_customer_file_name', [
                            'file_name' => str_replace('/', '-', $estimate_number . '.pdf'),
                            'estimate'  => $_pdf_estimate,
                        ]);

                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $hook['file_name'],
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        $sent = true;
                        array_push($emails_sent, $contact->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }
        if ($sent) {
            $this->set_estimate_sent($id, $emails_sent);
            hooks()->do_action('estimate_sent', $id);

            return true;
        }
        if ($status_auto_updated) {
            // Estimate not send to customer but the status was previously updated to sent now we need to revert back to draft
            $this->db->where('id', $estimate->id);
            $this->db->update(db_prefix() . 'estimates', [
                    'status' => 1,
                ]);
        }


        return false;
    }

    /**
     * All estimate activity
     * @param  mixed $id estimateid
     * @return array
     */
    public function get_estimate_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'estimate');
        $this->db->order_by('date', 'asc');

        return $this->db->get(db_prefix() . 'sales_activity')->result_array();
    }

    /**
     * Log estimate activity to database
     * @param  mixed $id   estimateid
     * @param  string $description activity description
     */
    public function log_estimate_activity($id, $description = '', $client = false, $additional_data = '')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if (DEFINED('CRON')) {
            $staffid   = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid   = null;
            $full_name = '';
        }

        $this->db->insert(db_prefix() . 'sales_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'estimate',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    /**
     * Updates pipeline order when drag and drop
     * @param  mixe $data $_POST data
     * @return void
     */
    public function update_pipeline($data)
    {
        $this->mark_action_status($data['status'], $data['estimateid']);
        foreach ($data['order'] as $order_data) {
            $this->db->where('id', $order_data[0]);
            $this->db->update(db_prefix() . 'estimates', [
                'pipeline_order' => $order_data[1],
            ]);
        }
    }

    /**
     * Get estimate unique year for filtering
     * @return array
     */
    public function get_estimates_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'estimates ORDER BY year DESC')->result_array();
    }

    private function map_shipping_columns($data)
    {
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_estimate'] = 1;
            $data['include_shipping']          = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_estimate']) && ($data['show_shipping_on_estimate'] == 1 || $data['show_shipping_on_estimate'] == 'on')) {
                $data['show_shipping_on_estimate'] = 1;
            } else {
                $data['show_shipping_on_estimate'] = 0;
            }
        }

        return $data;
    }
}
