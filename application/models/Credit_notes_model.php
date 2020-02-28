<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Credit_notes_model extends App_Model
{
    private $shipping_fields = ['shipping_street', 'shipping_city', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('invoices_model');
    }

    public function get_statuses()
    {
        return hooks()->apply_filters('before_get_credit_notes_statuses', [
            [
                'id'             => 1,
                'color'          => '#03a9f4',
                'name'           => _l('credit_note_status_open'),
                'order'          => 1,
                'filter_default' => true,
                ],
             [
                'id'             => 2,
                'color'          => '#84c529',
                'name'           => _l('credit_note_status_closed'),
                'order'          => 2,
                'filter_default' => true,
             ],
             [
                'id'             => 3,
                'color'          => '#777',
                'name'           => _l('credit_note_status_void'),
                'order'          => 3,
                'filter_default' => false,
             ],
        ]);
    }

    public function get_available_creditable_invoices($credit_note_id)
    {
        $has_permission_view = has_permission('invoices', '', 'view');

        $invoices_statuses_available_for_credits = invoices_statuses_available_for_credits();
        $this->db->select('clientid');
        $this->db->where('id', $credit_note_id);
        $credit_note = $this->db->get(db_prefix() . 'creditnotes')->row();

        $this->db->select('' . db_prefix() . 'invoices.id as id, status, total, date, '.db_prefix().'currencies.name as currency_name');
        $this->db->where('clientid', $credit_note->clientid);
        $this->db->where('status IN (' . implode(', ', $invoices_statuses_available_for_credits) . ')');
        if (!$has_permission_view) {
            $this->db->where('addedfrom', get_staff_user_id());
        }
        $this->db->join(db_prefix() . 'currencies', '' . db_prefix() . 'currencies.id = ' . db_prefix() . 'invoices.currency');
        $invoices = $this->db->get(db_prefix() . 'invoices')->result_array();

        foreach ($invoices as $key => $invoice) {
            $invoices[$key]['total_left_to_pay'] = get_invoice_total_left_to_pay($invoice['id'], $invoice['total']);
        }

        return $invoices;
    }

    /**
    * Send credit note to client
    * @param  mixed  $id        credit note id
    * @param  string  $template  email template to sent
    * @param  boolean $attachpdf attach credit note pdf or not
    * @return boolean
    */
    public function send_credit_note_to_client($id, $attachpdf = true, $cc = '', $manually = false)
    {
        $credit_note = $this->get($id);
        $number      = format_credit_note_number($credit_note->id);

        $sent    = false;
        $sent_to = $this->input->post('sent_to');

        if ($manually === true) {
            $sent_to  = [];
            $contacts = $this->clients_model->get_contacts($credit_note->clientid, ['active' => 1, 'credit_note_emails' => 1]);
            foreach ($contacts as $contact) {
                array_push($sent_to, $contact['id']);
            }
        }

        if (is_array($sent_to) && count($sent_to) > 0) {
            if ($attachpdf) {
                set_mailing_constant();
                $pdf    = credit_note_pdf($credit_note);
                $attach = $pdf->Output($number . '.pdf', 'S');
            }
            $i = 0;
            foreach ($sent_to as $contact_id) {
                if ($contact_id != '') {
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }
                    $contact = $this->clients_model->get_contact($contact_id);

                    $template = mail_template('credit_note_send_to_customer', $credit_note, $contact, $cc);

                    if ($attachpdf) {
                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => str_replace('/', '-', $number . '.pdf'),
                            'type'       => 'application/pdf',
                        ]);
                    }
                    if ($template->send()) {
                        $sent = true;
                    }
                }
                $i++;
            }
        } else {
            return false;
        }

        if ($sent) {
            hooks()->do_action('credit_note_sent', $id);

            return true;
        }

        return false;
    }

    /**
     * Get credit note/s
     * @param  mixed $id    credit note id
     * @param  array  $where perform where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*,' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'creditnotes.id as id, ' . db_prefix() . 'currencies.name as currency_name');
        $this->db->from(db_prefix() . 'creditnotes');
        $this->db->join(db_prefix() . 'currencies', '' . db_prefix() . 'currencies.id = ' . db_prefix() . 'creditnotes.currency', 'left');
        $this->db->where($where);

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'creditnotes.id', $id);
            $credit_note = $this->db->get()->row();
            if ($credit_note) {
                $credit_note->refunds       = $this->get_refunds($id);
                $credit_note->total_refunds = $this->total_refunds_by_credit_note($id);

                $credit_note->applied_credits   = $this->get_applied_credits($id);
                $credit_note->remaining_credits = $this->total_remaining_credits_by_credit_note($id);
                $credit_note->credits_used      = $this->total_credits_used_by_credit_note($id);

                $credit_note->items  = get_items_by_type('credit_note', $id);
                $credit_note->client = $this->clients_model->get($credit_note->clientid);

                if (!$credit_note->client) {
                    $credit_note->client          = new stdClass();
                    $credit_note->client->company = $credit_note->deleted_customer_name;
                }
                $credit_note->attachments = $this->get_attachments($id);
            }

            return $credit_note;
        }

        $this->db->order_by('number,YEAR(date)', 'desc');

        return $this->db->get()->result_array();
    }

    public function add($data)
    {
        $save_and_send = isset($data['save_and_send']);

        $data['prefix']        = get_option('credit_note_prefix');
        $data['number_format'] = get_option('credit_note_number_format');
        $data['datecreated']   = date('Y-m-d H:i:s');
        $data['addedfrom']     = get_staff_user_id();

        $items = [];
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data = $this->map_shipping_columns($data);

        $hook = hooks()->apply_filters('before_create_credit_note', ['data' => $data, 'items' => $items]);

        $data  = $hook['data'];
        $items = $hook['items'];

        $this->db->insert(db_prefix() . 'creditnotes', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {

            // Update next credit note number in settings
            $this->db->where('name', 'next_credit_note_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            foreach ($items as $key => $item) {
                if ($itemid = add_new_sales_item_post($item, $insert_id, 'credit_note')) {
                    _maybe_insert_post_item_tax($itemid, $item, $insert_id, 'credit_note');
                }
            }

            update_sales_total_tax_column($insert_id, 'credit_note', db_prefix() . 'creditnotes');

            log_activity('Credit Note Created [ID: ' . $insert_id . ']');

            hooks()->do_action('after_create_credit_note', $insert_id);

            if ($save_and_send === true) {
                $this->send_credit_note_to_client($insert_id, true, '', true);
            }

            return $insert_id;
        }

        return false;
    }

    /**
     * Update proposal
     * @param  mixed $data $_POST data
     * @param  mixed $id   proposal id
     * @return boolean
     */
    public function update($data, $id)
    {
        $affectedRows  = 0;
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

        $data = $this->map_shipping_columns($data);

        $hook = hooks()->apply_filters('before_update_credit_note', [
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
            if (handle_removed_sales_item_post($remove_item_id, 'credit_note')) {
                $affectedRows++;
            }
        }
        unset($data['removed_items']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'creditnotes', $data);

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }

        foreach ($items as $key => $item) {
            if (update_sales_item_post($item['itemid'], $item)) {
                $affectedRows++;
            }

            if (isset($item['custom_fields'])) {
                if (handle_custom_fields_post($item['itemid'], $item['custom_fields'])) {
                    $affectedRows++;
                }
            }

            if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                if (delete_taxes_from_item($item['itemid'], 'credit_note')) {
                    $affectedRows++;
                }
            } else {
                $item_taxes        = get_credit_note_item_taxes($item['itemid']);
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
                if (_maybe_insert_post_item_tax($item['itemid'], $item, $id, 'credit_note')) {
                    $affectedRows++;
                }
            }
        }

        foreach ($newitems as $key => $item) {
            if ($new_item_added = add_new_sales_item_post($item, $id, 'credit_note')) {
                _maybe_insert_post_item_tax($new_item_added, $item, $id, 'credit_note');
                $affectedRows++;
            }
        }

        if ($save_and_send === true) {
            $this->send_credit_note_to_client($id, true, '', true);
        }

        if ($affectedRows > 0) {
            $this->update_credit_note_status($id);
            update_sales_total_tax_column($id, 'credit_note', db_prefix() . 'creditnotes');
        }

        if ($affectedRows > 0) {
            log_activity('Credit Note Updated [ID:' . $id . ']');
            hooks()->do_action('after_update_credit_note', $id);

            return true;
        }

        return false;
    }

    /**
    *  Delete credit note attachment
    * @param   mixed $id  attachmentid
    * @return  boolean
    */
    public function delete_attachment($id)
    {
        $attachment = $this->misc_model->get_file($id);

        $deleted = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('credit_note') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Credit Note Attachment Deleted [Credite Note: ' . format_credit_note_number($attachment->rel_id) . ']');
            }
            if (is_dir(get_upload_path_by_type('credit_note') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('credit_note') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('credit_note') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    public function get_attachments($credit_note_id)
    {
        $this->db->where('rel_id', $credit_note_id);
        $this->db->where('rel_type', 'credit_note');

        return $this->db->get(db_prefix() . 'files')->result_array();
    }

    /**
    * Delete credit note
    * @param  mixed $id credit note id
    * @return boolean
    */
    public function delete($id, $simpleDelete = false)
    {
        hooks()->do_action('before_credit_note_deleted', $id);
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'creditnotes');
        if ($this->db->affected_rows() > 0) {
            $current_credit_note_number = get_option('next_credit_note_number');

            if ($current_credit_note_number > 1 && $simpleDelete == false && is_last_credit_note($id)) {
                // Decrement next credit note number
                $this->db->where('name', 'next_credit_note_number');
                $this->db->set('value', 'value-1', false);
                $this->db->update(db_prefix() . 'options');
            }

            delete_tracked_emails($id, 'credit_note');

            // Delete the custom field values
            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="credit_note" AND rel_id="' . $id . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'credit_note');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('credit_id', $id);
            $this->db->delete(db_prefix() . 'credits');

            $this->db->where('credit_note_id', $id);
            $this->db->delete(db_prefix() . 'creditnote_refunds');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'credit_note');
            $this->db->delete(db_prefix() . 'itemable');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'credit_note');
            $this->db->delete(db_prefix() . 'item_tax');

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('rel_type', 'credit_note');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            hooks()->do_action('after_credit_note_deleted', $id);

            return true;
        }

        return false;
    }

    public function mark($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'creditnotes', ['status' => $status]);

        return $this->db->affected_rows() > 0 ? true : false;
    }

    public function total_remaining_credits_by_customer($customer_id)
    {
        $has_permission_view = has_permission('credit_notes', '', 'view');
        $this->db->select('total,id');
        $this->db->where('clientid', $customer_id);
        $this->db->where('status', 1);
        if (!$has_permission_view) {
            $this->db->where('addedfrom', get_staff_user_id());
        }
        $credits = $this->db->get(db_prefix() . 'creditnotes')->result_array();

        $total = $this->calc_remaining_credits($credits);

        return $total;
    }

    public function total_remaining_credits_by_credit_note($credit_note_id)
    {
        $this->db->select('total,id');
        $this->db->where('id', $credit_note_id);
        $credits = $this->db->get(db_prefix() . 'creditnotes')->result_array();

        $total = $this->calc_remaining_credits($credits);

        return $total;
    }

    private function calc_remaining_credits($credits)
    {
        $total       = 0;
        $credits_ids = [];

        $bcadd = function_exists('bcadd');
        foreach ($credits as $credit) {
            if ($bcadd) {
                $total = bcadd($total, $credit['total'], get_decimal_places());
            } else {
                $total += $credit['total'];
            }
            array_push($credits_ids, $credit['id']);
        }

        if (count($credits_ids) > 0) {
            $this->db->where('credit_id IN (' . implode(', ', $credits_ids) . ')');
            $applied_credits = $this->db->get(db_prefix() . 'credits')->result_array();
            $bcsub           = function_exists('bcsub');
            foreach ($applied_credits as $credit) {
                if ($bcsub) {
                    $total = bcsub($total, $credit['amount'], get_decimal_places());
                } else {
                    $total -= $credit['amount'];
                }
            }

            foreach ($credits_ids as $credit_note_id) {
                $total_refunds_by_credit_note = $this->total_refunds_by_credit_note($credit_note_id);
                if ($bcsub) {
                    $total = bcsub($total, $total_refunds_by_credit_note, get_decimal_places());
                } else {
                    $total -= $total_refunds_by_credit_note;
                }
            }
        }

        return $total;
    }

    public function delete_applied_credit($id, $credit_id, $invoice_id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'credits');
        if ($this->db->affected_rows() > 0) {
            $this->update_credit_note_status($credit_id);
            update_invoice_status($invoice_id);
        }
    }

    public function credit_note_from_invoice($invoice_id)
    {
        $_invoice = $this->invoices_model->get($invoice_id);

        $new_credit_note_data             = [];
        $new_credit_note_data['clientid'] = $_invoice->clientid;
        $new_credit_note_data['number']   = get_option('next_credit_note_number');
        $new_credit_note_data['date']     = _d(date('Y-m-d'));

        $new_credit_note_data['show_quantity_as'] = $_invoice->show_quantity_as;
        $new_credit_note_data['currency']         = $_invoice->currency;
        $new_credit_note_data['subtotal']         = $_invoice->subtotal;
        $new_credit_note_data['total']            = $_invoice->total;
        $new_credit_note_data['adminnote']        = $_invoice->adminnote;
        $new_credit_note_data['adjustment']       = $_invoice->adjustment;
        $new_credit_note_data['discount_percent'] = $_invoice->discount_percent;
        $new_credit_note_data['discount_total']   = $_invoice->discount_total;
        $new_credit_note_data['discount_type']    = $_invoice->discount_type;


        $new_credit_note_data['billing_street']   = clear_textarea_breaks($_invoice->billing_street);
        $new_credit_note_data['billing_city']     = $_invoice->billing_city;
        $new_credit_note_data['billing_state']    = $_invoice->billing_state;
        $new_credit_note_data['billing_zip']      = $_invoice->billing_zip;
        $new_credit_note_data['billing_country']  = $_invoice->billing_country;
        $new_credit_note_data['shipping_street']  = clear_textarea_breaks($_invoice->shipping_street);
        $new_credit_note_data['shipping_city']    = $_invoice->shipping_city;
        $new_credit_note_data['shipping_state']   = $_invoice->shipping_state;
        $new_credit_note_data['shipping_zip']     = $_invoice->shipping_zip;
        $new_credit_note_data['shipping_country'] = $_invoice->shipping_country;
        $new_credit_note_data['reference_no']     = format_invoice_number($_invoice->id);
        if ($_invoice->include_shipping == 1) {
            $new_credit_note_data['include_shipping'] = $_invoice->include_shipping;
        }
        $new_credit_note_data['show_shipping_on_credit_note'] = $_invoice->show_shipping_on_invoice;
        $new_credit_note_data['clientnote']                   = get_option('predefined_clientnote_credit_note');
        $new_credit_note_data['terms']                        = get_option('predefined_terms_credit_note');
        $new_credit_note_data['adminnote']                    = '';
        $new_credit_note_data['newitems']                     = [];

        $custom_fields_items = get_custom_fields('items');
        $key                 = 1;
        foreach ($_invoice->items as $item) {
            $new_credit_note_data['newitems'][$key]['description']      = $item['description'];
            $new_credit_note_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_credit_note_data['newitems'][$key]['qty']              = $item['qty'];
            $new_credit_note_data['newitems'][$key]['unit']             = $item['unit'];
            $new_credit_note_data['newitems'][$key]['taxname']          = [];
            $taxes                                                      = get_invoice_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_credit_note_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_credit_note_data['newitems'][$key]['rate']  = $item['rate'];
            $new_credit_note_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_credit_note_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $id = $this->add($new_credit_note_data);
        if ($id) {
            if ($_invoice->status != 2) {
                if ($this->apply_credits($id, ['invoice_id' => $invoice_id, 'amount' => $_invoice->total_left_to_pay])) {
                    update_invoice_status($invoice_id, true);
                }
            }

            log_activity('Created Credit Note From Invoice [Invoice: ' . format_invoice_number($_invoice->id) . ', Credit Note: ' . format_credit_note_number($id) . ']');

            hooks()->do_action('created_credit_note_from_invoice', ['invoice_id' => $invoice_id, 'credit_note_id' => $id]);

            return $id;
        }

        return false;
    }

    public function create_refund($id, $data)
    {
        if ($data['amount'] == 0) {
            return false;
        }

        $data['note'] = trim($data['note']);

        $this->db->insert(db_prefix() . 'creditnote_refunds', [
            'created_at'     => date('Y-m-d H:i:s'),
            'credit_note_id' => $id,
            'staff_id'       => $data['staff_id'],
            'refunded_on'    => $data['refunded_on'],
            'payment_mode'   => $data['payment_mode'],
            'amount'         => $data['amount'],
            'note'           => nl2br($data['note']),
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            $this->update_credit_note_status($id);

            hooks()->do_action('credit_note_refund_created', ['data' => $data, 'credit_note_id' => $id]);
        }

        return $insert_id;
    }

    public function edit_refund($id, $data)
    {
        if ($data['amount'] == 0) {
            return false;
        }

        $refund = $this->get_refund($id);

        $data['note'] = trim($data['note']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'creditnote_refunds', [
            'refunded_on'  => $data['refunded_on'],
            'payment_mode' => $data['payment_mode'],
            'amount'       => $data['amount'],
            'note'         => nl2br($data['note']),
        ]);

        $insert_id = $this->db->insert_id();

        if ($this->db->affected_rows() > 0) {
            $this->update_credit_note_status($refund->credit_note_id);

            hooks()->do_action('credit_note_refund_updated', ['data' => $data, 'refund_id' => $refund->credit_note_id]);
        }

        return $insert_id;
    }

    public function get_refund($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'creditnote_refunds')->row();
    }

    public function get_refunds($credit_note_id)
    {
        $this->db->select(prefixed_table_fields_array(db_prefix() . 'creditnote_refunds', true) . ',' . db_prefix() . 'payment_modes.id as payment_mode_id, ' . db_prefix() . 'payment_modes.name as payment_mode_name');
        $this->db->where('credit_note_id', $credit_note_id);

        $this->db->join(db_prefix() . 'payment_modes', db_prefix() . 'payment_modes.id = ' . db_prefix() . 'creditnote_refunds.payment_mode', 'left');

        $this->db->order_by('refunded_on', 'desc');

        $refunds = $this->db->get(db_prefix() . 'creditnote_refunds')->result_array();

        $this->load->model('payment_modes_model');
        $payment_gateways = $this->payment_modes_model->get_payment_gateways(true);
        $i                = 0;

        foreach ($refunds as $refund) {
            if (is_null($refund['payment_mode_id'])) {
                foreach ($payment_gateways as $gateway) {
                    if ($refund['payment_mode'] == $gateway['id']) {
                        $refunds[$i]['payment_mode_id']   = $gateway['id'];
                        $refunds[$i]['payment_mode_name'] = $gateway['name'];
                    }
                }
            }
            $i++;
        }

        return $refunds;
    }

    public function delete_refund($refund_id, $credit_note_id)
    {
        $this->db->where('id', $refund_id);
        $this->db->delete(db_prefix() . 'creditnote_refunds');
        if ($this->db->affected_rows() > 0) {
            $this->update_credit_note_status($credit_note_id);
            hooks()->do_action('credit_note_refund_deleted', ['refund_id' => $refund_id, 'credit_note_id' => $credit_note_id]);

            return true;
        }

        return false;
    }

    private function total_refunds_by_credit_note($id)
    {
        return sum_from_table(db_prefix() . 'creditnote_refunds', [
                'field' => 'amount',
                'where' => ['credit_note_id' => $id],
            ]);
    }

    public function apply_credits($id, $data)
    {
        if ($data['amount'] == 0) {
            return false;
        }

        $this->db->insert(db_prefix() . 'credits', [
            'invoice_id'   => $data['invoice_id'],
            'credit_id'    => $id,
            'staff_id'     => get_staff_user_id(),
            'date'         => date('Y-m-d'),
            'date_applied' => date('Y-m-d H:i:s'),
            'amount'       => $data['amount'],
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            $this->update_credit_note_status($id);

            $this->db->select(db_prefix().'currencies.name as currency_name');
            $this->db->join(db_prefix() . 'currencies', '' . db_prefix() . 'currencies.id = ' . db_prefix() . 'invoices.currency');
            $this->db->where(db_prefix() . 'invoices.id', $data['invoice_id']);

            $invoice = $this->db->get(db_prefix() . 'invoices')->row();

            $inv_number         = format_invoice_number($data['invoice_id']);
            $credit_note_number = format_credit_note_number($id);

            $this->invoices_model->log_invoice_activity($data['invoice_id'], 'invoice_activity_applied_credits', false, serialize([
                   app_format_money($data['amount'], $invoice->currency_name),
                   $credit_note_number,
             ]));

            hooks()->do_action('credits_applied', ['data' => $data, 'credit_note_id' => $id]);

            log_activity('Credit Applied to Invoice [ Invoice: ' . $inv_number . ', Credit: ' . $credit_note_number . ' ]');

        }

        return $insert_id;
    }

    private function total_credits_used_by_credit_note($id)
    {
        return sum_from_table(db_prefix() . 'credits', [
                'field' => 'amount',
                'where' => ['credit_id' => $id],
            ]);
    }

    public function update_credit_note_status($id)
    {
        $total_refunds_by_credit_note = $this->total_refunds_by_credit_note($id);
        $total_credits_used           = $this->total_credits_used_by_credit_note($id);

        $status = 1;

        // sum from table returns null if nothing found
        if ($total_credits_used || $total_refunds_by_credit_note) {
            $compare = $total_credits_used + $total_refunds_by_credit_note;

            $this->db->select('total');
            $this->db->where('id', $id);
            $credit = $this->db->get(db_prefix() . 'creditnotes')->row();

            if ($credit) {
                if (function_exists('bccomp')) {
                    if (bccomp($credit->total, $compare, get_decimal_places()) === 0) {
                        $status = 2;
                    }
                } else {
                    if ($credit->total == $compare) {
                        $status = 2;
                    }
                }
            }
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'creditnotes', ['status' => $status]);

        return $this->db->affected_rows() > 0 ? true : false;
    }

    public function get_open_credits($customer_id)
    {
        $has_permission_view = has_permission('credit_notes', '', 'view');
        $this->db->where('status', 1);
        $this->db->where('clientid', $customer_id);
        if (!$has_permission_view) {
            $this->db->where('addedfrom', get_staff_user_id());
        }
        $credits = $this->db->get(db_prefix() . 'creditnotes')->result_array();

        foreach ($credits as $key => $credit) {
            $credits[$key]['available_credits'] = $this->calculate_available_credits($credit['id'], $credit['total']);
        }

        return $credits;
    }

    public function get_applied_invoice_credits($invoice_id)
    {
        $this->db->order_by('date', 'desc');
        $this->db->where('invoice_id', $invoice_id);

        return $this->db->get(db_prefix() . 'credits')->result_array();
    }

    public function get_applied_credits($credit_id)
    {
        $this->db->where('credit_id', $credit_id);
        $this->db->order_by('date', 'desc');

        return $this->db->get(db_prefix() . 'credits')->result_array();
    }

    private function calculate_available_credits($credit_id, $credit_amount = false)
    {
        if ($credit_amount === false) {
            $this->db->select('total')
            ->from(db_prefix() . 'creditnotes')
            ->where('id', $credit_id);

            $credit_amount = $this->db->get()->row()->total;
        }

        $available_total = $credit_amount;

        $bcsub           = function_exists('bcsub');
        $applied_credits = $this->get_applied_credits($credit_id);


        foreach ($applied_credits as $credit) {
            if ($bcsub) {
                $available_total = bcsub($available_total, $credit['amount'], get_decimal_places());
            } else {
                $available_total -= $credit['amount'];
            }
        }

        $total_refunds = $this->total_refunds_by_credit_note($credit_id);

        if ($total_refunds) {
            if ($bcsub) {
                $available_total = bcsub($available_total, $total_refunds, get_decimal_places());
            } else {
                $available_total -= $total_refunds;
            }
        }

        return $available_total;
    }

    public function get_credits_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'creditnotes ORDER BY year DESC')->result_array();
    }

    private function map_shipping_columns($data)
    {
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_credit_note'] = 1;
            $data['include_shipping']             = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_credit_note']) && ($data['show_shipping_on_credit_note'] == 1 || $data['show_shipping_on_credit_note'] == 'on')) {
                $data['show_shipping_on_credit_note'] = 1;
            } else {
                $data['show_shipping_on_credit_note'] = 0;
            }
        }

        return $data;
    }
}
