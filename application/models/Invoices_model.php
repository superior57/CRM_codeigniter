<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Invoices_model extends App_Model
{
    const STATUS_UNPAID = 1;

    const STATUS_PAID = 2;

    const STATUS_PARTIALLY = 3;

    const STATUS_OVERDUE = 4;

    const STATUS_CANCELLED = 5;

    const STATUS_DRAFT = 6;

    private $statuses = [
        self::STATUS_UNPAID,
        self::STATUS_PAID,
        self::STATUS_PARTIALLY,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
        self::STATUS_DRAFT,
    ];

    private $shipping_fields = [
        'shipping_street',
        'shipping_city',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function get_statuses()
    {
        return $this->statuses;
    }

    public function get_sale_agents()
    {
        return $this->db->query('SELECT DISTINCT(sale_agent) as sale_agent, CONCAT(firstname, \' \', lastname) as full_name FROM ' . db_prefix() . 'invoices JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'invoices.sale_agent WHERE sale_agent != 0')->result_array();
    }

    /**
     * Get invoice by id
     * @param  mixed $id
     * @return array
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*, ' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'invoices.id as id, ' . db_prefix() . 'currencies.name as currency_name');
        $this->db->from(db_prefix() . 'invoices');
        $this->db->join(db_prefix() . 'currencies', '' . db_prefix() . 'currencies.id = ' . db_prefix() . 'invoices.currency', 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'invoices' . '.id', $id);
            $invoice = $this->db->get()->row();
            if ($invoice) {
                $invoice->total_left_to_pay = get_invoice_total_left_to_pay($invoice->id, $invoice->total);

                $invoice->items       = get_items_by_type('invoice', $id);
                $invoice->attachments = $this->get_attachments($id);

                if ($invoice->project_id != 0) {
                    $this->load->model('projects_model');
                    $invoice->project_data = $this->projects_model->get($invoice->project_id);
                }

                $invoice->visible_attachments_to_customer_found = false;
                foreach ($invoice->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $invoice->visible_attachments_to_customer_found = true;

                        break;
                    }
                }

                $client          = $this->clients_model->get($invoice->clientid);
                $invoice->client = $client;
                if (!$invoice->client) {
                    $invoice->client          = new stdClass();
                    $invoice->client->company = $invoice->deleted_customer_name;
                }

                $this->load->model('payments_model');
                $invoice->payments = $this->payments_model->get_invoice_payments($id);
            }

            return $invoice;
        }

        $this->db->order_by('number,YEAR(date)', 'desc');

        return $this->db->get()->result_array();
    }

    public function get_invoice_item($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'itemable')->row();
    }

    public function mark_as_cancelled($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'invoices', [
            'status' => self::STATUS_CANCELLED,
            'sent'   => 1,
        ]);
        if ($this->db->affected_rows() > 0) {
            $this->log_invoice_activity($id, 'invoice_activity_marked_as_cancelled');
            hooks()->do_action('invoice_marked_as_cancelled', $id);

            return true;
        }

        return false;
    }

    public function unmark_as_cancelled($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'invoices', [
            'status' => self::STATUS_UNPAID,
        ]);
        if ($this->db->affected_rows() > 0) {
            $this->log_invoice_activity($id, 'invoice_activity_unmarked_as_cancelled');

            return true;
        }

        return false;
    }

    /**
     * Get this invoice generated recurring invoices
     * @since  Version 1.0.1
     * @param  mixed $id main invoice id
     * @return array
     */
    public function get_invoice_recurring_invoices($id)
    {
        $this->db->select('id');
        $this->db->where('is_recurring_from', $id);
        $invoices           = $this->db->get(db_prefix() . 'invoices')->result_array();
        $recurring_invoices = [];
        foreach ($invoices as $invoice) {
            $recurring_invoices[] = $this->get($invoice['id']);
        }

        return $recurring_invoices;
    }

    /**
     * Get invoice total from all statuses
     * @since  Version 1.0.2
     * @param  mixed $data $_POST data
     * @return array
     */
    public function get_invoices_total($data)
    {
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

        $result            = [];
        $result['due']     = [];
        $result['paid']    = [];
        $result['overdue'] = [];

        $has_permission_view                = has_permission('invoices', '', 'view');
        $has_permission_view_own            = has_permission('invoices', '', 'view_own');
        $allow_staff_view_invoices_assigned = get_option('allow_staff_view_invoices_assigned');
        $noPermissionsQuery                 = get_invoices_where_sql_for_staff(get_staff_user_id());

        for ($i = 1; $i <= 3; $i++) {
            $select = 'id,total';
            if ($i == 1) {
                $select .= ', (SELECT total - (SELECT COALESCE(SUM(amount),0) FROM ' . db_prefix() . 'invoicepaymentrecords WHERE invoiceid = ' . db_prefix() . 'invoices.id) - (SELECT COALESCE(SUM(amount),0) FROM ' . db_prefix() . 'credits WHERE ' . db_prefix() . 'credits.invoice_id=' . db_prefix() . 'invoices.id)) as outstanding';
            } elseif ($i == 2) {
                $select .= ',(SELECT SUM(amount) FROM ' . db_prefix() . 'invoicepaymentrecords WHERE invoiceid=' . db_prefix() . 'invoices.id) as total_paid';
            }
            $this->db->select($select);
            $this->db->from(db_prefix() . 'invoices');
            $this->db->where('currency', $currencyid);
            // Exclude cancelled invoices
            $this->db->where('status !=', self::STATUS_CANCELLED);
            // Exclude draft
            $this->db->where('status !=', self::STATUS_DRAFT);

            if (isset($data['project_id']) && $data['project_id'] != '') {
                $this->db->where('project_id', $data['project_id']);
            } elseif (isset($data['customer_id']) && $data['customer_id'] != '') {
                $this->db->where('clientid', $data['customer_id']);
            }

            if ($i == 3) {
                $this->db->where('status', self::STATUS_OVERDUE);
            } elseif ($i == 1) {
                $this->db->where('status !=', self::STATUS_PAID);
            }

            if (isset($data['years']) && count($data['years']) > 0) {
                $this->db->where_in('YEAR(date)', $data['years']);
            } else {
                $this->db->where('YEAR(date)', date('Y'));
            }

            if (!$has_permission_view) {
                $whereUser = $noPermissionsQuery;
                $this->db->where('(' . $whereUser . ')');
            }

            $invoices = $this->db->get()->result_array();

            foreach ($invoices as $invoice) {
                if ($i == 1) {
                    $result['due'][] = $invoice['outstanding'];
                } elseif ($i == 2) {
                    $result['paid'][] = $invoice['total_paid'];
                } elseif ($i == 3) {
                    $result['overdue'][] = $invoice['total'];
                }
            }
        }
        $currency             = get_currency($currencyid);
        $result['due']        = array_sum($result['due']);
        $result['paid']       = array_sum($result['paid']);
        $result['overdue']    = array_sum($result['overdue']);
        $result['currency']   = $currency;
        $result['currencyid'] = $currencyid;

        return $result;
    }

    /**
     * Insert new invoice to database
     * @param array $data invoice data
     * @return mixed - false if not insert, invoice ID if succes
     */
    public function add($data, $expense = false)
    {
        $data['prefix'] = get_option('invoice_prefix');

        $data['number_format'] = get_option('invoice_number_format');

        $data['datecreated'] = date('Y-m-d H:i:s');

        $save_and_send = isset($data['save_and_send']);

        $data['addedfrom'] = !DEFINED('CRON') ? get_staff_user_id() : 0;

        $data['cancel_overdue_reminders'] = isset($data['cancel_overdue_reminders']) ? 1 : 0;

        $data['allowed_payment_modes'] = isset($data['allowed_payment_modes']) ? serialize($data['allowed_payment_modes']) : serialize([]);

        $billed_tasks = isset($data['billed_tasks']) ? array_map('unserialize', array_unique(array_map('serialize', $data['billed_tasks']))) : [];

        $billed_expenses = isset($data['billed_expenses']) ? array_map('unserialize', array_unique(array_map('serialize', $data['billed_expenses']))) : [];

        $invoices_to_merge = isset($data['invoices_to_merge']) ? $data['invoices_to_merge'] : [];

        $cancel_merged_invoices = isset($data['cancel_merged_invoices']);

        $tags = isset($data['tags']) ? $data['tags'] : '';

        if (isset($data['save_as_draft'])) {
            $data['status'] = self::STATUS_DRAFT;
            unset($data['save_as_draft']);
        }

        if (isset($data['recurring'])) {
            if ($data['recurring'] == 'custom') {
                $data['recurring_type']   = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
                $data['recurring']        = $data['repeat_every_custom'];
            }
        } else {
            $data['custom_recurring'] = 0;
            $data['recurring']        = 0;
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data['hash'] = app_generate_hash();

        $items = [];
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        $data = $this->map_shipping_columns($data, $expense);

        if (isset($data['shipping_street'])) {
            $data['shipping_street'] = trim($data['shipping_street']);
            $data['shipping_street'] = nl2br($data['shipping_street']);
        }

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        $hook = hooks()->apply_filters('before_invoice_added', [
            'data'  => $data,
            'items' => $items,
        ]);

        $data  = $hook['data'];
        $items = $hook['items'];

        $this->db->insert(db_prefix() . 'invoices', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {

            // Update next invoice number in settings
            $this->db->where('name', 'next_invoice_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            handle_tags_save($tags, $insert_id, 'invoice');

            foreach ($invoices_to_merge as $m) {
                $merged   = false;
                $or_merge = $this->get($m);
                if ($cancel_merged_invoices == false) {
                    if ($this->delete($m, true)) {
                        $merged = true;
                    }
                } else {
                    if ($this->mark_as_cancelled($m)) {
                        $merged     = true;
                        $admin_note = $or_merge->adminnote;
                        $note       = 'Merged into invoice ' . format_invoice_number($insert_id);
                        if ($admin_note != '') {
                            $admin_note .= "\n\r" . $note;
                        } else {
                            $admin_note = $note;
                        }
                        $this->db->where('id', $m);
                        $this->db->update(db_prefix() . 'invoices', [
                                'adminnote' => $admin_note,
                            ]);
                        // Delete the old items related from the merged invoice
                        foreach ($or_merge->items as $or_merge_item) {
                            $this->db->where('item_id', $or_merge_item['id']);
                            $this->db->delete(db_prefix() . 'related_items');
                        }
                    }
                }
                if ($merged) {
                    $this->db->where('invoiceid', $or_merge->id);
                    $is_expense_invoice = $this->db->get(db_prefix() . 'expenses')->row();
                    if ($is_expense_invoice) {
                        $this->db->where('id', $is_expense_invoice->id);
                        $this->db->update(db_prefix() . 'expenses', [
                                'invoiceid' => $insert_id,
                            ]);
                    }
                    if (total_rows(db_prefix() . 'estimates', [
                            'invoiceid' => $or_merge->id,
                        ]) > 0) {
                        $this->db->where('invoiceid', $or_merge->id);
                        $estimate = $this->db->get(db_prefix() . 'estimates')->row();
                        $this->db->where('id', $estimate->id);
                        $this->db->update(db_prefix() . 'estimates', [
                                'invoiceid' => $insert_id,
                            ]);
                    } elseif (total_rows(db_prefix() . 'proposals', [
                                'invoice_id' => $or_merge->id,
                            ]) > 0) {
                        $this->db->where('invoice_id', $or_merge->id);
                        $proposal = $this->db->get(db_prefix() . 'proposals')->row();
                        $this->db->where('id', $proposal->id);
                        $this->db->update(db_prefix() . 'proposals', [
                                'invoice_id' => $insert_id,
                            ]);
                    }
                }
            }

            foreach ($billed_tasks as $key => $tasks) {
                foreach ($tasks as $t) {
                    $this->db->select('status')
                        ->where('id', $t);

                    $_task = $this->db->get(db_prefix() . 'tasks')->row();

                    $taskUpdateData = [
                            'billed'     => 1,
                            'invoice_id' => $insert_id,
                        ];

                    if ($_task->status != Tasks_model::STATUS_COMPLETE) {
                        $taskUpdateData['status']       = Tasks_model::STATUS_COMPLETE;
                        $taskUpdateData['datefinished'] = date('Y-m-d H:i:s');
                    }

                    $this->db->where('id', $t);
                    $this->db->update(db_prefix() . 'tasks', $taskUpdateData);
                }
            }

            foreach ($billed_expenses as $key => $val) {
                foreach ($val as $expense_id) {
                    $this->db->where('id', $expense_id);
                    $this->db->update(db_prefix() . 'expenses', [
                            'invoiceid' => $insert_id,
                        ]);
                }
            }

            update_invoice_status($insert_id);

            foreach ($items as $key => $item) {
                if ($itemid = add_new_sales_item_post($item, $insert_id, 'invoice')) {
                    if (isset($billed_tasks[$key])) {
                        foreach ($billed_tasks[$key] as $_task_id) {
                            $this->db->insert(db_prefix() . 'related_items', [
                                    'item_id'  => $itemid,
                                    'rel_id'   => $_task_id,
                                    'rel_type' => 'task',
                                ]);
                        }
                    } elseif (isset($billed_expenses[$key])) {
                        foreach ($billed_expenses[$key] as $_expense_id) {
                            $this->db->insert(db_prefix() . 'related_items', [
                                    'item_id'  => $itemid,
                                    'rel_id'   => $_expense_id,
                                    'rel_type' => 'expense',
                                ]);
                        }
                    }
                    _maybe_insert_post_item_tax($itemid, $item, $insert_id, 'invoice');
                }
            }

            update_sales_total_tax_column($insert_id, 'invoice', db_prefix() . 'invoices');

            if (!DEFINED('CRON') && $expense == false) {
                $lang_key = 'invoice_activity_created';
            } elseif (!DEFINED('CRON') && $expense == true) {
                $lang_key = 'invoice_activity_from_expense';
            } elseif (DEFINED('CRON') && $expense == false) {
                $lang_key = 'invoice_activity_recurring_created';
            } else {
                $lang_key = 'invoice_activity_recurring_from_expense_created';
            }
            $this->log_invoice_activity($insert_id, $lang_key);

            if ($save_and_send === true) {
                $this->send_invoice_to_client($insert_id, '', true, '', true);
            }
            hooks()->do_action('after_invoice_added', $insert_id);

            return $insert_id;
        }

        return false;
    }

    public function get_expenses_to_bill($clientid)
    {
        $this->load->model('expenses_model');
        $where = 'billable=1 AND clientid=' . $clientid . ' AND invoiceid IS NULL';
        if (!has_permission('expenses', '', 'view')) {
            $where .= ' AND ' . db_prefix() . 'expenses.addedfrom=' . get_staff_user_id();
        }

        return $this->expenses_model->get('', $where);
    }

    public function check_for_merge_invoice($client_id, $current_invoice = '')
    {
        if ($current_invoice != '') {
            $this->db->select('status');
            $this->db->where('id', $current_invoice);
            $row = $this->db->get(db_prefix() . 'invoices')->row();
            // Cant merge on paid invoice and partialy paid and cancelled
            if ($row->status == self::STATUS_PAID || $row->status == self::STATUS_PARTIALLY || $row->status == self::STATUS_CANCELLED) {
                return [];
            }
        }

        $statuses = [
            self::STATUS_UNPAID,
            self::STATUS_OVERDUE,
            self::STATUS_DRAFT,
        ];
        $noPermissionsQuery  = get_invoices_where_sql_for_staff(get_staff_user_id());
        $has_permission_view = has_permission('invoices', '', 'view');
        $this->db->select('id');
        $this->db->where('clientid', $client_id);
        $this->db->where('STATUS IN (' . implode(', ', $statuses) . ')');
        if (!$has_permission_view) {
            $whereUser = $noPermissionsQuery;
            $this->db->where('(' . $whereUser . ')');
        }
        if ($current_invoice != '') {
            $this->db->where('id !=', $current_invoice);
        }

        $invoices = $this->db->get(db_prefix() . 'invoices')->result_array();
        $invoices = hooks()->apply_filters('invoices_ids_available_for_merging', $invoices);

        $_invoices = [];

        foreach ($invoices as $invoice) {
            $inv = $this->get($invoice['id']);
            if ($inv) {
                $_invoices[] = $inv;
            }
        }

        return $_invoices;
    }

    /**
     * Copy invoice
     * @param  mixed $id invoice id to copy
     * @return mixed
     */
    public function copy($id)
    {
        $_invoice                     = $this->get($id);
        $new_invoice_data             = [];
        $new_invoice_data['clientid'] = $_invoice->clientid;
        $new_invoice_data['number']   = get_option('next_invoice_number');
        $new_invoice_data['date']     = _d(date('Y-m-d'));

        if ($_invoice->duedate && get_option('invoice_due_after') != 0) {
            $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }

        $new_invoice_data['save_as_draft']    = true;
        $new_invoice_data['recurring_type']   = $_invoice->recurring_type;
        $new_invoice_data['custom_recurring'] = $_invoice->custom_recurring;
        $new_invoice_data['show_quantity_as'] = $_invoice->show_quantity_as;
        $new_invoice_data['currency']         = $_invoice->currency;
        $new_invoice_data['subtotal']         = $_invoice->subtotal;
        $new_invoice_data['total']            = $_invoice->total;
        $new_invoice_data['adminnote']        = $_invoice->adminnote;
        $new_invoice_data['adjustment']       = $_invoice->adjustment;
        $new_invoice_data['discount_percent'] = $_invoice->discount_percent;
        $new_invoice_data['discount_total']   = $_invoice->discount_total;
        $new_invoice_data['recurring']        = $_invoice->recurring;
        $new_invoice_data['discount_type']    = $_invoice->discount_type;
        $new_invoice_data['terms']            = $_invoice->terms;
        $new_invoice_data['sale_agent']       = $_invoice->sale_agent;
        $new_invoice_data['project_id']       = $_invoice->project_id;
        $new_invoice_data['cycles']           = $_invoice->cycles;
        $new_invoice_data['total_cycles']     = 0;
        // Since version 1.0.6
        $new_invoice_data['billing_street']   = clear_textarea_breaks($_invoice->billing_street);
        $new_invoice_data['billing_city']     = $_invoice->billing_city;
        $new_invoice_data['billing_state']    = $_invoice->billing_state;
        $new_invoice_data['billing_zip']      = $_invoice->billing_zip;
        $new_invoice_data['billing_country']  = $_invoice->billing_country;
        $new_invoice_data['shipping_street']  = clear_textarea_breaks($_invoice->shipping_street);
        $new_invoice_data['shipping_city']    = $_invoice->shipping_city;
        $new_invoice_data['shipping_state']   = $_invoice->shipping_state;
        $new_invoice_data['shipping_zip']     = $_invoice->shipping_zip;
        $new_invoice_data['shipping_country'] = $_invoice->shipping_country;
        if ($_invoice->include_shipping == 1) {
            $new_invoice_data['include_shipping'] = $_invoice->include_shipping;
        }
        $new_invoice_data['show_shipping_on_invoice'] = $_invoice->show_shipping_on_invoice;
        // Set to unpaid status automatically
        $new_invoice_data['status']                = self::STATUS_UNPAID;
        $new_invoice_data['clientnote']            = $_invoice->clientnote;
        $new_invoice_data['adminnote']             = $_invoice->adminnote;
        $new_invoice_data['allowed_payment_modes'] = unserialize($_invoice->allowed_payment_modes);
        $new_invoice_data['newitems']              = [];
        $key                                       = 1;

        $custom_fields_items = get_custom_fields('items');
        foreach ($_invoice->items as $item) {
            $new_invoice_data['newitems'][$key]['description']      = $item['description'];
            $new_invoice_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_invoice_data['newitems'][$key]['qty']              = $item['qty'];
            $new_invoice_data['newitems'][$key]['unit']             = $item['unit'];
            $new_invoice_data['newitems'][$key]['taxname']          = [];
            $taxes                                                  = get_invoice_item_taxes($item['id']);
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
        $id = $this->invoices_model->add($new_invoice_data);
        if ($id) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'invoices', [
                'cancel_overdue_reminders' => $_invoice->cancel_overdue_reminders,
            ]);

            $custom_fields = get_custom_fields('invoice');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($_invoice->id, $field['id'], 'invoice', false);
                if ($value == '') {
                    continue;
                }
                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'invoice',
                    'value'   => $value,
                ]);
            }

            $tags = get_tags_in($_invoice->id, 'invoice');
            handle_tags_save($tags, $id, 'invoice');

            log_activity('Copied Invoice ' . format_invoice_number($_invoice->id));

            hooks()->do_action('invoice_copied', ['copy_from' => $_invoice->id, 'copy_id' => $id]);

            return $id;
        }

        return false;
    }

    /**
     * Update invoice data
     * @param  array $data invoice data
     * @param  mixed $id   invoiceid
     * @return boolean
     */
    public function update($data, $id)
    {
        $original_invoice = $this->get($id);
        $affectedRows     = 0;

        $data['number'] = trim($data['number']);

        $original_number_formatted = format_invoice_number($id);

        $original_number = $original_invoice->number;

        $save_and_send = isset($data['save_and_send']);

        $cancel_merged_invoices = isset($data['cancel_merged_invoices']);

        $invoices_to_merge = isset($data['invoices_to_merge']) ? $data['invoices_to_merge'] : [];

        $billed_tasks = isset($data['billed_tasks']) ? $data['billed_tasks'] : [];

        $billed_expenses = isset($data['billed_expenses']) ? array_map('unserialize', array_unique(array_map('serialize', $data['billed_expenses']))) : [];

        $data['cancel_overdue_reminders'] = isset($data['cancel_overdue_reminders']) ? 1 : 0;

        $data['cycles'] = !isset($data['cycles']) ? 0 : $data['cycles'];

        $data['allowed_payment_modes'] = isset($data['allowed_payment_modes']) ? serialize($data['allowed_payment_modes']) : serialize([]);

        // Recurring invoice set to NO, Cancelled
        if ($original_invoice->recurring != 0 && $data['recurring'] == 0) {
            $data['cycles']              = 0;
            $data['total_cycles']        = 0;
            $data['last_recurring_date'] = null;
        }

        if (isset($data['recurring'])) {
            if ($data['recurring'] == 'custom') {
                $data['recurring_type']   = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
                $data['recurring']        = $data['repeat_every_custom'];
            } else {
                $data['recurring_type']   = null;
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['custom_recurring'] = 0;
            $data['recurring']        = 0;
            $data['recurring_type']   = null;
        }

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
            if (handle_tags_save($data['tags'], $id, 'invoice')) {
                $affectedRows++;
            }
        }

        $data = $this->map_shipping_columns($data);

        $data['billing_street']  = trim($data['billing_street']);
        $data['shipping_street'] = trim($data['shipping_street']);

        $data['billing_street']  = nl2br($data['billing_street']);
        $data['shipping_street'] = nl2br($data['shipping_street']);

        $hook = hooks()->apply_filters('before_invoice_updated', [
            'data'          => $data,
            'items'         => $items,
            'newitems'      => $newitems,
            'removed_items' => isset($data['removed_items']) ? $data['removed_items'] : [],
        ], $id);

        $data                  = $hook['data'];
        $items                 = $hook['items'];
        $newitems              = $hook['newitems'];
        $data['removed_items'] = $hook['removed_items'];

        foreach ($billed_tasks as $key => $tasks) {
            foreach ($tasks as $t) {
                $this->db->select('status')
                    ->where('id', $t);
                $_task          = $this->db->get(db_prefix() . 'tasks')->row();
                $taskUpdateData = [
                        'billed'     => 1,
                        'invoice_id' => $id,
                    ];
                if ($_task->status != Tasks_model::STATUS_COMPLETE) {
                    $taskUpdateData['status']       = Tasks_model::STATUS_COMPLETE;
                    $taskUpdateData['datefinished'] = date('Y-m-d H:i:s');
                }
                $this->db->where('id', $t);
                $this->db->update(db_prefix() . 'tasks', $taskUpdateData);
            }
        }

        foreach ($billed_expenses as $key => $val) {
            foreach ($val as $expense_id) {
                $this->db->where('id', $expense_id);
                $this->db->update(db_prefix() . 'expenses', [
                        'invoiceid' => $id,
                    ]);
            }
        }

        // Delete items checked to be removed from database
        foreach ($data['removed_items'] as $remove_item_id) {
            $original_item = $this->get_invoice_item($remove_item_id);
            if (handle_removed_sales_item_post($remove_item_id, 'invoice')) {
                $affectedRows++;

                $this->log_invoice_activity($id, 'invoice_estimate_activity_removed_item', false, serialize([
                        $original_item->description,
                    ]));

                $this->db->where('item_id', $original_item->id);
                $related_items = $this->db->get(db_prefix() . 'related_items')->result_array();

                foreach ($related_items as $rel_item) {
                    if ($rel_item['rel_type'] == 'task') {
                        $this->db->where('id', $rel_item['rel_id']);
                        $this->db->update(db_prefix() . 'tasks', [
                                'invoice_id' => null,
                                'billed'     => 0,
                            ]);
                    } elseif ($rel_item['rel_type'] == 'expense') {
                        $this->db->where('id', $rel_item['rel_id']);
                        $this->db->update(db_prefix() . 'expenses', [
                                'invoiceid' => null,
                            ]);
                    }
                    $this->db->where('item_id', $original_item->id);
                    $this->db->delete(db_prefix() . 'related_items');
                }
            }
        }

        unset($data['removed_items']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'invoices', $data);

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            if ($original_number != $data['number']) {
                $this->log_invoice_activity($original_invoice->id, 'invoice_activity_number_changed', false, serialize([
                    $original_number_formatted,
                    format_invoice_number($original_invoice->id),
                ]));
            }
        }

        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $original_item = $this->get_invoice_item($item['itemid']);

                if (update_sales_item_post($item['itemid'], $item, 'item_order')) {
                    $affectedRows++;
                }

                if (update_sales_item_post($item['itemid'], $item, 'unit')) {
                    $affectedRows++;
                }

                if (update_sales_item_post($item['itemid'], $item, 'description')) {
                    $this->log_invoice_activity($id, 'invoice_estimate_activity_updated_item_short_description', false, serialize([
                        $original_item->description,
                        $item['description'],
                    ]));
                    $affectedRows++;
                }

                if (update_sales_item_post($item['itemid'], $item, 'long_description')) {
                    $this->log_invoice_activity($id, 'invoice_estimate_activity_updated_item_long_description', false, serialize([
                        $original_item->long_description,
                        $item['long_description'],
                    ]));
                    $affectedRows++;
                }

                if (update_sales_item_post($item['itemid'], $item, 'rate')) {
                    $this->log_invoice_activity($id, 'invoice_estimate_activity_updated_item_rate', false, serialize([
                        $original_item->rate,
                        $item['rate'],
                    ]));
                    $affectedRows++;
                }

                if (update_sales_item_post($item['itemid'], $item, 'qty')) {
                    $this->log_invoice_activity($id, 'invoice_estimate_activity_updated_qty_item', false, serialize([
                        $item['description'],
                        $original_item->qty,
                        $item['qty'],
                    ]));
                    $affectedRows++;
                }

                if (isset($item['custom_fields'])) {
                    if (handle_custom_fields_post($item['itemid'], $item['custom_fields'])) {
                        $affectedRows++;
                    }
                }

                if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                    if (delete_taxes_from_item($item['itemid'], 'invoice')) {
                        $affectedRows++;
                    }
                } else {
                    $item_taxes        = get_invoice_item_taxes($item['itemid']);
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
                    if (_maybe_insert_post_item_tax($item['itemid'], $item, $id, 'invoice')) {
                        $affectedRows++;
                    }
                }
            }
        }

        foreach ($newitems as $key => $item) {
            if ($new_item_added = add_new_sales_item_post($item, $id, 'invoice')) {
                if (isset($billed_tasks[$key])) {
                    foreach ($billed_tasks[$key] as $_task_id) {
                        $this->db->insert(db_prefix() . 'related_items', [
                                'item_id'  => $new_item_added,
                                'rel_id'   => $_task_id,
                                'rel_type' => 'task',
                            ]);
                    }
                } elseif (isset($billed_expenses[$key])) {
                    foreach ($billed_expenses[$key] as $_expense_id) {
                        $this->db->insert(db_prefix() . 'related_items', [
                                'item_id'  => $new_item_added,
                                'rel_id'   => $_expense_id,
                                'rel_type' => 'expense',
                            ]);
                    }
                }
                _maybe_insert_post_item_tax($new_item_added, $item, $id, 'invoice');

                $this->log_invoice_activity($id, 'invoice_estimate_activity_added_item', false, serialize([
                        $item['description'],
                    ]));
                $affectedRows++;
            }
        }

        foreach ($invoices_to_merge as $m) {
            $merged   = false;
            $or_merge = $this->get($m);
            if ($cancel_merged_invoices == false) {
                if ($this->delete($m, true)) {
                    $merged = true;
                }
            } else {
                if ($this->mark_as_cancelled($m)) {
                    $merged     = true;
                    $admin_note = $or_merge->adminnote;
                    $note       = 'Merged into invoice ' . format_invoice_number($id);
                    if ($admin_note != '') {
                        $admin_note .= "\n\r" . $note;
                    } else {
                        $admin_note = $note;
                    }
                    $this->db->where('id', $m);
                    $this->db->update(db_prefix() . 'invoices', [
                            'adminnote' => $admin_note,
                        ]);
                }
            }
            if ($merged) {
                $this->db->where('invoiceid', $or_merge->id);
                $is_expense_invoice = $this->db->get(db_prefix() . 'expenses')->row();
                if ($is_expense_invoice) {
                    $this->db->where('id', $is_expense_invoice->id);
                    $this->db->update(db_prefix() . 'expenses', [
                            'invoiceid' => $id,
                        ]);
                }
                if (total_rows(db_prefix() . 'estimates', [
                        'invoiceid' => $or_merge->id,
                    ]) > 0) {
                    $this->db->where('invoiceid', $or_merge->id);
                    $estimate = $this->db->get(db_prefix() . 'estimates')->row();
                    $this->db->where('id', $estimate->id);
                    $this->db->update(db_prefix() . 'estimates', [
                            'invoiceid' => $id,
                        ]);
                } elseif (total_rows(db_prefix() . 'proposals', [
                            'invoice_id' => $or_merge->id,
                        ]) > 0) {
                    $this->db->where('invoice_id', $or_merge->id);
                    $proposal = $this->db->get(db_prefix() . 'proposals')->row();
                    $this->db->where('id', $proposal->id);
                    $this->db->update(db_prefix() . 'proposals', [
                            'invoice_id' => $id,
                        ]);
                }
            }
        }

        if ($affectedRows > 0) {
            update_sales_total_tax_column($id, 'invoice', db_prefix() . 'invoices');
            update_invoice_status($id);
        }

        if ($save_and_send === true) {
            $this->send_invoice_to_client($id, '', true, '', true);
        }

        if ($affectedRows > 0) {
            hooks()->do_action('after_invoice_updated', $id);

            return true;
        }

        return false;
    }

    public function get_attachments($invoiceid, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $invoiceid);
        }
        $this->db->where('rel_type', 'invoice');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     *  Delete invoice attachment
     * @since  Version 1.0.4
     * @param   mixed $id  attachmentid
     * @return  boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('invoice') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Invoice Attachment Deleted [InvoiceID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('invoice') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('invoice') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('invoice') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Delete invoice items and all connections
     * @param  mixed $id invoiceid
     * @return boolean
     */
    public function delete($id, $simpleDelete = false)
    {
        if (get_option('delete_only_on_last_invoice') == 1 && $simpleDelete == false) {
            if (!is_last_invoice($id)) {
                return false;
            }
        }
        $number = format_invoice_number($id);

        hooks()->do_action('before_invoice_deleted', $id);
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'invoices');
        if ($this->db->affected_rows() > 0) {
            if (get_option('invoice_number_decrement_on_delete') == 1 && $simpleDelete == false) {
                $current_next_invoice_number = get_option('next_invoice_number');
                if ($current_next_invoice_number > 1) {
                    // Decrement next invoice number to
                    $this->db->where('name', 'next_invoice_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update(db_prefix() . 'options');
                }
            }
            if ($simpleDelete == false) {
                $this->db->where('invoiceid', $id);
                $this->db->update(db_prefix() . 'expenses', [
                    'invoiceid' => null,
                ]);

                $this->db->where('invoice_id', $id);
                $this->db->update(db_prefix() . 'proposals', [
                    'invoice_id'     => null,
                    'date_converted' => null,
                ]);

                $this->db->where('invoice_id', $id);
                $this->db->update(db_prefix() . 'tasks', [
                    'invoice_id' => null,
                    'billed'     => 0,
                ]);

                // if is converted from estimate set the estimate invoice to null
                if (total_rows(db_prefix() . 'estimates', [
                    'invoiceid' => $id,
                ]) > 0) {
                    $this->db->where('invoiceid', $id);
                    $estimate = $this->db->get(db_prefix() . 'estimates')->row();
                    $this->db->where('id', $estimate->id);
                    $this->db->update(db_prefix() . 'estimates', [
                        'invoiceid'     => null,
                        'invoiced_date' => null,
                    ]);
                    $this->load->model('estimates_model');
                    $this->estimates_model->log_estimate_activity($estimate->id, 'not_estimate_invoice_deleted');
                }
            }

            $this->db->select('id');
            $this->db->where('id IN (SELECT credit_id FROM ' . db_prefix() . 'credits WHERE invoice_id=' . $id . ')');
            $linked_credit_notes = $this->db->get(db_prefix() . 'creditnotes')->result_array();

            $this->db->where('invoice_id', $id);
            $this->db->delete(db_prefix() . 'credits');

            $this->load->model('credit_notes_model');
            foreach ($linked_credit_notes as $credit_note) {
                $this->credit_notes_model->update_credit_note_status($credit_note['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'invoice');
            $this->db->delete(db_prefix() . 'notes');

            delete_tracked_emails($id, 'invoice');

            $this->db->where('rel_type', 'invoice');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'taggables');

            $this->db->where('rel_type', 'invoice');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_type', 'invoice');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="invoice" AND rel_id="' . $id . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $items = get_items_by_type('invoice', $id);

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'invoice');
            $this->db->delete(db_prefix() . 'itemable');

            foreach ($items as $item) {
                $this->db->where('item_id', $item['id']);
                $this->db->delete(db_prefix() . 'related_items');
            }

            $this->db->where('invoiceid', $id);
            $this->db->delete(db_prefix() . 'invoicepaymentrecords');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'invoice');
            $this->db->delete(db_prefix() . 'sales_activity');

            $this->db->where('is_recurring_from', $id);
            $this->db->update(db_prefix() . 'invoices', [
                'is_recurring_from' => null,
            ]);

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'invoice');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'invoice');
            $this->db->delete(db_prefix() . 'item_tax');

            // Get billed tasks for this invoice and set to unbilled
            $this->db->where('invoice_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->db->where('id', $task['id']);
                $this->db->update(db_prefix() . 'tasks', [
                    'invoice_id' => null,
                    'billed'     => 0,
                ]);
            }

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }
            // Get related tasks
            $this->db->where('rel_type', 'invoice');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            if ($simpleDelete == false) {
                log_activity('Invoice Deleted [' . $number . ']');
            }

            return true;
        }

        return false;
    }

    /**
     * Set invoice to sent when email is successfuly sended to client
     * @param mixed $id invoiceid
     * @param  mixed $manually is staff manually marking this invoice as sent
     * @return  boolean
     */
    public function set_invoice_sent($id, $manually = false, $emails_sent = [], $is_status_updated = false)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'invoices', [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ]);
        $marked = false;
        if ($this->db->affected_rows() > 0) {
            $marked = true;
        }
        if (DEFINED('CRON')) {
            $additional_activity_data = serialize([
                '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
            ]);
            $description = 'invoice_activity_sent_to_client_cron';
        } else {
            if ($manually == false) {
                $additional_activity_data = serialize([
                    '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
                ]);
                $description = 'invoice_activity_sent_to_client';
            } else {
                $additional_activity_data = serialize([]);
                $description              = 'invoice_activity_marked_as_sent';
            }
        }

        if ($is_status_updated == false) {
            update_invoice_status($id, true);
        }

        $this->log_invoice_activity($id, $description, false, $additional_activity_data);

        return $marked;
    }

    /**
     * Send overdue notice to client for this invoice
     * @param  mxied  $id   invoiceid
     * @return boolean
     */
    public function send_invoice_overdue_notice($id)
    {
        $invoice        = $this->get($id);
        $invoice_number = format_invoice_number($invoice->id);
        set_mailing_constant();
        $pdf = invoice_pdf($invoice);

        $attach_pdf = hooks()->apply_filters('invoice_overdue_notice_attach_pdf', true);

        if ($attach_pdf === true) {
            $attach = $pdf->Output($invoice_number . '.pdf', 'S');
        }

        $emails_sent      = [];
        $email_sent       = false;
        $sms_sent         = false;
        $sms_reminder_log = [];

        // For all cases update this to prevent sending multiple reminders eq on fail
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'invoices', [
            'last_overdue_reminder' => date('Y-m-d'),
        ]);

        $contacts = $this->clients_model->get_contacts($invoice->clientid, ['active' => 1, 'invoice_emails' => 1]);
        foreach ($contacts as $contact) {
            $template = mail_template('invoice_overdue_notice', $invoice, $contact);

            if ($attach_pdf === true) {
                $template->add_attachment([
                        'attachment' => $attach,
                        'filename'   => str_replace('/', '-', $invoice_number . '.pdf'),
                        'type'       => 'application/pdf',
                    ]);
            }

            $merge_fields = $template->get_merge_fields();

            if ($template->send()) {
                array_push($emails_sent, $contact['email']);
                $email_sent = true;
            }

            if (can_send_sms_based_on_creation_date($invoice->datecreated)
                && $this->app_sms->trigger(SMS_TRIGGER_INVOICE_OVERDUE, $contact['phonenumber'], $merge_fields)) {
                $sms_sent = true;
                array_push($sms_reminder_log, $contact['firstname'] . ' (' . $contact['phonenumber'] . ')');
            }
        }

        if ($email_sent || $sms_sent) {
            if ($email_sent) {
                $this->log_invoice_activity($id, 'user_sent_overdue_reminder', false, serialize([
                    '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
                    defined('CRON') ? ' ' : get_staff_full_name(),
                ]));
            }

            if ($sms_sent) {
                $this->log_invoice_activity($id, 'sms_reminder_sent_to', false, serialize([
                   implode(', ', $sms_reminder_log),
                ]));
            }

            hooks()->do_action('invoice_overdue_reminder_sent', [
                'invoice_id' => $id,
                'sent_to'    => $emails_sent,
                'sms_send'   => $sms_sent,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Send invoice to client
     * @param  mixed  $id        invoiceid
     * @param  string  $template  email template to sent
     * @param  boolean $attachpdf attach invoice pdf or not
     * @return boolean
     */
    public function send_invoice_to_client($id, $template_name = '', $attachpdf = true, $cc = '', $manually = false, $attachStatement = [])
    {
        $invoice = $this->get($id);

        $invoice = hooks()->apply_filters('invoice_object_before_send_to_client', $invoice);

        if ($template_name == '') {
            if ($invoice->sent == 0) {
                $template_name = 'invoice_send_to_customer';
            } else {
                $template_name = 'invoice_send_to_customer_already_sent';
            }
            $template_name = hooks()->apply_filters('after_invoice_sent_template_statement', $template_name);
        }
        $invoice_number = format_invoice_number($invoice->id);

        $emails_sent = [];
        $sent        = false;
        $sent_to     = [];
        // Manually is used when sending the invoice via add/edit area button Save & Send
        if (!DEFINED('CRON') && $manually === false) {
            $sent_to = $this->input->post('sent_to');
        } else {
            $contacts = $this->clients_model->get_contacts($invoice->clientid, ['active' => 1, 'invoice_emails' => 1]);

            foreach ($contacts as $contact) {
                array_push($sent_to, $contact['id']);
            }
        }

        $attachStatementPdf = false;
        if (is_array($sent_to) && count($sent_to) > 0) {
            if (isset($attachStatement['attach']) && $attachStatement['attach'] == true) {
                $statement    = $this->clients_model->get_statement($invoice->clientid, $attachStatement['from'], $attachStatement['to']);
                $statementPdf = statement_pdf($statement);

                $statementPdfFileName = slug_it(_l('customer_statement') . '-' . $statement['client']->company);

                $attachStatementPdf = $statementPdf->Output($statementPdfFileName . '.pdf', 'S');
            }

            $status_updated = update_invoice_status($invoice->id, true, true);

            if ($attachpdf) {
                $_pdf_invoice = $this->get($id);
                set_mailing_constant();
                $pdf    = invoice_pdf($_pdf_invoice);
                $attach = $pdf->Output($invoice_number . '.pdf', 'S');
            }

            $i = 0;
            foreach ($sent_to as $contact_id) {
                if ($contact_id != '') {

                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $contact = $this->clients_model->get_contact($contact_id);

                    $template = mail_template($template_name, $invoice, $contact, $cc);

                    if ($attachpdf) {
                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => str_replace('/', '-', $invoice_number . '.pdf'),
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($attachStatementPdf) {
                        $template->add_attachment([
                            'attachment' => $attachStatementPdf,
                            'filename'   => $statementPdfFileName,
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
            $this->set_invoice_sent($id, false, $emails_sent, true);
            hooks()->do_action('invoice_sent', $id);

            return true;
        }
        // In case the invoice not sended and the status was draft and the invoice status is updated before send return back to draft status
        if ($invoice->status == self::STATUS_DRAFT && $status_updated !== false) {
            $this->db->where('id', $invoice->id);
            $this->db->update(db_prefix() . 'invoices', [
                    'status' => self::STATUS_DRAFT,
                ]);
        }

        return false;
    }

    /**
     * All invoice activity
     * @param  mixed $id invoiceid
     * @return array
     */
    public function get_invoice_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'invoice');
        $this->db->order_by('date', 'asc');

        return $this->db->get(db_prefix() . 'sales_activity')->result_array();
    }

    /**
     * Log invoice activity to database
     * @param  mixed $id   invoiceid
     * @param  string $description activity description
     */
    public function log_invoice_activity($id, $description = '', $client = false, $additional_data = '')
    {
        if (DEFINED('CRON')) {
            $staffid   = '[CRON]';
            $full_name = '[CRON]';
        } elseif (defined('STRIPE_SUBSCRIPTION_INVOICE')) {
            $staffid   = null;
            $full_name = '[Stripe]';
        } elseif ($client == true) {
            $staffid   = null;
            $full_name = '';
        } else {
            $staffid   = get_staff_user_id();
            $full_name = get_staff_full_name(get_staff_user_id());
        }
        $this->db->insert(db_prefix() . 'sales_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'invoice',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    public function get_invoices_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'invoices ORDER BY year DESC')->result_array();
    }

    private function map_shipping_columns($data, $expense = false)
    {
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_invoice'] = 1;
            $data['include_shipping']         = 0;
        } else {
            // We dont need to overwrite to 1 unless its coming from the main function add
            if (!DEFINED('CRON') && $expense == false) {
                $data['include_shipping'] = 1;
                // set by default for the next time to be checked
                if (isset($data['show_shipping_on_invoice']) && ($data['show_shipping_on_invoice'] == 1 || $data['show_shipping_on_invoice'] == 'on')) {
                    $data['show_shipping_on_invoice'] = 1;
                } else {
                    $data['show_shipping_on_invoice'] = 0;
                }
            }
            // else its just like they are passed
        }

        return $data;
    }
}
