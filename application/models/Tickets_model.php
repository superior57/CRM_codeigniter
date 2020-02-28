<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Tickets_model extends App_Model
{
    private $piping = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function insert_piped_ticket($data)
    {
        $data = hooks()->apply_filters('piped_ticket_data', $data);

        $this->piping = true;
        $attachments  = $data['attachments'];
        $subject      = $data['subject'];
        // Prevent insert ticket to database if mail delivery error happen
        // This will stop createing a thousand tickets
        $system_blocked_subjects = [
            'Mail delivery failed',
            'failure notice',
            'Returned mail: see transcript for details',
            'Undelivered Mail Returned to Sender',
            ];

        $subject_blocked = false;

        foreach ($system_blocked_subjects as $sb) {
            if (strpos('x' . $subject, $sb) !== false) {
                $subject_blocked = true;

                break;
            }
        }

        if ($subject_blocked == true) {
            return;
        }

        $message = $data['body'];
        $name    = $data['fromname'];

        $email   = $data['email'];
        $to      = $data['to'];
        $subject = $subject;
        $message = $message;

        $this->load->model('spam_filters_model');
        $mailstatus = $this->spam_filters_model->check($email, $subject, $message, 'tickets');

        // No spam found
        if (!$mailstatus) {
            $pos = strpos($subject, '[Ticket ID: ');
            if ($pos === false) {
            } else {
                $tid = substr($subject, $pos + 12);
                $tid = substr($tid, 0, strpos($tid, ']'));
                $this->db->where('ticketid', $tid);
                $data = $this->db->get(db_prefix() . 'tickets')->row();
                $tid  = $data->ticketid;
            }
            $to            = trim($to);
            $toemails      = explode(',', $to);
            $department_id = false;
            $userid        = false;
            foreach ($toemails as $toemail) {
                if (!$department_id) {
                    $this->db->where('email', trim($toemail));
                    $data = $this->db->get(db_prefix() . 'departments')->row();
                    if ($data) {
                        $department_id = $data->departmentid;
                        $to            = $data->email;
                    }
                }
            }
            if (!$department_id) {
                $mailstatus = 'Department Not Found';
            } else {
                if ($to == $email) {
                    $mailstatus = 'Blocked Potential Email Loop';
                } else {
                    $message = trim($message);
                    $this->db->where('active', 1);
                    $this->db->where('email', $email);
                    $result = $this->db->get(db_prefix() . 'staff')->row();
                    if ($result) {
                        if ($tid) {
                            $data            = [];
                            $data['message'] = $message;
                            $data['status']  = get_option('default_ticket_reply_status');

                            if (!$data['status']) {
                                $data['status'] = 3; // Answered
                            }

                            if ($userid == false) {
                                $data['name']  = $name;
                                $data['email'] = $email;
                            }

                            $reply_id = $this->add_reply($data, $tid, $result->staffid, $attachments);
                            if ($reply_id) {
                                $mailstatus = 'Ticket Reply Imported Successfully';
                            }
                        } else {
                            $mailstatus = 'Ticket ID Not Found';
                        }
                    } else {
                        $this->db->where('email', $email);
                        $result = $this->db->get(db_prefix() . 'contacts')->row();
                        if ($result) {
                            $userid    = $result->userid;
                            $contactid = $result->id;
                        }
                        if ($userid == false && get_option('email_piping_only_registered') == '1') {
                            $mailstatus = 'Unregistered Email Address';
                        } else {
                            $filterdate = date('Y-m-d H:i:s', strtotime('-15 minutes'));
                            $query      = 'SELECT count(*) as total FROM ' . db_prefix() . 'tickets WHERE date > "' . $filterdate . '" AND (email="' . $this->db->escape($email) . '"';
                            if ($userid) {
                                $query .= ' OR userid=' . (int) $userid;
                            }
                            $query .= ')';
                            $result = $this->db->query($query)->row();
                            if (10 < $result->total) {
                                $mailstatus = 'Exceeded Limit of 10 Tickets within 15 Minutes';
                            } else {
                                if (isset($tid)) {
                                    $data            = [];
                                    $data['message'] = $message;
                                    $data['status']  = 1;
                                    if ($userid == false) {
                                        $data['name']  = $name;
                                        $data['email'] = $email;
                                    } else {
                                        $data['userid']    = $userid;
                                        $data['contactid'] = $contactid;

                                        $this->db->where('userid', $userid);
                                        $this->db->where('ticketid', $tid);
                                        $t = $this->db->get(db_prefix() . 'tickets')->row();
                                        if (!$t) {
                                            $abuse = true;
                                        }
                                    }
                                    if (!isset($abuse)) {
                                        $reply_id = $this->add_reply($data, $tid, null, $attachments);
                                        if ($reply_id) {
                                            // Dont change this line
                                            $mailstatus = 'Ticket Reply Imported Successfully';
                                        }
                                    } else {
                                        $mailstatus = 'Ticket ID Not Found For User';
                                    }
                                } else {
                                    if (get_option('email_piping_only_registered') == 1 && !$userid) {
                                        $mailstatus = 'Blocked Ticket Opening from Unregistered User';
                                    } else {
                                        if (get_option('email_piping_only_replies') == '1') {
                                            $mailstatus = 'Only Replies Allowed by Email';
                                        } else {
                                            $data               = [];
                                            $data['department'] = $department_id;
                                            $data['subject']    = $subject;
                                            $data['message']    = $message;
                                            $data['contactid']  = $contactid;
                                            $data['priority']   = get_option('email_piping_default_priority');
                                            if ($userid == false) {
                                                $data['name']  = $name;
                                                $data['email'] = $email;
                                            } else {
                                                $data['userid'] = $userid;
                                            }
                                            $tid = $this->add($data, null, $attachments);
                                            // Dont change this line
                                            $mailstatus = 'Ticket Imported Successfully';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($mailstatus == '') {
            $mailstatus = 'Ticket Import Failed';
        }
        $this->db->insert(db_prefix() . 'tickets_pipe_log', [
            'date'     => date('Y-m-d H:i:s'),
            'email_to' => $to,
            'name'     => $name,
            'email'    => $email,
            'subject'  => $subject,
            'message'  => $message,
            'status'   => $mailstatus,
        ]);

        return $mailstatus;
    }

    private function process_pipe_attachments($attachments, $ticket_id, $reply_id = '')
    {
        if (!empty($attachments)) {
            $ticket_attachments = [];
            $allowed_extensions = explode(',', get_option('ticket_attachments_file_extensions'));

            $path = FCPATH . 'uploads/ticket_attachments' . '/' . $ticket_id . '/';

            foreach ($attachments as $attachment) {
                $filename      = $attachment['filename'];
                $filenameparts = explode('.', $filename);
                $extension     = end($filenameparts);
                $extension     = strtolower($extension);
                if (in_array('.' . $extension, $allowed_extensions)) {
                    $filename = implode(array_slice($filenameparts, 0, 0 - 1));
                    $filename = trim(preg_replace('/[^a-zA-Z0-9-_ ]/', '', $filename));
                    if (!$filename) {
                        $filename = 'attachment';
                    }
                    if (!file_exists($path)) {
                        mkdir($path, 0755);
                        $fp = fopen($path . 'index.html', 'w');
                        fclose($fp);
                    }
                    $filename = unique_filename($path, $filename . '.' . $extension);
                    $fp       = fopen($path . $filename, 'w');
                    fwrite($fp, $attachment['data']);
                    fclose($fp);
                    array_push($ticket_attachments, [
                        'file_name' => $filename,
                        'filetype'  => get_mime_by_extension($filename),
                    ]);
                }
            }
            $this->insert_ticket_attachments_to_database($ticket_attachments, $ticket_id, $reply_id);
        }
    }

    public function get($id = '', $where = [])
    {
        $this->db->select('*,' . db_prefix() . 'tickets.userid,' . db_prefix() . 'tickets.name as from_name,' . db_prefix() . 'tickets.email as ticket_email, ' . db_prefix() . 'departments.name as department_name, ' . db_prefix() . 'tickets_priorities.name as priority_name, statuscolor, ' . db_prefix() . 'tickets.admin, ' . db_prefix() . 'services.name as service_name, service, ' . db_prefix() . 'tickets_status.name as status_name,' . db_prefix() . 'tickets.ticketid, ' . db_prefix() . 'contacts.firstname as user_firstname, ' . db_prefix() . 'contacts.lastname as user_lastname,' . db_prefix() . 'staff.firstname as staff_firstname, ' . db_prefix() . 'staff.lastname as staff_lastname,lastreply,message,' . db_prefix() . 'tickets.status,subject,department,priority,' . db_prefix() . 'contacts.email,adminread,clientread,date');
        $this->db->join(db_prefix() . 'departments', db_prefix() . 'departments.departmentid = ' . db_prefix() . 'tickets.department', 'left');
        $this->db->join(db_prefix() . 'tickets_status', db_prefix() . 'tickets_status.ticketstatusid = ' . db_prefix() . 'tickets.status', 'left');
        $this->db->join(db_prefix() . 'services', db_prefix() . 'services.serviceid = ' . db_prefix() . 'tickets.service', 'left');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'tickets.userid', 'left');
        $this->db->join(db_prefix() . 'contacts', db_prefix() . 'contacts.id = ' . db_prefix() . 'tickets.contactid', 'left');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'tickets.admin', 'left');
        $this->db->join(db_prefix() . 'tickets_priorities', db_prefix() . 'tickets_priorities.priorityid = ' . db_prefix() . 'tickets.priority', 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'tickets.ticketid', $id);

            return $this->db->get(db_prefix() . 'tickets')->row();
        }
        $this->db->order_by('lastreply', 'asc');

        return $this->db->get(db_prefix() . 'tickets')->result_array();
    }

    /**
     * Get ticket by id and all data
     * @param  mixed  $id     ticket id
     * @param  mixed $userid Optional - Tickets from USER ID
     * @return object
     */
    public function get_ticket_by_id($id, $userid = '')
    {
        $this->db->select('*, ' . db_prefix() . 'tickets.userid, ' . db_prefix() . 'tickets.name as from_name, ' . db_prefix() . 'tickets.email as ticket_email, ' . db_prefix() . 'departments.name as department_name, ' . db_prefix() . 'tickets_priorities.name as priority_name, statuscolor, ' . db_prefix() . 'tickets.admin, ' . db_prefix() . 'services.name as service_name, service, ' . db_prefix() . 'tickets_status.name as status_name, ' . db_prefix() . 'tickets.ticketid, ' . db_prefix() . 'contacts.firstname as user_firstname, ' . db_prefix() . 'contacts.lastname as user_lastname, ' . db_prefix() . 'staff.firstname as staff_firstname, ' . db_prefix() . 'staff.lastname as staff_lastname, lastreply, message, ' . db_prefix() . 'tickets.status, subject, department, priority, ' . db_prefix() . 'contacts.email, adminread, clientread, date');
        $this->db->from(db_prefix() . 'tickets');
        $this->db->join(db_prefix() . 'departments', db_prefix() . 'departments.departmentid = ' . db_prefix() . 'tickets.department', 'left');
        $this->db->join(db_prefix() . 'tickets_status', db_prefix() . 'tickets_status.ticketstatusid = ' . db_prefix() . 'tickets.status', 'left');
        $this->db->join(db_prefix() . 'services', db_prefix() . 'services.serviceid = ' . db_prefix() . 'tickets.service', 'left');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'tickets.userid', 'left');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'tickets.admin', 'left');
        $this->db->join(db_prefix() . 'contacts', db_prefix() . 'contacts.id = ' . db_prefix() . 'tickets.contactid', 'left');
        $this->db->join(db_prefix() . 'tickets_priorities', db_prefix() . 'tickets_priorities.priorityid = ' . db_prefix() . 'tickets.priority', 'left');
        $this->db->where(db_prefix() . 'tickets.ticketid', $id);
        if (is_numeric($userid)) {
            $this->db->where(db_prefix() . 'tickets.userid', $userid);
        }
        $ticket = $this->db->get()->row();
        if ($ticket) {
            if ($ticket->admin == null || $ticket->admin == 0) {
                if ($ticket->contactid != 0) {
                    $ticket->submitter = $ticket->user_firstname . ' ' . $ticket->user_lastname;
                } else {
                    $ticket->submitter = $ticket->from_name;
                }
            } else {
                if ($ticket->contactid != 0) {
                    $ticket->submitter = $ticket->user_firstname . ' ' . $ticket->user_lastname;
                } else {
                    $ticket->submitter = $ticket->from_name;
                }
                $ticket->opened_by = $ticket->staff_firstname . ' ' . $ticket->staff_lastname;
            }

            $ticket->attachments = $this->get_ticket_attachments($id);
        }


        return $ticket;
    }

    /**
     * Insert ticket attachments to database
     * @param  array  $attachments array of attachment
     * @param  mixed  $ticketid
     * @param  boolean $replyid If is from reply
     */
    public function insert_ticket_attachments_to_database($attachments, $ticketid, $replyid = false)
    {
        foreach ($attachments as $attachment) {
            $attachment['ticketid']  = $ticketid;
            $attachment['dateadded'] = date('Y-m-d H:i:s');
            if ($replyid !== false && is_int($replyid)) {
                $attachment['replyid'] = $replyid;
            }
            $this->db->insert(db_prefix() . 'ticket_attachments', $attachment);
        }
    }

    /**
     * Get ticket attachments from database
     * @param  mixed $id      ticket id
     * @param  mixed $replyid Optional - reply id if is from from reply
     * @return array
     */
    public function get_ticket_attachments($id, $replyid = '')
    {
        $this->db->where('ticketid', $id);
        if (is_numeric($replyid)) {
            $this->db->where('replyid', $replyid);
        } else {
            $this->db->where('replyid', null);
        }
        $this->db->where('ticketid', $id);

        return $this->db->get(db_prefix() . 'ticket_attachments')->result_array();
    }

    /**
     * Add new reply to ticket
     * @param mixed $data  reply $_POST data
     * @param mixed $id    ticket id
     * @param boolean $admin staff id if is staff making reply
     */
    public function add_reply($data, $id, $admin = null, $pipe_attachments = false)
    {
        if (isset($data['assign_to_current_user'])) {
            $assigned = get_staff_user_id();
            unset($data['assign_to_current_user']);
        }

        $unsetters = [
            'note_description',
            'department',
            'priority',
            'subject',
            'assigned',
            'project_id',
            'service',
            'status_top',
            'attachments',
            'DataTables_Table_0_length',
            'DataTables_Table_1_length',
            'custom_fields',
        ];

        foreach ($unsetters as $unset) {
            if (isset($data[$unset])) {
                unset($data[$unset]);
            }
        }

        if ($admin !== null) {
            $data['admin'] = $admin;
            $status        = $data['status'];
        } else {
            $status = 1;
        }

        if (isset($data['status'])) {
            unset($data['status']);
        }

        $cc = '';
        if (isset($data['cc'])) {
            $cc = $data['cc'];
            unset($data['cc']);
        }

        $data['ticketid'] = $id;
        $data['date']     = date('Y-m-d H:i:s');
        $data['message']  = trim($data['message']);

        if ($this->piping == true) {
            $data['message'] = preg_replace('/\v+/u', '<br>', $data['message']);
        }

        // admin can have html
        if ($admin == null) {
            $data['message'] = _strip_tags($data['message']);
            $data['message'] = nl2br_save_html($data['message']);
        }

        if (!isset($data['userid'])) {
            $data['userid'] = 0;
        }

        /*  if (is_client_logged_in()) {
                    $data['contactid'] = get_contact_user_id();
                }
        */

        $data = hooks()->apply_filters('before_ticket_reply_add', $data, $id, $admin);

        $this->db->insert(db_prefix() . 'ticket_replies', $data);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            if (isset($assigned)) {
                $this->db->where('ticketid', $id);
                $this->db->update(db_prefix() . 'tickets', [
                    'assigned' => $assigned,
                ]);
            }
            if ($pipe_attachments != false) {
                $this->process_pipe_attachments($pipe_attachments, $id, $insert_id);
            } else {
                $attachments = handle_ticket_attachments($id);
                if ($attachments) {
                    $this->tickets_model->insert_ticket_attachments_to_database($attachments, $id, $insert_id);
                }
            }

            $_attachments = $this->get_ticket_attachments($id, $insert_id);

            log_activity('New Ticket Reply [ReplyID: ' . $insert_id . ']');

            $this->db->select('status');
            $this->db->where('ticketid', $id);
            $old_ticket_status = $this->db->get(db_prefix() . 'tickets')->row()->status;

            /**
             * When a ticket is in status "In progress" and the customer reply to the ticket it changes the status to "Open" which is not normal.
             * The ticket should keep the status "In progress"
             */

            $this->db->where('ticketid', $id);
            $this->db->update(db_prefix() . 'tickets', [
                    'lastreply'  => date('Y-m-d H:i:s'),
                    'status'     => ($old_ticket_status == 2 && $admin == null ? $old_ticket_status : $status),
                    'adminread'  => 0,
                    'clientread' => 0,
                ]);

            if ($old_ticket_status != $status) {
                hooks()->do_action('after_ticket_status_changed', [
                        'id'     => $id,
                        'status' => $status,
                    ]);
            }

            $ticket    = $this->get_ticket_by_id($id);
            $userid    = $ticket->userid;
            $isContact = false;
            if ($ticket->userid != 0 && $ticket->contactid != 0) {
                $email     = $this->clients_model->get_contact($ticket->contactid)->email;
                $isContact = true;
            } else {
                $email = $ticket->ticket_email;
            }
            if ($admin == null) {
                $this->load->model('departments_model');
                $this->load->model('staff_model');
                $staff = $this->staff_model->get('', ['active' => 1]);

                $notifiedUsers                           = [];
                $notificationForStaffMemberOnTicketReply = get_option('receive_notification_on_new_ticket_replies') == 1;

                foreach ($staff as $staff_key => $member) {
                    if (get_option('access_tickets_to_none_staff_members') == 0
                         && !is_staff_member($member['staffid'])) {
                        continue;
                    }

                    $staff_departments = $this->departments_model->get_staff_departments($member['staffid'], true);

                    if (in_array($ticket->department, $staff_departments)) {
                        send_mail_template('ticket_new_reply_to_staff', $ticket, $member, $_attachments);

                        if ($notificationForStaffMemberOnTicketReply) {
                            $notified = add_notification([
                                    'description'     => 'not_new_ticket_reply',
                                    'touserid'        => $member['staffid'],
                                    'fromcompany'     => 1,
                                    'fromuserid'      => null,
                                    'link'            => 'tickets/ticket/' . $id,
                                    'additional_data' => serialize([
                                        $ticket->subject,
                                    ]),
                                ]);
                            if ($notified) {
                                array_push($notifiedUsers, $member['staffid']);
                            }
                        }
                    }
                }
                pusher_trigger_notification($notifiedUsers);
            } else {
                $sendEmail = true;
                if ($isContact && total_rows(db_prefix() . 'contacts', ['ticket_emails' => 1, 'id' => $ticket->contactid]) == 0) {
                    $sendEmail = false;
                }
                if ($sendEmail) {
                    send_mail_template('ticket_new_reply_to_customer', $ticket, $email, $_attachments, $cc);
                }
            }
            hooks()->do_action('after_ticket_reply_added', [
                'data'    => $data,
                'id'      => $id,
                'admin'   => $admin,
                'replyid' => $insert_id,
            ]);

            return $insert_id;
        }

        return false;
    }

    /**
     *  Delete ticket reply
     * @param   mixed $ticket_id    ticket id
     * @param   mixed $reply_id     reply id
     * @return  boolean
     */
    public function delete_ticket_reply($ticket_id, $reply_id)
    {
        $this->db->where('id', $reply_id);
        $this->db->delete(db_prefix() . 'ticket_replies');
        if ($this->db->affected_rows() > 0) {
            // Get the reply attachments by passing the reply_id to get_ticket_attachments method
            $attachments = $this->get_ticket_attachments($ticket_id, $reply_id);
            if (count($attachments) > 0) {
                foreach ($attachments as $attachment) {
                    $this->delete_ticket_attachment($attachment['id']);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Remove ticket attachment by id
     * @param  mixed $id attachment id
     * @return boolean
     */
    public function delete_ticket_attachment($id)
    {
        $deleted = false;
        $this->db->where('id', $id);
        $attachment = $this->db->get(db_prefix() . 'ticket_attachments')->row();
        if ($attachment) {
            if (unlink(get_upload_path_by_type('ticket') . $attachment->ticketid . '/' . $attachment->file_name)) {
                $this->db->where('id', $attachment->id);
                $this->db->delete(db_prefix() . 'ticket_attachments');
                $deleted = true;
            }
            // Check if no attachments left, so we can delete the folder also
            $other_attachments = list_files(get_upload_path_by_type('ticket') . $attachment->ticketid);
            if (count($other_attachments) == 0) {
                delete_dir(get_upload_path_by_type('ticket') . $attachment->ticketid);
            }
        }

        return $deleted;
    }

    /**
     * Get ticket attachment by id
     * @param  mixed $id attachment id
     * @return mixed
     */
    public function get_ticket_attachment($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'ticket_attachments')->row();
    }

    /**
     * This functions is used when staff open client ticket
     * @param  mixed $userid client id
     * @param  mixed $id     ticketid
     * @return array
     */
    public function get_user_other_tickets($userid, $id)
    {
        $this->db->select(db_prefix().'departments.name as department_name, '.db_prefix().'services.name as service_name,'.db_prefix().'tickets_status.name as status_name,'.db_prefix().'staff.firstname as staff_firstname, '.db_prefix().'clients.lastname as staff_lastname,ticketid,subject,firstname,lastname,lastreply');
        $this->db->from(db_prefix() . 'tickets');
        $this->db->join(db_prefix() . 'departments', db_prefix().'departments.departmentid = '.db_prefix().'tickets.department', 'left');
        $this->db->join(db_prefix() . 'tickets_status', db_prefix().'tickets_status.ticketstatusid = '.db_prefix().'tickets.status', 'left');
        $this->db->join(db_prefix() . 'services', db_prefix().'services.serviceid = '.db_prefix().'tickets.service', 'left');
        $this->db->join(db_prefix() . 'clients', db_prefix().'clients.userid = '.db_prefix().'tickets.userid', 'left');
        $this->db->join(db_prefix() . 'staff', db_prefix().'staff.staffid = '.db_prefix().'tickets.admin', 'left');
        $this->db->where(db_prefix() . 'tickets.userid', $userid);
        $this->db->where(db_prefix().'tickets.ticketid !=', $id);
        $tickets = $this->db->get()->result_array();
        $i       = 0;
        foreach ($tickets as $ticket) {
            $tickets[$i]['submitter'] = $ticket['firstname'] . ' ' . $ticket['lastname'];
            unset($ticket['firstname']);
            unset($ticket['lastname']);
            $i++;
        }

        return $tickets;
    }

    /**
     * Get all ticket replies
     * @param  mixed  $id     ticketid
     * @param  mixed $userid specific client id
     * @return array
     */
    public function get_ticket_replies($id)
    {
        $ticket_replies_order = get_option('ticket_replies_order');
        // backward compatibility for the action hook
        $ticket_replies_order = hooks()->apply_filters('ticket_replies_order', $ticket_replies_order);

        $this->db->select(db_prefix().'ticket_replies.id,'.db_prefix().'ticket_replies.name as from_name,'.db_prefix().'ticket_replies.email as reply_email, '.db_prefix().'ticket_replies.admin, '.db_prefix().'ticket_replies.userid,'.db_prefix().'staff.firstname as staff_firstname, '.db_prefix().'staff.lastname as staff_lastname,'.db_prefix().'contacts.firstname as user_firstname,'.db_prefix().'contacts.lastname as user_lastname,message,date,contactid');
        $this->db->from(db_prefix() . 'ticket_replies');
        $this->db->join(db_prefix() . 'clients', db_prefix().'clients.userid = '.db_prefix().'ticket_replies.userid', 'left');
        $this->db->join(db_prefix() . 'staff', db_prefix().'staff.staffid = '.db_prefix().'ticket_replies.admin', 'left');
        $this->db->join(db_prefix() . 'contacts', db_prefix().'contacts.id = '.db_prefix().'ticket_replies.contactid', 'left');
        $this->db->where('ticketid', $id);
        $this->db->order_by('date', $ticket_replies_order);
        $replies = $this->db->get()->result_array();
        $i       = 0;
        foreach ($replies as $reply) {
            if ($reply['admin'] !== null || $reply['admin'] != 0) {
                // staff reply
                $replies[$i]['submitter'] = $reply['staff_firstname'] . ' ' . $reply['staff_lastname'];
            } else {
                if ($reply['contactid'] != 0) {
                    $replies[$i]['submitter'] = $reply['user_firstname'] . ' ' . $reply['user_lastname'];
                } else {
                    $replies[$i]['submitter'] = $reply['from_name'];
                }
            }
            unset($replies[$i]['staff_firstname']);
            unset($replies[$i]['staff_lastname']);
            unset($replies[$i]['user_firstname']);
            unset($replies[$i]['user_lastname']);
            $replies[$i]['attachments'] = $this->get_ticket_attachments($id, $reply['id']);
            $i++;
        }

        return $replies;
    }

    /**
     * Add new ticket to database
     * @param mixed $data  ticket $_POST data
     * @param mixed $admin If admin adding the ticket passed staff id
     */
    public function add($data, $admin = null, $pipe_attachments = false)
    {
        if ($admin !== null) {
            $data['admin'] = $admin;
            unset($data['ticket_client_search']);
        }

        if (isset($data['assigned']) && $data['assigned'] == '') {
            $data['assigned'] = 0;
        }

        if (isset($data['project_id']) && $data['project_id'] == '') {
            $data['project_id'] = 0;
        }

        if ($admin == null) {
            if (isset($data['email'])) {
                $data['userid']    = 0;
                $data['contactid'] = 0;
            } else {
                // Opened from customer portal otherwise is passed from pipe or admin area
                if (!isset($data['userid']) && !isset($data['contactid'])) {
                    $data['userid']    = get_client_user_id();
                    $data['contactid'] = get_contact_user_id();
                }
            }
            $data['status'] = 1;
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        // CC is only from admin area
        $cc = '';
        if (isset($data['cc'])) {
            $cc = $data['cc'];
            unset($data['cc']);
        }

        $data['date']      = date('Y-m-d H:i:s');
        $data['ticketkey'] = md5(uniqid(time(), true));
        $data['status']    = 1;
        $data['message']   = trim($data['message']);
        $data['subject']   = trim($data['subject']);
        if ($this->piping == true) {
            $data['message'] = preg_replace('/\v+/u', '<br>', $data['message']);
        }
        // Admin can have html
        if ($admin == null) {
            $data['message'] = _strip_tags($data['message']);
            $data['subject'] = _strip_tags($data['subject']);
            $data['message'] = nl2br_save_html($data['message']);
        }
        if (!isset($data['userid'])) {
            $data['userid'] = 0;
        }
        if (isset($data['priority']) && $data['priority'] == '' || !isset($data['priority'])) {
            $data['priority'] = 0;
        }

        $tags = '';
        if (isset($data['tags'])) {
            $tags = $data['tags'];
            unset($data['tags']);
        }

        $data = hooks()->apply_filters('before_ticket_created', $data, $admin);

        $this->db->insert(db_prefix() . 'tickets', $data);
        $ticketid = $this->db->insert_id();
        if ($ticketid) {
            handle_tags_save($tags, $ticketid, 'ticket');

            if (isset($custom_fields)) {
                handle_custom_fields_post($ticketid, $custom_fields);
            }

            if (isset($data['assigned']) && $data['assigned'] != 0) {
                if ($data['assigned'] != get_staff_user_id()) {
                    $notified = add_notification([
                        'description'     => 'not_ticket_assigned_to_you',
                        'touserid'        => $data['assigned'],
                        'fromcompany'     => 1,
                        'fromuserid'      => null,
                        'link'            => 'tickets/ticket/' . $ticketid,
                        'additional_data' => serialize([
                            $data['subject'],
                        ]),
                    ]);

                    if ($notified) {
                        pusher_trigger_notification([$data['assigned']]);
                    }

                    send_mail_template('ticket_assigned_to_staff', $assignedEmail, $data['assigned'], $ticketid, $data['userid'], $data['contactid']);
                }
            }
            if ($pipe_attachments != false) {
                $this->process_pipe_attachments($pipe_attachments, $ticketid);
            } else {
                $attachments = handle_ticket_attachments($ticketid);
                if ($attachments) {
                    $this->insert_ticket_attachments_to_database($attachments, $ticketid);
                }
            }

            $_attachments = $this->get_ticket_attachments($ticketid);


            $isContact = false;
            if (isset($data['userid']) && $data['userid'] != false) {
                $email     = $this->clients_model->get_contact($data['contactid'])->email;
                $isContact = true;
            } else {
                $email = $data['email'];
            }

            $template = 'ticket_created_to_customer';
            if ($admin == null) {
                $template = 'ticket_autoresponse';

                $this->load->model('departments_model');
                $this->load->model('staff_model');
                $staff = $this->staff_model->get('', ['active' => 1]);

                $notifiedUsers                              = [];
                $notificationForStaffMemberOnTicketCreation = get_option('receive_notification_on_new_ticket') == 1;

                foreach ($staff as $member) {
                    if (get_option('access_tickets_to_none_staff_members') == 0
                        && !is_staff_member($member['staffid'])) {
                        continue;
                    }
                    $staff_departments = $this->departments_model->get_staff_departments($member['staffid'], true);

                    if (in_array($data['department'], $staff_departments)) {
                        send_mail_template('ticket_created_to_staff', $ticketid, $data['userid'], $data['contactid'], $member, $_attachments);

                        if ($notificationForStaffMemberOnTicketCreation) {
                            $notified = add_notification([
                                    'description'     => 'not_new_ticket_created',
                                    'touserid'        => $member['staffid'],
                                    'fromcompany'     => 1,
                                    'fromuserid'      => null,
                                    'link'            => 'tickets/ticket/' . $ticketid,
                                    'additional_data' => serialize([
                                        $data['subject'],
                                    ]),
                                ]);
                            if ($notified) {
                                array_push($notifiedUsers, $member['staffid']);
                            }
                        }
                    }
                }
                pusher_trigger_notification($notifiedUsers);
            }

            $sendEmail = true;

            if ($isContact && total_rows(db_prefix() . 'contacts', ['ticket_emails' => 1, 'id' => $data['contactid']]) == 0) {
                $sendEmail = false;
            }

            if ($sendEmail) {
                $ticket = $this->get_ticket_by_id($ticketid);
                // $admin == null ? [] : $_attachments - Admin opened ticket from admin area add the attachments to the email
                send_mail_template($template, $ticket, $email, $admin == null ? [] : $_attachments, $cc);
            }

            hooks()->do_action('ticket_created', $ticketid);
            log_activity('New Ticket Created [ID: ' . $ticketid . ']');

            return $ticketid;
        }

        return false;
    }

    /**
     * Get latest 5 client tickets
     * @param  integer $limit  Optional limit tickets
     * @param  mixed $userid client id
     * @return array
     */
    public function get_client_latests_ticket($limit = 5, $userid = '')
    {
        $this->db->select(db_prefix().'tickets.userid, ticketstatusid, statuscolor, '.db_prefix().'tickets_status.name as status_name,'.db_prefix().'tickets.ticketid, subject, date');
        $this->db->from(db_prefix() . 'tickets');
        $this->db->join(db_prefix() . 'tickets_status', db_prefix().'tickets_status.ticketstatusid = '.db_prefix().'tickets.status', 'left');
        if (is_numeric($userid)) {
            $this->db->where(db_prefix() . 'tickets.userid', $userid);
        } else {
            $this->db->where(db_prefix() . 'tickets.userid', get_client_user_id());
        }
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Delete ticket from database and all connections
     * @param  mixed $ticketid ticketid
     * @return boolean
     */
    public function delete($ticketid)
    {
        $affectedRows = 0;
        hooks()->do_action('before_ticket_deleted', $ticketid);
        // final delete ticket
        $this->db->where('ticketid', $ticketid);
        $this->db->delete(db_prefix() . 'tickets');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            $this->db->where('ticketid', $ticketid);
            $attachments = $this->db->get(db_prefix() . 'ticket_attachments')->result_array();
            if (count($attachments) > 0) {
                if (is_dir(get_upload_path_by_type('ticket') . $ticketid)) {
                    if (delete_dir(get_upload_path_by_type('ticket') . $ticketid)) {
                        foreach ($attachments as $attachment) {
                            $this->db->where('id', $attachment['id']);
                            $this->db->delete(db_prefix() . 'ticket_attachments');
                            if ($this->db->affected_rows() > 0) {
                                $affectedRows++;
                            }
                        }
                    }
                }
            }

            $this->db->where('relid', $ticketid);
            $this->db->where('fieldto', 'tickets');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            // Delete replies
            $this->db->where('ticketid', $ticketid);
            $this->db->delete(db_prefix() . 'ticket_replies');

            $this->db->where('rel_id', $ticketid);
            $this->db->where('rel_type', 'ticket');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_id', $ticketid);
            $this->db->where('rel_type', 'ticket');
            $this->db->delete(db_prefix() . 'taggables');

            $this->db->where('rel_type', 'ticket');
            $this->db->where('rel_id', $ticketid);
            $this->db->delete(db_prefix() . 'reminders');

            // Get related tasks
            $this->db->where('rel_type', 'ticket');
            $this->db->where('rel_id', $ticketid);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
        }
        if ($affectedRows > 0) {
            log_activity('Ticket Deleted [ID: ' . $ticketid . ']');

            return true;
        }

        return false;
    }

    /**
     * Update ticket data / admin use
     * @param  mixed $data ticket $_POST data
     * @return boolean
     */
    public function update_single_ticket_settings($data)
    {
        $affectedRows = 0;
        $data         = hooks()->apply_filters('before_ticket_settings_updated', $data);

        $ticketBeforeUpdate = $this->get_ticket_by_id($data['ticketid']);

        if (isset($data['custom_fields']) && count($data['custom_fields']) > 0) {
            if (handle_custom_fields_post($data['ticketid'], $data['custom_fields'])) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        $tags = '';
        if (isset($data['tags'])) {
            $tags = $data['tags'];
            unset($data['tags']);
        }

        if (handle_tags_save($tags, $data['ticketid'], 'ticket')) {
            $affectedRows++;
        }

        if (isset($data['priority']) && $data['priority'] == '' || !isset($data['priority'])) {
            $data['priority'] = 0;
        }

        if ($data['assigned'] == '') {
            $data['assigned'] = 0;
        }

        if (isset($data['project_id']) && $data['project_id'] == '') {
            $data['project_id'] = 0;
        }

        if (isset($data['contactid']) && $data['contactid'] != '') {
            $data['name']  = null;
            $data['email'] = null;
        }

        $this->db->where('ticketid', $data['ticketid']);
        $this->db->update(db_prefix() . 'tickets', $data);
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action(
                'ticket_settings_updated',
            [
                'ticket_id'       => $data['ticketid'],
                'original_ticket' => $ticketBeforeUpdate,
                'data'            => $data, ]
            );
            $affectedRows++;
        }

        $sendAssignedEmail = false;

        $current_assigned = $ticketBeforeUpdate->assigned;
        if ($current_assigned != 0) {
            if ($current_assigned != $data['assigned']) {
                if ($data['assigned'] != 0 && $data['assigned'] != get_staff_user_id()) {
                    $sendAssignedEmail = true;
                    $notified          = add_notification([
                        'description'     => 'not_ticket_reassigned_to_you',
                        'touserid'        => $data['assigned'],
                        'fromcompany'     => 1,
                        'fromuserid'      => null,
                        'link'            => 'tickets/ticket/' . $data['ticketid'],
                        'additional_data' => serialize([
                            $data['subject'],
                        ]),
                    ]);
                    if ($notified) {
                        pusher_trigger_notification([$data['assigned']]);
                    }
                }
            }
        } else {
            if ($data['assigned'] != 0 && $data['assigned'] != get_staff_user_id()) {
                $sendAssignedEmail = true;
                $notified          = add_notification([
                    'description'     => 'not_ticket_assigned_to_you',
                    'touserid'        => $data['assigned'],
                    'fromcompany'     => 1,
                    'fromuserid'      => null,
                    'link'            => 'tickets/ticket/' . $data['ticketid'],
                    'additional_data' => serialize([
                        $data['subject'],
                    ]),
                ]);

                if ($notified) {
                    pusher_trigger_notification([$data['assigned']]);
                }
            }
        }
        if ($sendAssignedEmail === true) {
            $this->db->where('staffid', $data['assigned']);
            $assignedEmail = $this->db->get(db_prefix() . 'staff')->row()->email;

            send_mail_template('ticket_assigned_to_staff', $assignedEmail, $data['assigned'], $data['ticketid'], $data['userid'], $data['contactid']);
        }
        if ($affectedRows > 0) {
            log_activity('Ticket Updated [ID: ' . $data['ticketid'] . ']');

            return true;
        }

        return false;
    }

    /**
     * C<ha></ha>nge ticket status
     * @param  mixed $id     ticketid
     * @param  mixed $status status id
     * @return array
     */
    public function change_ticket_status($id, $status)
    {
        $this->db->where('ticketid', $id);
        $this->db->update(db_prefix() . 'tickets', [
            'status' => $status,
        ]);
        $alert   = 'warning';
        $message = _l('ticket_status_changed_fail');
        if ($this->db->affected_rows() > 0) {
            $alert   = 'success';
            $message = _l('ticket_status_changed_successfully');
            hooks()->do_action('after_ticket_status_changed', [
                'id'     => $id,
                'status' => $status,
            ]);
        }

        return [
            'alert'   => $alert,
            'message' => $message,
        ];
    }

    // Priorities

    /**
     * Get ticket priority by id
     * @param  mixed $id priority id
     * @return mixed     if id passed return object else array
     */
    public function get_priority($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('priorityid', $id);

            return $this->db->get(db_prefix() . 'tickets_priorities')->row();
        }

        return $this->db->get(db_prefix() . 'tickets_priorities')->result_array();
    }

    /**
     * Add new ticket priority
     * @param array $data ticket priority data
     */
    public function add_priority($data)
    {
        $this->db->insert(db_prefix() . 'tickets_priorities', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Ticket Priority Added [ID: ' . $insert_id . ', Name: ' . $data['name'] . ']');
        }

        return $insert_id;
    }

    /**
     * Update ticket priority
     * @param  array $data ticket priority $_POST data
     * @param  mixed $id   ticket priority id
     * @return boolean
     */
    public function update_priority($data, $id)
    {
        $this->db->where('priorityid', $id);
        $this->db->update(db_prefix() . 'tickets_priorities', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Ticket Priority Updated [ID: ' . $id . ' Name: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete ticket priorit
     * @param  mixed $id ticket priority id
     * @return mixed
     */
    public function delete_priority($id)
    {
        $current = $this->get($id);
        // Check if the priority id is used in tickets table
        if (is_reference_in_table('priority', db_prefix() . 'tickets', $id)) {
            return [
                'referenced' => true,
            ];
        }
        $this->db->where('priorityid', $id);
        $this->db->delete(db_prefix() . 'tickets_priorities');
        if ($this->db->affected_rows() > 0) {
            if (get_option('email_piping_default_priority') == $id) {
                update_option('email_piping_default_priority', '');
            }
            log_activity('Ticket Priority Deleted [ID: ' . $id . ']');

            return true;
        }

        return false;
    }

    // Predefined replies

    /**
     * Get predefined reply  by id
     * @param  mixed $id predefined reply id
     * @return mixed if id passed return object else array
     */
    public function get_predefined_reply($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'tickets_predefined_replies')->row();
        }

        return $this->db->get(db_prefix() . 'tickets_predefined_replies')->result_array();
    }

    /**
     * Add new predefined reply
     * @param array $data predefined reply $_POST data
     */
    public function add_predefined_reply($data)
    {
        $this->db->insert(db_prefix() . 'tickets_predefined_replies', $data);
        $insertid = $this->db->insert_id();
        log_activity('New Predefined Reply Added [ID: ' . $insertid . ', ' . $data['name'] . ']');

        return $insertid;
    }

    /**
     * Update predefined reply
     * @param  array $data predefined $_POST data
     * @param  mixed $id   predefined reply id
     * @return boolean
     */
    public function update_predefined_reply($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'tickets_predefined_replies', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Predefined Reply Updated [ID: ' . $id . ', ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete predefined reply
     * @param  mixed $id predefined reply id
     * @return boolean
     */
    public function delete_predefined_reply($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'tickets_predefined_replies');
        if ($this->db->affected_rows() > 0) {
            log_activity('Predefined Reply Deleted [' . $id . ']');

            return true;
        }

        return false;
    }

    // Ticket statuses

    /**
     * Get ticket status by id
     * @param  mixed $id status id
     * @return mixed     if id passed return object else array
     */
    public function get_ticket_status($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('ticketstatusid', $id);

            return $this->db->get(db_prefix() . 'tickets_status')->row();
        }
        $this->db->order_by('statusorder', 'asc');

        return $this->db->get(db_prefix() . 'tickets_status')->result_array();
    }

    /**
     * Add new ticket status
     * @param array ticket status $_POST data
     * @return mixed
     */
    public function add_ticket_status($data)
    {
        $this->db->insert(db_prefix() . 'tickets_status', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Ticket Status Added [ID: ' . $insert_id . ', ' . $data['name'] . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Update ticket status
     * @param  array $data ticket status $_POST data
     * @param  mixed $id   ticket status id
     * @return boolean
     */
    public function update_ticket_status($data, $id)
    {
        $this->db->where('ticketstatusid', $id);
        $this->db->update(db_prefix() . 'tickets_status', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Ticket Status Updated [ID: ' . $id . ' Name: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete ticket status
     * @param  mixed $id ticket status id
     * @return mixed
     */
    public function delete_ticket_status($id)
    {
        $current = $this->get_ticket_status($id);
        // Default statuses cant be deleted
        if ($current->isdefault == 1) {
            return [
                'default' => true,
            ];
        // Not default check if if used in table
        } elseif (is_reference_in_table('status', db_prefix() . 'tickets', $id)) {
            return [
                'referenced' => true,
            ];
        }
        $this->db->where('ticketstatusid', $id);
        $this->db->delete(db_prefix() . 'tickets_status');
        if ($this->db->affected_rows() > 0) {
            log_activity('Ticket Status Deleted [ID: ' . $id . ']');

            return true;
        }

        return false;
    }

    // Ticket services
    public function get_service($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('serviceid', $id);

            return $this->db->get(db_prefix() . 'services')->row();
        }

        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'services')->result_array();
    }

    public function add_service($data)
    {
        $this->db->insert(db_prefix() . 'services', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Ticket Service Added [ID: ' . $insert_id . '.' . $data['name'] . ']');
        }

        return $insert_id;
    }

    public function update_service($data, $id)
    {
        $this->db->where('serviceid', $id);
        $this->db->update(db_prefix() . 'services', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Ticket Service Updated [ID: ' . $id . ' Name: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    public function delete_service($id)
    {
        if (is_reference_in_table('service', db_prefix() . 'tickets', $id)) {
            return [
                'referenced' => true,
            ];
        }
        $this->db->where('serviceid', $id);
        $this->db->delete(db_prefix() . 'services');
        if ($this->db->affected_rows() > 0) {
            log_activity('Ticket Service Deleted [ID: ' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * @return array
     * Used in home dashboard page
     * Displays weekly ticket openings statistics (chart)
     */
    public function get_weekly_tickets_opening_statistics()
    {
        $departments_ids = [];
        if (!is_admin()) {
            if (get_option('staff_access_only_assigned_departments') == 1) {
                $this->load->model('departments_model');
                $staff_deparments_ids = $this->departments_model->get_staff_departments(get_staff_user_id(), true);
                $departments_ids      = [];
                if (count($staff_deparments_ids) == 0) {
                    $departments = $this->departments_model->get();
                    foreach ($departments as $department) {
                        array_push($departments_ids, $department['departmentid']);
                    }
                } else {
                    $departments_ids = $staff_deparments_ids;
                }
            }
        }

        $chart = [
            'labels'   => get_weekdays(),
            'datasets' => [
                [
                    'label'           => _l('home_weekend_ticket_opening_statistics'),
                    'backgroundColor' => 'rgba(197, 61, 169, 0.5)',
                    'borderColor'     => '#c53da9',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                    ],
                ],
            ],
        ];

        $monday = new DateTime(date('Y-m-d', strtotime('monday this week')));
        $sunday = new DateTime(date('Y-m-d', strtotime('sunday this week')));

        $thisWeekDays = get_weekdays_between_dates($monday, $sunday);

        $byDepartments = count($departments_ids) > 0;
        if (isset($thisWeekDays[1])) {
            $i = 0;
            foreach ($thisWeekDays[1] as $weekDate) {
                $this->db->like('DATE(date)', $weekDate, 'after');
                if ($byDepartments) {
                    $this->db->where('department IN (SELECT departmentid FROM '.db_prefix().'staff_departments WHERE departmentid IN (' . implode(',', $departments_ids) . ') AND staffid="' . get_staff_user_id() . '")');
                }
                $chart['datasets'][0]['data'][$i] = $this->db->count_all_results(db_prefix() . 'tickets');

                $i++;
            }
        }

        return $chart;
    }

    public function get_tickets_assignes_disctinct()
    {
        return $this->db->query('SELECT DISTINCT(assigned) as assigned FROM '.db_prefix().'tickets WHERE assigned != 0')->result_array();
    }

    /**
     * Check for previous tickets opened by this email/contact and link to the contact
     * @param  string $email      email to check for
     * @param  mixed $contact_id the contact id to transfer the tickets
     * @return boolean
     */
    public function transfer_email_tickets_to_contact($email, $contact_id)
    {
        // Some users don't want to fill the email
        if (empty($email)) {
            return false;
        }

        $customer_id = get_user_id_by_contact_id($contact_id);

        $this->db->where('userid', 0)
                ->where('contactid', 0)
                ->where('admin IS NULL')
                ->where('email', $email);

        $this->db->update(db_prefix() . 'tickets', [
                    'email'     => null,
                    'name'      => null,
                    'userid'    => $customer_id,
                    'contactid' => $contact_id,
                ]);

        $this->db->where('userid', 0)
                ->where('contactid', 0)
                ->where('admin IS NULL')
                ->where('email', $email);

        $this->db->update(db_prefix() . 'ticket_replies', [
                    'email'     => null,
                    'name'      => null,
                    'userid'    => $customer_id,
                    'contactid' => $contact_id,
                ]);

        return true;
    }
}
