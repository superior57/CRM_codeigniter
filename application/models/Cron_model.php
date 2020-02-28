<?php

defined('BASEPATH') or exit('No direct script access allowed');

define('CRON', true);

class Cron_model extends App_Model
{
    public $manually = false;

    private $lock_handle;

    public function __construct()
    {
        if (!defined('APP_DISABLE_CRON_LOCK') || defined('APP_DISABLE_CRON_LOCK') && !APP_DISABLE_CRON_LOCK) {
            register_shutdown_function([$this, '__destruct']);
            $f = fopen(get_temp_dir() . 'pcrm-cron-lock', 'w+');

            if (!$f) {
                $this->lock_handle = fopen(TEMP_FOLDER . 'pcrm-cron-lock', 'w+');
                // Again? Disable the lock
                if (!$this->lock_handle && !defined('APP_DISABLE_CRON_LOCK')) {
                    // Defined this constant manually here so the cron is able to run
                    // Used in method can_cron_run
                    define('APP_DISABLE_CRON_LOCK', true);
                }
            } else {
                $this->lock_handle = $f;
            }
        }

        parent::__construct();
        $this->load->model('emails_model');
        $this->load->model('staff_model');
    }

    public function run($manually = false)
    {
        if ($this->can_cron_run()) {
            hooks()->do_action('before_cron_run', $manually);

            update_option('last_cron_run', time());

            if ($manually == true) {
                $this->manually = true;

                if (!extension_loaded('suhosin')) {
                    @ini_set('memory_limit', '-1');
                }

                log_activity('Cron Invoked Manually');
            }

            $this->staff_reminders();
            $this->events();
            $this->tasks_reminders();
            $this->recurring_tasks();
            $this->proposals();
            $this->invoice_overdue();
            $this->estimate_expiration();
            $this->contracts_expiration_check();
            $this->autoclose_tickets();
            $this->recurring_invoices();
            $this->recurring_expenses();

            $this->auto_import_imap_tickets();
            $this->check_leads_email_integration();
            $this->delete_activity_log();

            /**
             * Finally send any emails in the email queue - if enabled and any
             */
            $this->email->send_queue();

            $last_email_queue_retry = get_option('last_email_queue_retry');

            $retryQueue = hooks()->apply_filters('cron_retry_email_queue_seconds', 600);
            // Retry queue failed emails every 10 minutes
            if ($last_email_queue_retry == '' || (time() > ($last_email_queue_retry + $retryQueue))) {
                $this->email->retry_queue();
                update_option('last_email_queue_retry', time());
            }

            $this->_maybe_fix_duplicate_tasks_assignees_and_followers();

            app_maybe_delete_old_temporary_files();

            hooks()->do_action('after_cron_run', $manually);

            // For all cases try to release the lock after everything is finished
            $this->lockHandle();
        }
    }

    private function events()
    {
        // User events
        $this->db->where('isstartnotified', 0);
        $events = $this->db->get(db_prefix() . 'events')->result_array();

        $notified_users            = [];
        $notificationNotifiedUsers = [];
        $all_notified_events       = [];
        foreach ($events as $event) {
            $date_compare = date('Y-m-d H:i:s', strtotime('+' . $event['reminder_before'] . ' ' . strtoupper($event['reminder_before_type'])));

            if ($event['start'] <= $date_compare) {
                array_push($all_notified_events, $event['eventid']);
                array_push($notified_users, $event['userid']);

                $eventNotifications = hooks()->apply_filters('event_notifications', true);

                if ($eventNotifications) {
                    $notified = add_notification([
                        'description'     => 'not_event',
                        'touserid'        => $event['userid'],
                        'fromcompany'     => true,
                        'link'            => 'utilities/calendar?eventid=' . $event['eventid'],
                        'additional_data' => serialize([
                            $event['title'],
                        ]),
                    ]);

                    $staff = $this->staff_model->get($event['userid']);

                    send_mail_template('staff_event_notification', array_to_object($event), $staff);
                    array_push($notificationNotifiedUsers, $event['userid']);
                }
            }
        }

        // Public events
        $notified_users = array_unique($notified_users);

        $this->db->where('public', 1);

        $this->db->where('isstartnotified', 0);
        $events = $this->db->get(db_prefix() . 'events')->result_array();

        $whereStaff = 'active=1 AND is_not_staff=0';
        if (count($notified_users) > 0) {
            $whereStaff .= ' AND staffid NOT IN (' . implode(',', $notified_users) . ')';
        }

        $staff = $this->staff_model->get('', $whereStaff);

        foreach ($staff as $member) {
            foreach ($events as $event) {
                $date_compare = date('Y-m-d H:i:s', strtotime('+' . $event['reminder_before'] . ' ' . strtoupper($event['reminder_before_type'])));
                if ($event['start'] <= $date_compare) {
                    array_push($all_notified_events, $event['eventid']);

                    $eventNotifications = hooks()->apply_filters('event_notifications', true);

                    if ($eventNotifications) {
                        $notified = add_notification([
                                'description'     => 'not_event_public',
                                'touserid'        => $member['staffid'],
                                'fromcompany'     => true,
                                'link'            => 'utilities/calendar?eventid=' . $event['eventid'],
                                'additional_data' => serialize([
                                    $event['title'],
                                ]),
                            ]);
                        send_mail_template('staff_event_notification', array_to_object($event), array_to_object($member));

                        array_push($notificationNotifiedUsers, $member['staffid']);
                    }
                }
            }
        }

        foreach ($all_notified_events as $id) {
            $this->db->where('eventid', $id);
            $this->db->update(db_prefix() . 'events', [
                'isstartnotified' => 1,
            ]);
        }

        pusher_trigger_notification($notificationNotifiedUsers);
    }

    private function autoclose_tickets()
    {
        $auto_close_after = get_option('autoclose_tickets_after');

        if ($auto_close_after == 0) {
            return;
        }

        $this->db->select('ticketid,lastreply,date,userid,contactid,email');
        $this->db->where('status !=', 5); // Closed
        $this->db->where('status !=', 4); // On Hold
        $this->db->where('status !=', 2); // In Progress
        $tickets = $this->db->get(db_prefix() . 'tickets')->result_array();

        $this->load->model('tickets_model');

        foreach ($tickets as $ticket) {
            $close_ticket = false;
            if (!is_null($ticket['lastreply'])) {
                $last_reply = strtotime($ticket['lastreply']);
                if ($last_reply <= strtotime('-' . $auto_close_after . ' hours')) {
                    $close_ticket = true;
                }
            } else {
                $created = strtotime($ticket['date']);
                if ($created <= strtotime('-' . $auto_close_after . ' hours')) {
                    $close_ticket = true;
                }
            }

            if ($close_ticket == true) {
                $this->db->where('ticketid', $ticket['ticketid']);
                $this->db->update(db_prefix() . 'tickets', [
                    'status' => 5,
                ]);
                if ($this->db->affected_rows() > 0) {

                    hooks()->do_action('after_ticket_status_changed', [
                        'id'     => $ticket['ticketid'],
                        'status' => 5,
                    ]);

                    $isContact = false;
                    if ($ticket['userid'] != 0 && $ticket['contactid'] != 0) {
                        $email     = $this->clients_model->get_contact($ticket['contactid'])->email;
                        $isContact = true;
                    } else {
                        $email = $ticket['email'];
                    }
                    $sendEmail = true;
                    if ($isContact && total_rows(db_prefix() . 'contacts', ['ticket_emails' => 1, 'id' => $ticket['contactid']]) == 0) {
                        $sendEmail = false;
                    }
                    if ($sendEmail) {
                        $ticket = $this->tickets_model->get($ticket['ticketid']);
                        send_mail_template('ticket_auto_close_to_customer', $ticket, $email);
                    }
                }
            }
        }
    }

    public function contracts_expiration_check()
    {
        $contracts_auto_operations_hour = get_option('contracts_auto_operations_hour');

        if ($contracts_auto_operations_hour == '') {
            $contracts_auto_operations_hour = 9;
        }
        $contracts_auto_operations_hour = intval($contracts_auto_operations_hour);
        $hour_now                       = date('G');
        if ($hour_now != $contracts_auto_operations_hour && $this->manually === false) {
            return;
        }

        $this->db->select('id,client,dateend,subject,addedfrom,not_visible_to_client,dateadded');
        $this->db->where('isexpirynotified', 0);
        $this->db->where('dateend is NOT NULL');
        $this->db->where('trash', 0);
        $contracts = $this->db->get(db_prefix() . 'contracts')->result_array();
        $now       = new DateTime(date('Y-m-d'));

        $notifiedUsers = [];
        if (count($contracts) > 0) {
            $staff = $this->staff_model->get('', ['active' => 1]);
        }

        foreach ($contracts as $contract) {
            if ($contract['dateend'] > date('Y-m-d')) {
                $dateend = new DateTime($contract['dateend']);
                $diff    = $dateend->diff($now)->format('%a');
                if ($diff <= get_option('contract_expiration_before')) {
                    $this->db->where('id', $contract['id']);
                    $this->db->update(db_prefix() . 'contracts', [
                        'isexpirynotified' => 1,
                    ]);

                    foreach ($staff as $member) {
                        if ($member['staffid'] == $contract['addedfrom'] || is_admin($member['staffid'])) {
                            $notified = add_notification([
                                'description'     => 'not_contract_expiry_reminder',
                                'touserid'        => $member['staffid'],
                                'fromcompany'     => 1,
                                'fromuserid'      => null,
                                'link'            => 'contracts/contract/' . $contract['id'],
                                'additional_data' => serialize([
                                    $contract['subject'],
                                ]),
                            ]);

                            if ($notified) {
                                array_push($notifiedUsers, $member['staffid']);
                            }

                            send_mail_template('contract_expiration_reminder_to_staff', $contract, $member);
                        }
                    }

                    if ($contract['not_visible_to_client'] == 0) {
                        $contacts = $this->clients_model->get_contacts($contract['client'], ['active' => 1, 'contract_emails' => 1]);
                        foreach ($contacts as $contact) {
                            $template = mail_template('contract_expiration_reminder_to_customer', $contract, $contact);

                            $merge_fields = $template->get_merge_fields();

                            $template->send();

                            if (can_send_sms_based_on_creation_date($contract['dateadded'])) {
                                $this->app_sms->trigger(SMS_TRIGGER_CONTRACT_EXP_REMINDER, $contact['phonenumber'], $merge_fields);
                            }
                        }
                    }
                }
            }
        }

        pusher_trigger_notification($notifiedUsers);
    }

    public function recurring_tasks()
    {
        $this->db->select('id,addedfrom,recurring_type,repeat_every,last_recurring_date,startdate,duedate');
        $this->db->where('recurring', 1);
        $this->db->where('(cycles != total_cycles OR cycles=0)');
        $recurring_tasks = $this->db->get(db_prefix() . 'tasks')->result_array();

        foreach ($recurring_tasks as $task) {
            $type                = $task['recurring_type'];
            $repeat_every        = $task['repeat_every'];
            $last_recurring_date = $task['last_recurring_date'];
            $task_date           = $task['startdate'];

            // Current date
            $date = new DateTime(date('Y-m-d'));
            // Check if is first recurring
            if (!$last_recurring_date) {
                $last_recurring_date = date('Y-m-d', strtotime($task_date));
            } else {
                $last_recurring_date = date('Y-m-d', strtotime($last_recurring_date));
            }

            $re_create_at = date('Y-m-d', strtotime('+' . $repeat_every . ' ' . strtoupper($type), strtotime($last_recurring_date)));

            if (date('Y-m-d') >= $re_create_at) {
                $copy_task_data['copy_task_followers']       = 'true';
                $copy_task_data['copy_task_checklist_items'] = 'true';
                $copy_task_data['copy_from']                 = $task['id'];

                $overwrite_params = [
                    'startdate'           => $re_create_at,
                    'status'              => hooks()->apply_filters('recurring_task_status', 1),
                    'recurring_type'      => null,
                    'repeat_every'        => 0,
                    'cycles'              => 0,
                    'recurring'           => 0,
                    'custom_recurring'    => 0,
                    'last_recurring_date' => null,
                    'is_recurring_from'   => $task['id'],
                ];

                if (!empty($task['duedate'])) {
                    $dStart                      = new DateTime($task['startdate']);
                    $dEnd                        = new DateTime($task['duedate']);
                    $dDiff                       = $dStart->diff($dEnd);
                    $overwrite_params['duedate'] = date('Y-m-d', strtotime('+' . $dDiff->days . ' days', strtotime($re_create_at)));
                }

                $newTaskID = $this->tasks_model->copy($copy_task_data, $overwrite_params);

                if ($newTaskID) {
                    $this->db->where('id', $task['id']);
                    $this->db->update(db_prefix() . 'tasks', [
                        'last_recurring_date' => $re_create_at,
                    ]);

                    $this->db->where('id', $task['id']);
                    $this->db->set('total_cycles', 'total_cycles+1', false);
                    $this->db->update(db_prefix() . 'tasks');

                    $this->db->where('taskid', $task['id']);
                    $assigned = $this->db->get(db_prefix() . 'task_assigned')->result_array();
                    foreach ($assigned as $assignee) {
                        $assigneeId = $this->tasks_model->add_task_assignees([
                            'taskid'   => $newTaskID,
                            'assignee' => $assignee['staffid'],
                        ], true);

                        if ($assigneeId) {
                            $this->db->where('id', $assigneeId);
                            $this->db->update(db_prefix() . 'task_assigned', ['assigned_from' => $task['addedfrom']]);
                        }
                    }
                }
            }
        }
    }

    private function recurring_expenses()
    {
        $expenses_hour_auto_operations = get_option('expenses_auto_operations_hour');

        if ($expenses_hour_auto_operations == '') {
            $expenses_hour_auto_operations = 9;
        }

        $expenses_hour_auto_operations = intval($expenses_hour_auto_operations);
        $hour_now                      = date('G');
        if ($hour_now != $expenses_hour_auto_operations && $this->manually === false) {
            return;
        }

        $this->db->where('recurring', 1);
        $this->db->where('(cycles != total_cycles OR cycles=0)');
        $recurring_expenses = $this->db->get(db_prefix() . 'expenses')->result_array();
        // Load the necessary models
        $this->load->model('invoices_model');
        $this->load->model('expenses_model');

        $_renewals_ids_data = [];
        $total_renewed      = 0;

        foreach ($recurring_expenses as $expense) {
            $type                     = $expense['recurring_type'];
            $repeat_every             = $expense['repeat_every'];
            $last_recurring_date      = $expense['last_recurring_date'];
            $create_invoice_billable  = $expense['create_invoice_billable'];
            $send_invoice_to_customer = $expense['send_invoice_to_customer'];
            $expense_date             = $expense['date'];
            // Current date
            $date = new DateTime(date('Y-m-d'));
            // Check if is first recurring
            if (!$last_recurring_date) {
                $last_recurring_date = date('Y-m-d', strtotime($expense_date));
            } else {
                $last_recurring_date = date('Y-m-d', strtotime($last_recurring_date));
            }
            $re_create_at = date('Y-m-d', strtotime('+' . $repeat_every . ' ' . strtoupper($type), strtotime($last_recurring_date)));

            if (date('Y-m-d') >= $re_create_at) {
                // Ok we can repeat the expense now
                $new_expense_data = [];
                $expense_fields   = $this->db->list_fields(db_prefix() . 'expenses');
                foreach ($expense_fields as $field) {
                    if (isset($expense[$field])) {
                        // We dont need the invoiceid field
                        if ($field != 'invoiceid' && $field != 'id' && $field != 'recurring_from') {
                            $new_expense_data[$field] = $expense[$field];
                        }
                    }
                }

                $new_expense_data['dateadded']      = date('Y-m-d H:i:s');
                $new_expense_data['date']           = $re_create_at;
                $new_expense_data['recurring_from'] = $expense['id'];
                $new_expense_data['addedfrom']      = $expense['addedfrom'];

                $new_expense_data['recurring_type']      = null;
                $new_expense_data['repeat_every']        = 0;
                $new_expense_data['recurring']           = 0;
                $new_expense_data['cycles']              = 0;
                $new_expense_data['total_cycles']        = 0;
                $new_expense_data['custom_recurring']    = 0;
                $new_expense_data['last_recurring_date'] = null;

                $this->db->insert(db_prefix() . 'expenses', $new_expense_data);
                $insert_id = $this->db->insert_id();
                if ($insert_id) {
                    // Get the old expense custom field and add to the new
                    $custom_fields = get_custom_fields('expenses');
                    foreach ($custom_fields as $field) {
                        $value = get_custom_field_value($expense['id'], $field['id'], 'expenses', false);
                        if ($value != '') {
                            $this->db->insert(db_prefix() . 'customfieldsvalues', [
                                'relid'   => $insert_id,
                                'fieldid' => $field['id'],
                                'fieldto' => 'expenses',
                                'value'   => $value,
                            ]);
                        }
                    }
                    $total_renewed++;
                    $this->db->where('id', $expense['id']);
                    $this->db->update(db_prefix() . 'expenses', [
                        'last_recurring_date' => $re_create_at,
                        // In case cron job is late use the date actually when the recurring supposed to happen
                    ]);

                    $this->db->where('id', $expense['id']);
                    $this->db->set('total_cycles', 'total_cycles+1', false);
                    $this->db->update(db_prefix() . 'expenses');

                    $sent               = false;
                    $created_invoice_id = '';
                    if ($expense['create_invoice_billable'] == 1 && $expense['billable'] == 1) {
                        $invoiceid = $this->expenses_model->convert_to_invoice($insert_id, false, ['invoice_date' => $re_create_at]);
                        if ($invoiceid) {
                            $created_invoice_id = $invoiceid;
                            if ($expense['send_invoice_to_customer'] == 1) {
                                $sent = $this->invoices_model->send_invoice_to_client($invoiceid, 'invoice_send_to_customer', true);
                            }
                        }
                    }
                    $_renewals_ids_data[] = [
                        'from'                     => $expense['id'],
                        'renewed'                  => $insert_id,
                        'send_invoice_to_customer' => $expense['send_invoice_to_customer'],
                        'create_invoice_billable'  => $expense['create_invoice_billable'],
                        'is_sent'                  => $sent,
                        'addedfrom'                => $expense['addedfrom'],
                        'created_invoice_id'       => $created_invoice_id,
                    ];
                }
            }
        }

        $send_recurring_expenses_email = hooks()->apply_filters('send_recurring_system_expenses_email', 'true');
        if ($total_renewed > 0 && $send_recurring_expenses_email == 'true') {
            $this->load->model('currencies_model');
            $email_send_to_by_staff_and_expense = [];
            $date                               = _dt(date('Y-m-d H:i:s'));
            // Get all active staff members
            $staff = $this->staff_model->get('', ['active' => 1]);
            foreach ($staff as $member) {
                $sent = false;
                load_admin_language($member['staffid']);
                $recurring_expenses_email_data = _l('not_recurring_expense_cron_activity_heading') . ' - ' . $date . '<br /><br />';
                foreach ($_renewals_ids_data as $data) {
                    if ($data['addedfrom'] == $member['staffid'] || is_admin($member['staffid'])) {
                        $unique_send = '[' . $member['staffid'] . '-' . $data['from'] . ']';
                        $sent        = true;
                        // Prevent sending the email twice if the same staff is added is sale agent and is creator for this invoice.
                        if (in_array($unique_send, $email_send_to_by_staff_and_expense)) {
                            $sent = false;
                        }

                        $expense = $this->expenses_model->get($data['from']);

                        $recurring_expenses_email_data .= _l('not_recurring_expenses_action_taken_from') . ': <a href="' . admin_url('expenses/list_expenses/' . $data['from']) . '">' . $expense->category_name . (!empty($expense->expense_name) ? ' (' . $expense->expense_name . ')' : '') . '</a> - ' . _l('expense_amount') . ' ' . app_format_money($expense->amount, get_currency($expense->currency)) . '<br />';

                        $recurring_expenses_email_data .= _l('not_expense_renewed') . ' <a href="' . admin_url('expenses/list_expenses/' . $data['renewed']) . '">' . _l('id') . ' ' . $data['renewed'] . '</a>';

                        if ($data['create_invoice_billable'] == 1) {
                            $recurring_expenses_email_data .= '<br />' . _l('not_invoice_created') . ' ';
                            if (is_numeric($data['created_invoice_id'])) {
                                $recurring_expenses_email_data .= _l('not_invoice_sent_yes');
                                if ($data['send_invoice_to_customer'] == 1) {
                                    if ($data['is_sent']) {
                                        $invoice_sent = 'not_invoice_sent_yes';
                                    } else {
                                        $invoice_sent = 'not_invoice_sent_no';
                                    }
                                    $recurring_expenses_email_data .= '<br />' . _l('not_invoice_sent_to_customer', _l($invoice_sent));
                                }
                            } else {
                                $recurring_expenses_email_data .= _l('not_invoice_sent_no');
                            }
                        }
                        $recurring_expenses_email_data .= '<br /><br />';
                    }
                }
                if ($sent == true) {
                    array_push($email_send_to_by_staff_and_expense, $unique_send);
                    $this->emails_model->send_simple_email($member['email'], _l('not_recurring_expense_cron_activity_heading'), $recurring_expenses_email_data);
                }
                load_admin_language();
            }
        }
    }

    private function recurring_invoices()
    {
        $invoice_hour_auto_operations = get_option('invoice_auto_operations_hour');

        if ($invoice_hour_auto_operations == '') {
            $invoice_hour_auto_operations = 9;
        }

        $invoice_hour_auto_operations = intval($invoice_hour_auto_operations);
        $hour_now                     = date('G');
        if ($hour_now != $invoice_hour_auto_operations && $this->manually === false) {
            return;
        }

        $new_recurring_invoice_action = get_option('new_recurring_invoice_action');

        $invoices_create_invoice_from_recurring_only_on_paid_invoices = get_option('invoices_create_invoice_from_recurring_only_on_paid_invoices');
        $this->load->model('invoices_model');
        $this->db->select('id,recurring,date,last_recurring_date,number,duedate,recurring_type,custom_recurring,addedfrom,sale_agent,clientid');
        $this->db->from(db_prefix() . 'invoices');
        $this->db->where('recurring !=', 0);
        $this->db->where('(cycles != total_cycles OR cycles=0)');

        if ($invoices_create_invoice_from_recurring_only_on_paid_invoices == 1) {
            // Includes all recurring invoices with paid status if this option set to Yes
            $this->db->where('status', 2);
        }
        $this->db->where('status !=', 6);
        $invoices = $this->db->get()->result_array();

        $_renewals_ids_data = [];
        $total_renewed      = 0;
        foreach ($invoices as $invoice) {

            // Current date
            $date = new DateTime(date('Y-m-d'));
            // Check if is first recurring
            if (!$invoice['last_recurring_date']) {
                $last_recurring_date = date('Y-m-d', strtotime($invoice['date']));
            } else {
                $last_recurring_date = date('Y-m-d', strtotime($invoice['last_recurring_date']));
            }
            if ($invoice['custom_recurring'] == 0) {
                $invoice['recurring_type'] = 'MONTH';
            }

            $re_create_at = date('Y-m-d', strtotime('+' . $invoice['recurring'] . ' ' . strtoupper($invoice['recurring_type']), strtotime($last_recurring_date)));

            if (date('Y-m-d') >= $re_create_at) {

                // Recurring invoice date is okey lets convert it to new invoice
                $_invoice                     = $this->invoices_model->get($invoice['id']);
                $new_invoice_data             = [];
                $new_invoice_data['clientid'] = $_invoice->clientid;
                $new_invoice_data['number']   = get_option('next_invoice_number');
                $new_invoice_data['date']     = _d($re_create_at);
                $new_invoice_data['duedate']  = null;

                if ($_invoice->duedate) {
                    // Now we need to get duedate from the old invoice and calculate the time difference and set new duedate
                    // Ex. if the first invoice had duedate 20 days from now we will add the same duedate date but starting from now
                    $dStart                      = new DateTime($invoice['date']);
                    $dEnd                        = new DateTime($invoice['duedate']);
                    $dDiff                       = $dStart->diff($dEnd);
                    $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . $dDiff->days . ' DAY', strtotime($re_create_at))));
                } else {
                    if (get_option('invoice_due_after') != 0) {
                        $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime($re_create_at))));
                    }
                }

                $new_invoice_data['project_id']       = $_invoice->project_id;
                $new_invoice_data['show_quantity_as'] = $_invoice->show_quantity_as;
                $new_invoice_data['currency']         = $_invoice->currency;
                $new_invoice_data['subtotal']         = $_invoice->subtotal;
                $new_invoice_data['total']            = $_invoice->total;
                $new_invoice_data['adjustment']       = $_invoice->adjustment;
                $new_invoice_data['discount_percent'] = $_invoice->discount_percent;
                $new_invoice_data['discount_total']   = $_invoice->discount_total;
                $new_invoice_data['discount_type']    = $_invoice->discount_type;
                $new_invoice_data['terms']            = clear_textarea_breaks($_invoice->terms);
                $new_invoice_data['sale_agent']       = $_invoice->sale_agent;
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
                $new_invoice_data['include_shipping']         = $_invoice->include_shipping;
                $new_invoice_data['show_shipping_on_invoice'] = $_invoice->show_shipping_on_invoice;
                // Determine status based on settings
                if ($new_recurring_invoice_action == 'generate_and_send' || $new_recurring_invoice_action == 'generate_unpaid') {
                    $new_invoice_data['status'] = 1;
                } elseif ($new_recurring_invoice_action == 'generate_draft') {
                    $new_invoice_data['save_as_draft'] = true;
                }
                $new_invoice_data['clientnote']            = clear_textarea_breaks($_invoice->clientnote);
                $new_invoice_data['adminnote']             = '';
                $new_invoice_data['allowed_payment_modes'] = unserialize($_invoice->allowed_payment_modes);
                $new_invoice_data['is_recurring_from']     = $_invoice->id;
                $new_invoice_data['newitems']              = [];
                $key                                       = 1;
                $custom_fields_items                       = get_custom_fields('items');
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
                        'addedfrom'                => $_invoice->addedfrom,
                        'sale_agent'               => $_invoice->sale_agent,
                        'cancel_overdue_reminders' => $_invoice->cancel_overdue_reminders,
                    ]);


                    $tags = get_tags_in($_invoice->id, 'invoice');
                    handle_tags_save($tags, $id, 'invoice');

                    // Get the old expense custom field and add to the new
                    $custom_fields = get_custom_fields('invoice');
                    foreach ($custom_fields as $field) {
                        $value = get_custom_field_value($invoice['id'], $field['id'], 'invoice', false);
                        if ($value != '') {
                            $this->db->insert(db_prefix() . 'customfieldsvalues', [
                                'relid'   => $id,
                                'fieldid' => $field['id'],
                                'fieldto' => 'invoice',
                                'value'   => $value,
                            ]);
                        }
                    }
                    // Increment total renewed invoices
                    $total_renewed++;
                    // Update last recurring date to this invoice
                    $this->db->where('id', $invoice['id']);
                    $this->db->update(db_prefix() . 'invoices', [
                        'last_recurring_date' => $re_create_at,
                    ]);

                    $this->db->where('id', $invoice['id']);
                    $this->db->set('total_cycles', 'total_cycles+1', false);
                    $this->db->update(db_prefix() . 'invoices');

                    if ($new_recurring_invoice_action == 'generate_and_send') {
                        $this->invoices_model->send_invoice_to_client($id, 'invoice_send_to_customer', true);
                    }

                    $_renewals_ids_data[] = [
                        'from'       => $invoice['id'],
                        'clientid'   => $invoice['clientid'],
                        'renewed'    => $id,
                        'addedfrom'  => $invoice['addedfrom'],
                        'sale_agent' => $invoice['sale_agent'],
                    ];
                }
            }
        }

        $send_recurring_invoices_email = hooks()->apply_filters('send_recurring_invoices_system_email', 'true');
        if ($total_renewed > 0 && $send_recurring_invoices_email == 'true') {
            $date                               = _dt(date('Y-m-d H:i:s'));
            $email_send_to_by_staff_and_invoice = [];
            // Get all active staff members
            $staff = $this->staff_model->get('', ['active' => 1]);
            foreach ($staff as $member) {
                $sent = false;
                load_admin_language($member['staffid']);
                $recurring_invoices_email_data = _l('not_recurring_invoices_cron_activity_heading') . ' - ' . $date . '<br /><br />';
                foreach ($_renewals_ids_data as $renewed_invoice_data) {
                    if ($renewed_invoice_data['addedfrom'] == $member['staffid'] || $renewed_invoice_data['sale_agent'] == $member['staffid'] || is_admin($member['staffid'])) {
                        $unique_send = '[' . $member['staffid'] . '-' . $renewed_invoice_data['from'] . ']';
                        $sent        = true;
                        // Prevent sending the email twice if the same staff is added is sale agent and is creator for this invoice.
                        if (in_array($unique_send, $email_send_to_by_staff_and_invoice)) {
                            $sent = false;
                        }
                        $recurring_invoices_email_data .= _l('not_action_taken_from_recurring_invoice') . ' <a href="' . admin_url('invoices/list_invoices/' . $renewed_invoice_data['from']) . '">' . format_invoice_number($renewed_invoice_data['from']) . '</a><br />';
                        $recurring_invoices_email_data .= _l('not_invoice_renewed') . ' <a href="' . admin_url('invoices/list_invoices/' . $renewed_invoice_data['renewed']) . '">' . format_invoice_number($renewed_invoice_data['renewed']) . '</a> - <a href="' . admin_url('clients/client/' . $renewed_invoice_data['clientid']) . '">' . get_company_name($renewed_invoice_data['clientid']) . '</a><br /><br />';
                    }
                }
                if ($sent == true) {
                    array_push($email_send_to_by_staff_and_invoice, $unique_send);
                    $this->emails_model->send_simple_email($member['email'], _l('not_recurring_invoices_cron_activity_heading'), $recurring_invoices_email_data);
                }
            }
            load_admin_language();
        }
    }

    private function tasks_reminders()
    {
        $reminder_before = get_option('tasks_reminder_notification_before');
        $this->db->where('status !=', 5);
        $this->db->where('duedate IS NOT NULL');
        $this->db->where('deadline_notified', 0);

        $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
        $now   = new DateTime(date('Y-m-d'));

        $notifiedUsers = [];

        foreach ($tasks as $task) {
            if (date('Y-m-d', strtotime($task['duedate'])) >= date('Y-m-d')) {
                $duedate = new DateTime($task['duedate']);
                $diff    = $duedate->diff($now)->format('%a');
                // Check if difference between start date and duedate is the same like the reminder before
                // In this case reminder wont be sent becuase the task it too short
                $start_date              = strtotime($task['startdate']);
                $duedate                 = strtotime($task['duedate']);
                $start_and_due_date_diff = $duedate - $start_date;
                $start_and_due_date_diff = floor($start_and_due_date_diff / (60 * 60 * 24));

                if ($diff <= $reminder_before && $start_and_due_date_diff > $reminder_before) {
                    $assignees = $this->tasks_model->get_task_assignees($task['id']);

                    foreach ($assignees as $member) {
                        $this->db->select('email');
                        $this->db->where('staffid', $member['assigneeid']);
                        $row = $this->db->get(db_prefix() . 'staff')->row();
                        if ($row) {
                            $notified = add_notification([
                                'description'     => 'not_task_deadline_reminder',
                                'touserid'        => $member['assigneeid'],
                                'fromcompany'     => 1,
                                'fromuserid'      => null,
                                'link'            => '#taskid=' . $task['id'],
                                'additional_data' => serialize([
                                    $task['name'],
                                ]),
                            ]);

                            if ($notified) {
                                array_push($notifiedUsers, $member['assigneeid']);
                            }

                            send_mail_template('task_deadline_reminder_to_staff', $row->email, $member['assigneeid'], $task['id']);

                            $this->db->where('id', $task['id']);
                            $this->db->update(db_prefix() . 'tasks', [
                                'deadline_notified' => 1,
                            ]);
                        }
                    }
                }
            }
        }

        pusher_trigger_notification($notifiedUsers);
    }

    private function staff_reminders()
    {
        $this->db->select('' . db_prefix() . 'reminders.*, email, phonenumber');
        $this->db->join(db_prefix() . 'staff', '' . db_prefix() . 'staff.staffid=' . db_prefix() . 'reminders.staff');
        $this->db->where('isnotified', 0);
        $reminders     = $this->db->get(db_prefix() . 'reminders')->result_array();
        $notifiedUsers = [];

        foreach ($reminders as $reminder) {
            if (date('Y-m-d H:i:s') >= $reminder['date']) {
                $this->db->where('id', $reminder['id']);
                $this->db->update(db_prefix() . 'reminders', [
                    'isnotified' => 1,
                ]);

                $rel_data   = get_relation_data($reminder['rel_type'], $reminder['rel_id']);
                $rel_values = get_relation_values($rel_data, $reminder['rel_type']);

                $notificationLink = str_replace(admin_url(), '', $rel_values['link']);
                $notificationLink = ltrim($notificationLink, '/');

                $notified = add_notification([
                    'fromcompany'     => true,
                    'touserid'        => $reminder['staff'],
                    'description'     => 'not_new_reminder_for',
                    'link'            => $notificationLink,
                    'additional_data' => serialize([
                        $rel_values['name'] . ' - ' . strip_tags(mb_substr($reminder['description'], 0, 50)) . '...',
                    ]),
                ]);

                if ($notified) {
                    array_push($notifiedUsers, $reminder['staff']);
                }

                $template = mail_template('staff_reminder', $reminder['email'], $reminder['staff'], $reminder);

                if ($reminder['notify_by_email'] == 1) {
                    $template->send();
                }

                $this->app_sms->trigger(SMS_TRIGGER_STAFF_REMINDER, $reminder['phonenumber'], $template->get_merge_fields());
            }
        }

        pusher_trigger_notification($notifiedUsers);
    }

    private function invoice_overdue()
    {
        $invoice_auto_operations_hour = get_option('invoice_auto_operations_hour');
        if ($invoice_auto_operations_hour == '') {
            $invoice_auto_operations_hour = 9;
        }

        $invoice_auto_operations_hour = intval($invoice_auto_operations_hour);
        $hour_now                     = date('G');
        if ($hour_now != $invoice_auto_operations_hour && $this->manually === false) {
            return;
        }

        $this->load->model('invoices_model');
        $this->db->select('id,date,status,last_overdue_reminder,duedate,cancel_overdue_reminders');
        $this->db->from(db_prefix() . 'invoices');
        $this->db->where('(duedate != "" AND duedate IS NOT NULL)'); // We dont need invoices with no duedate
        $this->db->where('status !=', 2); // We dont need paid status
        $this->db->where('status !=', 5); // We dont need cancelled status
        $this->db->where('status !=', 6); // We dont need draft status
        $invoices = $this->db->get()->result_array();

        $now = time();
        foreach ($invoices as $invoice) {
            $statusid = update_invoice_status($invoice['id']);

            if ($invoice['cancel_overdue_reminders'] == 0 && is_invoices_overdue_reminders_enabled()) {
                if ($invoice['status'] == Invoices_model::STATUS_OVERDUE
                    || $statusid == Invoices_model::STATUS_OVERDUE
                    || $invoice['status'] == Invoices_model::STATUS_PARTIALLY) {
                    if ($invoice['status'] == Invoices_model::STATUS_PARTIALLY) {
                        // Invoice is with status partialy paid and its not due
                        if (date('Y-m-d') <= date('Y-m-d', strtotime($invoice['duedate']))) {
                            continue;
                        }
                    }
                    // Check if already sent invoice reminder
                    if ($invoice['last_overdue_reminder']) {
                        // We already have sent reminder, check for resending
                        $resend_days = get_option('automatically_resend_invoice_overdue_reminder_after');
                        // If resend_days from options is 0 means that the admin dont want to resend the mails.
                        if ($resend_days != 0) {
                            $datediff  = $now - strtotime($invoice['last_overdue_reminder']);
                            $days_diff = floor($datediff / (60 * 60 * 24));
                            if ($days_diff >= $resend_days) {
                                $this->invoices_model->send_invoice_overdue_notice($invoice['id']);
                            }
                        }
                    } else {
                        $datediff  = $now - strtotime($invoice['duedate']);
                        $days_diff = floor($datediff / (60 * 60 * 24));
                        if ($days_diff >= get_option('automatically_send_invoice_overdue_reminder_after')) {
                            $this->invoices_model->send_invoice_overdue_notice($invoice['id']);
                        }
                    }
                }
            }
        }
    }

    public function proposals()
    {
        $proposals_auto_operations_hour = get_option('proposals_auto_operations_hour');

        if ($proposals_auto_operations_hour == '') {
            $proposals_auto_operations_hour = 9;
        }

        $proposals_auto_operations_hour = intval($proposals_auto_operations_hour);
        $hour_now                       = date('G');
        if ($hour_now != $proposals_auto_operations_hour && $this->manually === false) {
            return;
        }

        $this->load->model('proposals_model');

        $this->db->select('open_till,date,id');
        // Only 1 = open, 4 = sent
        $this->db->where('status IN (1,4)');
        $this->db->where('is_expiry_notified', 0);
        $proposals = $this->db->get(db_prefix() . 'proposals')->result_array();
        $now       = new DateTime(date('Y-m-d'));

        foreach ($proposals as $proposal) {
            if ($proposal['open_till'] != null
                && date('Y-m-d') < $proposal['open_till']
                && is_proposals_expiry_reminders_enabled()) {
                $reminder_before        = get_option('send_proposal_expiry_reminder_before');
                $open_till              = new DateTime($proposal['open_till']);
                $diff                   = $open_till->diff($now)->format('%a');
                $date                   = strtotime($proposal['date']);
                $open_till              = strtotime($proposal['open_till']);
                $date_and_due_date_diff = $open_till - $date;
                $date_and_due_date_diff = floor($date_and_due_date_diff / (60 * 60 * 24));

                if ($diff <= $reminder_before && $date_and_due_date_diff > $reminder_before) {
                    $this->proposals_model->send_expiry_reminder($proposal['id']);
                }
            }
        }
    }

    private function estimate_expiration()
    {
        $estimates_auto_operations_hour = get_option('estimates_auto_operations_hour');

        if ($estimates_auto_operations_hour == '') {
            $estimates_auto_operations_hour = 9;
        }
        $estimates_auto_operations_hour = intval($estimates_auto_operations_hour);
        $hour_now                       = date('G');
        if ($hour_now != $estimates_auto_operations_hour && $this->manually === false) {
            return;
        }

        $this->db->select('id,expirydate,status,is_expiry_notified,date');
        $this->db->from(db_prefix() . 'estimates');
        // Only get sent estimates
        $this->db->where('status', 2);
        $estimates = $this->db->get()->result_array();
        $this->load->model('estimates_model');
        $now = new DateTime(date('Y-m-d'));
        foreach ($estimates as $estimate) {
            if ($estimate['expirydate'] != null) {
                if (date('Y-m-d') > $estimate['expirydate']) {
                    $this->db->where('id', $estimate['id']);
                    $this->db->update(db_prefix() . 'estimates', [
                        'status' => 5,
                    ]);
                    if ($this->db->affected_rows() > 0) {
                        $additional_activity = serialize([
                            '<original_status>' . $estimate['status'] . '</original_status>',
                            '<new_status>5</new_status>',
                        ]);
                        $this->estimates_model->log_estimate_activity($estimate['id'], 'not_estimate_status_updated', false, $additional_activity);
                    }
                } else {
                    if ($estimate['is_expiry_notified'] == 0 && is_estimates_expiry_reminders_enabled()) {
                        $reminder_before        = get_option('send_estimate_expiry_reminder_before');
                        $expirydate             = new DateTime($estimate['expirydate']);
                        $diff                   = $expirydate->diff($now)->format('%a');
                        $date                   = strtotime($estimate['date']);
                        $expirydate             = strtotime($estimate['expirydate']);
                        $date_and_due_date_diff = $expirydate - $date;
                        $date_and_due_date_diff = floor($date_and_due_date_diff / (60 * 60 * 24));
                        if ($diff <= $reminder_before && $date_and_due_date_diff > $reminder_before) {
                            $this->estimates_model->send_expiry_reminder($estimate['id']);
                        }
                    }
                }
            }
        }
    }

    public function check_leads_email_integration()
    {
        $this->load->model('leads_model');
        $mail = $this->leads_model->get_email_integration();

        if ($mail->active == 0) {
            return false;
        }

        require_once(APPPATH . 'third_party/php-imap/Imap.php');

        if (empty($mail->last_run) || (time() > $mail->last_run + ($mail->check_every * 60))) {
            $this->load->model('spam_filters_model');

            $this->db->where('id', 1);
            $this->db->update(db_prefix() . 'leads_email_integration', [
                'last_run' => time(),
            ]);
            $ps = $this->encryption->decrypt($mail->password);
            if (!$ps) {
                if (ENVIRONMENT !== 'production') {
                    log_activity('Failed to decrypt email integration password', null);
                }

                return false;
            }
            $mailbox    = $mail->imap_server;
            $username   = $mail->email;
            $password   = $ps;
            $encryption = $mail->encryption;
            // open connection
            $imap = new Imap($mailbox, $username, $password, $encryption);
            if ($imap->isConnected() === false) {
                return false;
            }
            if ($mail->folder == '') {
                $mail->folder = 'INBOX';
            }
            $imap->selectFolder($mail->folder);
            if ($mail->only_loop_on_unseen_emails == 1) {
                $emails = $imap->getUnreadMessages();
            } else {
                $emails = $imap->getMessages();
            }

            include_once(APPPATH . 'third_party/simple_html_dom.php');

            foreach ($emails as $email) {
                $html                    = str_get_html($email['body']);
                $lead_form_fields        = [];
                $lead_form_custom_fields = [];
                if ($html) {
                    foreach ($html->find('[id^="field_"],[id^="custom_field_"]') as $data) {
                        if (isset($data->plaintext)) {
                            $value = trim($data->plaintext);
                            $value = strip_tags($value);
                            if ($value && isset($data->attr['id']) && !empty($data->attr['id'])) {
                                $lead_form_fields[$data->attr['id']] = $this->security->xss_clean($value);
                            }
                        }
                    }
                }

                foreach ($lead_form_fields as $key => $val) {
                    $field = (strpos($key, 'custom_field_') !== false ? strafter($key, 'custom_field_') : strafter($key, 'field_'));

                    if (strpos($key, 'custom_field_') !== false) {
                        $lead_form_custom_fields[$field] = $val;
                    } elseif ($this->db->field_exists($field, db_prefix() . 'leads')) {
                        $lead_form_fields[$field] = $val;
                    }

                    unset($lead_form_fields[$key]);
                }

                $from    = $email['from'];
                $replyTo = $imap->getReplyToAddresses($email['uid']);
                if (count($replyTo) === 1) {
                    $from = $replyTo[0];
                }
                $fromname = preg_replace('/(.*)<(.*)>/', '\\1', $from);
                $fromname = trim(str_replace('"', '', $fromname));

                $fromemail = isset($lead_form_fields['email']) ? $lead_form_fields['email'] : trim(preg_replace('/(.*)<(.*)>/', '\\2', $from));

                $email['subject'] = trim($email['subject']);

                $mailstatus = $this->spam_filters_model->check($fromemail, $email['subject'], $email['body'], 'leads');

                if ($mailstatus) {
                    $imap->setUnseenMessage($email['uid']);
                    log_activity('Lead Email Integration Blocked Email by Spam Filters [' . $mailstatus . ']');

                    continue;
                }

                $body = hooks()->apply_filters(
                    'leads_email_integration_email_body_for_database',
                    $this->prepare_imap_email_body_html($email['body'])
                );

                // Okey everything good now let make some statements
                // Check if this email exists in customers table first
                $this->db->select('id,userid');
                $this->db->where('email', $fromemail);
                $contact = $this->db->get(db_prefix() . 'contacts')->row();
                if ($contact) {

                    // Set message to seen to in the next time we dont need to loop over this message
                    $imap->setUnseenMessage($email['uid']);

                    if ($mail->create_task_if_customer == '1') {
                        load_admin_language($mail->responsible);

                        $body = '<b>' . _l('leads_email_integration') . ' (' . _l('existing_customer') . ')</b> - <a href="' . admin_url('clients/client/' . $contact->userid . '?contactid=' . $contact->id) . '" target="_blank"><b>' . get_company_name($contact->userid) . '</b></a><br /><br />' . $body;

                        load_admin_language();

                        $task_data = [
                                        'name'        => $fromname . ' - ' . $fromemail,
                                        'priority'    => get_option('default_task_priority'),
                                        'dateadded'   => date('Y-m-d H:i:s'),
                                        'startdate'   => date('Y-m-d'),
                                        'addedfrom'   => $mail->responsible,
                                        'status'      => 1,
                                        'description' => $body,
                                        ];

                        $task_data = hooks()->apply_filters('before_add_task', $task_data);
                        $this->db->insert(db_prefix() . 'tasks', $task_data);

                        $task_id = $this->db->insert_id();
                        if ($task_id) {
                            $assignee_data = [
                                            'taskid'   => $task_id,
                                            'assignee' => $mail->responsible,
                                            ];

                            $this->tasks_model->add_task_assignees($assignee_data, true);
                            $this->_check_lead_email_integration_attachments($email, false, $imap, $task_id);
                            hooks()->do_action('after_add_task', $task_id);
                        }
                    }

                    // Exists no need to do anything
                    continue;
                }
                // Not exists its okey.
                // Now we need to check the leads table
                $this->db->where('email', $fromemail);
                $lead = $this->db->get(db_prefix() . 'leads')->row();

                $lead = hooks()->apply_filters('leads_email_integration_lead_check', $lead, $email);

                if ($lead) {
                    // Check if the lead uid is the same with the email uid
                    if ($lead->email_integration_uid == $email['uid']) {
                        // Set message to seen to in the next time we dont need to loop over this message
                        $imap->setUnseenMessage($email['uid']);

                        continue;
                    }
                    // Check if this uid exists in the emails data log table
                    $this->db->where('emailid', $email['uid']);
                    $exists_in_emails = $this->db->count_all_results(db_prefix() . 'lead_integration_emails');
                    if ($exists_in_emails > 0) {
                        // Set message to seen to in the next time we dont need to loop over this message
                        $imap->setUnseenMessage($email['uid']);

                        continue;
                    }
                    // We dont need the junk leads
                    if ($lead->junk == 1) {
                        // Set message to seen to in the next time we dont need to loop over this message
                        $imap->setUnseenMessage($email['uid']);

                        continue;
                    }
                    // More the one time email from this lead, insert into the lead emails log table
                    $this->db->insert(db_prefix() . 'lead_integration_emails', [
                            'leadid'    => $lead->id,
                            'subject'   => $email['subject'],
                            'body'      => $body,
                            'dateadded' => date('Y-m-d H:i:s'),
                            'emailid'   => $email['uid'],
                        ]);
                    $inserted_email_id = $this->db->insert_id();
                    // Set message to seen to in the next time we dont need to loop over this message
                    $imap->setUnseenMessage($email['uid']);
                    $this->_notification_lead_email_integration('not_received_one_or_more_messages_lead', $mail, $lead->id);
                    $this->_check_lead_email_integration_attachments($email, $lead->id, $imap);
                    hooks()->do_action('existing_lead_email_inserted_from_email_integration', [
                            'email'    => $email,
                            'lead'     => $lead,
                            'email_id' => $inserted_email_id,
                        ]);
                    // Exists not need to do anything except to add the email
                    continue;
                }

                // Lets insert into the leads table
                $lead_data = [
                        'name'                               => $fromname,
                        'assigned'                           => $mail->responsible,
                        'dateadded'                          => date('Y-m-d H:i:s'),
                        'status'                             => $mail->lead_status,
                        'source'                             => $mail->lead_source,
                        'addedfrom'                          => 0,
                        'email'                              => $fromemail,
                        'is_imported_from_email_integration' => 1,
                        'email_integration_uid'              => $email['uid'],
                        'lastcontact'                        => null,
                        'is_public'                          => $mail->mark_public,
                    ];

                $lead_data = hooks()->apply_filters('before_insert_lead_from_email_integration', $lead_data);

                $this->db->insert(db_prefix() . 'leads', $lead_data);
                $insert_id = $this->db->insert_id();
                if ($insert_id) {
                    foreach ($lead_form_fields as $field => $value) {
                        if ($field == 'country') {
                            if ($value == '') {
                                $value = 0;
                            } else {
                                $this->db->where('iso2', $value);
                                $this->db->or_where('short_name', $value);
                                $this->db->or_where('long_name', $value);
                                $country = $this->db->get(db_prefix() . 'countries')->row();
                                if ($country) {
                                    $value = $country->country_id;
                                } else {
                                    $value = 0;
                                }
                            }
                        }

                        if ($field == 'address' || $field == 'description') {
                            $value = nl2br($value);
                        }

                        $this->db->where('id', $insert_id);
                        $this->db->update(db_prefix() . 'leads', [
                                $field => $value,
                            ]);
                    }

                    foreach ($lead_form_custom_fields as $cf_id => $value) {
                        $this->db->insert(db_prefix() . 'customfieldsvalues', [
                                'relid'   => $insert_id,
                                'fieldto' => 'leads',
                                'fieldid' => $cf_id,
                                'value'   => $value,
                            ]);
                    }

                    $this->db->insert(db_prefix() . 'lead_integration_emails', [
                            'leadid'    => $insert_id,
                            'subject'   => $email['subject'],
                            'body'      => $body,
                            'dateadded' => date('Y-m-d H:i:s'),
                            'emailid'   => $email['uid'],
                        ]);

                    if ($mail->delete_after_import == 1) {
                        $imap->deleteMessage($email['uid']);
                    } else {
                        $imap->setUnseenMessage($email['uid']);
                    }

                    // Set message to seen to in the next time we dont need to loop over this message
                    $this->_notification_lead_email_integration('not_received_lead_imported_email_integration', $mail, $insert_id);
                    $this->leads_model->log_lead_activity($insert_id, 'not_received_lead_imported_email_integration', true);
                    $this->_check_lead_email_integration_attachments($email, $insert_id, $imap);
                    $this->leads_model->lead_assigned_member_notification($insert_id, $mail->responsible, true);

                    hooks()->do_action('lead_created', $insert_id);

                    hooks()->do_action('lead_created_from_email_integration', $insert_id);
                }
            }
        }
    }

    public function auto_import_imap_tickets()
    {
        $this->db->select('host,encryption,password,email,delete_after_import,imap_username')->from(db_prefix() . 'departments')->where('host !=', '')->where('password !=', '')->where('email !=', '');
        $dep_emails = $this->db->get()->result_array();
        foreach ($dep_emails as $e) {
            $password = $this->encryption->decrypt($e['password']);
            if (!$password) {
                log_activity('Failed to decrypt department password', null);

                continue;
            }
            require_once(APPPATH . 'third_party/php-imap/Imap.php');
            $mailbox  = $e['host'];
            $username = $e['email'];
            if (!empty($e['imap_username'])) {
                $username = $e['imap_username'];
            }
            $password   = $password;
            $encryption = $e['encryption'];
            // open connection
            $imap = new Imap($mailbox, $username, $password, $encryption);
            if ($imap->isConnected() === false) {
                log_activity('Failed to connect to IMAP auto importing tickets from departments.', null);

                continue;
            }
            $imap->selectFolder('INBOX');
            $emails = $imap->getUnreadMessages();
            $this->load->model('tickets_model');

            foreach ($emails as $email) {
                // Check if empty body
                if (isset($email['body']) && $email['body'] == '' || !isset($email['body'])) {
                    $email['body'] = 'No message found';
                }

                $plainTextBody = $imap->getPlainTextBody($email['uid']);
                $plainTextBody = trim($plainTextBody);

                if (!empty($plainTextBody)) {
                    $email['body'] = $plainTextBody;
                }

                $email['body'] = handle_google_drive_links_in_text($email['body']);

                if (class_exists('EmailReplyParser\EmailReplyParser')
                    && get_option('ticket_import_reply_only') === '1'
                    && (mb_substr_count($email['subject'], 'FWD:') == 0 && mb_substr_count($email['subject'], 'FW:') == 0)) {
                    $parsedBody = \EmailReplyParser\EmailReplyParser::parseReply($email['body']);
                    $parsedBody = trim($parsedBody);
                    // For some emails this is causing an issue and not returning the email, instead is returning empty string
                    // In this case, only use parsed email reply if not empty
                    if (!empty($parsedBody)) {
                        $email['body'] = $parsedBody;
                    }
                }

                $email['body']       = $this->prepare_imap_email_body_html($email['body']);
                $data['attachments'] = [];

                if (isset($email['attachments'])) {
                    foreach ($email['attachments'] as $key => $at) {
                        $_at_name = $email['attachments'][$key]['name'];
                        // Rename the name to filename the model expects filename not name
                        unset($email['attachments'][$key]['name']);
                        $email['attachments'][$key]['filename'] = $_at_name;
                        $_attachment                            = $imap->getAttachment($email['uid'], $key);
                        $email['attachments'][$key]['data']     = $_attachment['content'];
                    }
                    // Add the attchments to data
                    $data['attachments'] = $email['attachments'];
                } else {
                    // No attachments
                    $data['attachments'] = [];
                }

                $data['subject'] = $email['subject'];
                $data['body']    = $email['body'];

                $data['to'] = [];

                // To is the department name
                $data['to'][] = $e['email'];

                // Check for CC
                if (isset($email['cc'])) {
                    foreach ($email['cc'] as $cc) {
                        $data['to'][] = trim(preg_replace('/(.*)<(.*)>/', '\\2', $cc));
                    }
                }

                $data['to'] = implode(',', $data['to']);

                if (hooks()->apply_filters('imap_fetch_from_email_by_reply_to_header', 'true') == 'true') {
                    $replyTo = $imap->getReplyToAddresses($email['uid']);

                    if (count($replyTo) === 1) {
                        $email['from'] = $replyTo[0];
                    }
                }

                $data['email']    = preg_replace('/(.*)<(.*)>/', '\\2', $email['from']);
                $data['fromname'] = preg_replace('/(.*)<(.*)>/', '\\1', $email['from']);
                $data['fromname'] = trim(str_replace('"', '', $data['fromname']));

                $data = hooks()->apply_filters('imap_auto_import_ticket_data', $data, $email);

                $status = $this->tickets_model->insert_piped_ticket($data);

                if ($status == 'Ticket Imported Successfully' || $status == 'Ticket Reply Imported Successfully') {
                    if ($e['delete_after_import'] == 0) {
                        $imap->setUnseenMessage($email['uid']);
                    } else {
                        $imap->deleteMessage($email['uid']);
                    }
                } else {
                    // Set unseen message in all cases to prevent looping throught the message again
                    $imap->setUnseenMessage($email['uid']);
                }
            }
        }
    }

    public function delete_activity_log()
    {
        $older_then_months = get_option('delete_activity_log_older_then');

        if ($older_then_months == 0 || empty($older_then_months)) {
            return;
        }

        $this->db->query('DELETE FROM ' . db_prefix() . 'activity_log WHERE date < DATE_SUB(NOW(), INTERVAL ' . $older_then_months . ' MONTH);');
        $this->db->query('DELETE FROM ' . db_prefix() . 'tickets_pipe_log WHERE date < DATE_SUB(NOW(), INTERVAL ' . $older_then_months . ' MONTH);');
    }

    private function _maybe_fix_duplicate_tasks_assignees_and_followers()
    {
        $query = $this->db->query('SELECT `staffid`, `taskid`, COUNT(*) AS c FROM ' . db_prefix() . 'task_assigned GROUP BY `staffid`, `taskid` HAVING c > 1')->result_array();
        foreach ($query as $res) {
            $this->db->where('staffid', $res['staffid']);
            $this->db->where('taskid', $res['taskid']);
            $this->db->limit($res['c'] - 1);
            $this->db->delete(db_prefix() . 'task_assigned');
        }
        $query = $this->db->query('SELECT `staffid`, `taskid`, COUNT(*) AS c FROM ' . db_prefix() . 'task_followers GROUP BY `staffid`, `taskid` HAVING c > 1')->result_array();
        foreach ($query as $res) {
            $this->db->where('staffid', $res['staffid']);
            $this->db->where('taskid', $res['taskid']);
            $this->db->limit($res['c'] - 1);
            $this->db->delete(db_prefix() . 'task_followers');
        }
    }

    private function _notification_lead_email_integration($description, $mail, $leadid)
    {
        if (!empty($mail->notify_type)) {
            if ($mail->notify_type == 'assigned') {
                $ids   = [$mail->responsible];
                $field = 'staffid';
            } else {
                $ids = unserialize($mail->notify_ids);
                if (!is_array($ids) || count($ids) == 0) {
                    return;
                }
                if ($mail->notify_type == 'specific_staff') {
                    $field = 'staffid';
                } elseif ($mail->notify_type == 'roles') {
                    $field = 'role';
                } else {
                    return;
                }
            }

            $this->db->where('active', 1);
            $this->db->where_in($field, $ids);
            $staff = $this->db->get(db_prefix() . 'staff')->result_array();

            $notifiedUsers = [];

            foreach ($staff as $member) {
                $notified = add_notification([
                    'description' => $description,
                    'touserid'    => $member['staffid'],
                    'fromcompany' => 1,
                    'fromuserid'  => null,
                    'link'        => '#leadid=' . $leadid,
                ]);
                if ($notified) {
                    array_push($notifiedUsers, $member['staffid']);
                }
            }
            pusher_trigger_notification($notifiedUsers);
        }
    }

    private function _check_lead_email_integration_attachments($email, $leadid, &$imap, $task_id = false)
    {
        // Check for any attachments
        if (isset($email['attachments'])) {
            foreach ($email['attachments'] as $key => $attachment) {
                $email_attachment = $imap->getAttachment($email['uid'], $key);
                if ($task_id != false) {
                    $path = get_upload_path_by_type('task') . $task_id . '/';
                } else {
                    $path = get_upload_path_by_type('lead') . $leadid . '/';
                }
                $file_name = unique_filename($path, $attachment['name']);
                if (!file_exists($path)) {
                    mkdir($path, 0755);
                    $fp = fopen($path . 'index.html', 'w');
                    if ($fp) {
                        fclose($fp);
                    }
                }
                $path = $path . $file_name;
                $fp   = fopen($path, 'w+');
                if (fwrite($fp, $email_attachment['content'])) {
                    $db_attachment   = [];
                    $db_attachment[] = [
                        'file_name' => $file_name,
                        'filetype'  => get_mime_by_extension($attachment['name']),
                        'staffid'   => 0,
                    ];

                    $attachment_id = $this->misc_model->add_attachment_to_database(($task_id ? $task_id : $leadid), ($task_id ? 'task' : 'lead'), $db_attachment);

                    if ($attachment_id && $task_id === false) {
                        $this->leads_model->log_lead_activity($leadid, 'not_lead_imported_attachment', true);
                    }
                }
                fclose($fp);
            }
        }
    }

    public function __destruct()
    {
        $this->lockHandle();
    }

    private function lockHandle()
    {
        if ($this->lock_handle) {
            flock($this->lock_handle, LOCK_UN);
            fclose($this->lock_handle);
            $this->lock_handle = null;
        }
    }

    private function can_cron_run()
    {
        if ($this->app->is_db_upgrade_required()) {
            return false;
        }

        return ($this->lock_handle && flock($this->lock_handle, LOCK_EX | LOCK_NB))
        || (defined('APP_DISABLE_CRON_LOCK') && APP_DISABLE_CRON_LOCK);
    }

    private function prepare_imap_email_body_html($body)
    {
        // Trim message
        $body = trim($body);
        $body = str_replace('&nbsp;', ' ', $body);
        // Remove html tags - strips inline styles also
        $body = trim(strip_html_tags($body, '<br/>, <br>, <a>'));
        // Once again do security
        $body = $this->security->xss_clean($body);
        // Remove duplicate new lines
        $body = preg_replace("/[\r\n]+/", "\n", $body);
        // new lines with <br />
        $body = preg_replace('/\n(\s*\n)+/', '<br />', $body);
        $body = preg_replace('/\n/', '<br>', $body);

        return $body;
    }
}
