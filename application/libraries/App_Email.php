<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/App_mailer.php');

class App_Email extends App_mailer
{
    // Email Queue Table
    private $email_queue_table;

    // Status (pending, sending, sent, failed)
    private $status;

    /**
     * Constructor
     */
    public function __construct($config = [])
    {
        $this->email_queue_table = db_prefix() . 'mail_queue';
        parent::__construct($config);
    }

    public function set_smtp_user($value)
    {
        $value                         = (string) $value;
        $this->properties['smtp_user'] = $value;
        $this->_smtp_auth              = ($value != '' && $this->smtp_pass != '');
        if ($this->mailer_engine == 'phpmailer') {
            $this->phpmailer->Username = $value;
            $this->phpmailer->SMTPAuth = $this->_smtp_auth;
        }

        return $this;
    }

    public function set_smtp_pass($value)
    {
        $value                         = (string) $value;
        $this->properties['smtp_pass'] = $value;
        $this->_smtp_auth              = ($this->smtp_user != '' && $value != '');
        if ($this->mailer_engine == 'phpmailer') {
            $this->phpmailer->Password = $value;
            $this->phpmailer->SMTPAuth = $this->_smtp_auth;
        }

        return $this;
    }

    public function set_status($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get
     *
     * Get queue emails.
     * @return  mixed
     */
    public function get_queue_emails($limit = null, $offset = null)
    {
        if ($this->status != false) {
            $this->CI->db->where('q.status', $this->status);
        }
        $query = $this->CI->db->get("{$this->email_queue_table} q", $limit, $offset);

        return $query->result();
    }

    /**
     * Save
     *
     * Add queue email to database.
     * @return  mixed
     */
    public function send($skip_job = false)
    {
        $attachments = $this->mailer_engine == 'codeigniter' ? $this->_attachments : $this->phpmailer->getAttachments();

        $emailQueue          = get_option('email_queue_enabled');
        $queueSkipAttachment = get_option('email_queue_skip_with_attachments');

        if ($skip_job === true
            || $emailQueue == '0'
            || ($emailQueue == '1' && $queueSkipAttachment == '1' && count($attachments) > 0)
            || (defined('CRON') && !is_staff_logged_in())) {
            return parent::send();
        }

        $date = date('Y-m-d H:i:s');

        if ($this->mailer_engine == 'codeigniter') {
            $to      = is_array($this->_recipients) ? implode(', ', $this->_recipients) : $this->_recipients;
            $cc      = implode(', ', $this->_cc_array);
            $bcc     = implode(', ', $this->_bcc_array);
            $headers = serialize($this->_headers);
        } else {
            $to = $this->phpmailer->getToAddresses();
            $to = array_filter($to[0]);
            $to = is_array($to) ? implode(', ', $to) : $to;

            $ccMailer = $this->phpmailer->getCcAddresses();
            $cc       = '';
            foreach ($ccMailer as $ccAddress) {
                $cc .= $ccAddress[0] . ', ';
            }
            if ($cc != '') {
                $cc = rtrim($cc, ', ');
            }

            $bccMailer = $this->phpmailer->getBccAddresses();
            $bcc       = '';
            foreach ($bccMailer as $ccAddress) {
                $bcc .= $ccAddress[0] . ', ';
            }
            if ($bcc != '') {
                $bcc = rtrim($bcc, ', ');
            }

            $ReplyTo       = $this->phpmailer->getReplyToAddresses();
            $ReplyToString = '';
            foreach ($ReplyTo as $replyToEmail => $array) {
                $ReplyToString .= $replyToEmail . ', ';
            }

            $headers = $this->_headers;
            if ($ReplyToString != '') {
                $ReplyToString = rtrim($ReplyToString, ', ');

                $headers['replyTo'] = $ReplyToString;
            }

            $headers['from']     = $this->phpmailer->From;
            $headers['fromName'] = $this->phpmailer->FromName;
            $headers['subject']  = $this->phpmailer->Subject;

            $headers = serialize($headers);
        }

        $attachments = base64_encode(serialize($attachments));

        $dbdata = [
            'engine'      => $this->mailer_engine,
            'email'       => $to,
            'cc'          => $cc,
            'bcc'         => $bcc,
            'message'     => $this->_body,
            'alt_message' => $this->_get_alt_message(),
            'headers'     => $headers,
            'attachments' => $attachments,
            'status'      => 'pending',
            'date'        => $date,
        ];

        return $this->CI->db->insert($this->email_queue_table, $dbdata);
    }

    /**
     * Send queue
     *
     * Send queue emails.
     * @return  void
     */
    public function send_queue()
    {
        $this->CI->load->config('email');

        $this->clean_up_old_queue();

        $this->set_status('pending');
        $emails = $this->get_queue_emails();

        $this->CI->db->where('status', 'pending');
        $this->CI->db->set('status', 'sending');
        $this->CI->db->set('date', date('Y-m-d H:i:s'));

        $this->CI->db->update($this->email_queue_table);

        foreach ($emails as $email) {
            $this->set_mailer_engine($email->engine);

            $recipients = explode(', ', $email->email);
            $cc         = !empty($email->cc) ? explode(', ', $email->cc) : [];

            $bcc     = !empty($email->bcc) ? explode(', ', $email->bcc) : [];
            $headers = unserialize($email->headers);

            if ($email->engine == 'codeigniter') {
                if ($email->attachments) {
                    $attachments = unserialize(base64_decode($email->attachments));
                    foreach ($attachments as $attachment) {
                        $this->_attachments[] = $attachment;
                    }
                }
                $this->_headers = $headers;

                if (array_key_exists('Reply-To', $this->_headers) && !empty($this->_headers['Reply-To'])) {
                    $this->_replyto_flag = true;
                }
            } else {
                if ($email->attachments) {
                    $attachments = unserialize(base64_decode($email->attachments));
                    foreach ($attachments as $attachment) {
                        $this->phpmailer->addStringAttachment($attachment[0], $attachment[1], 'base64', $attachment[4], $attachment[6]);
                    }
                }
                $this->from($headers['from'], $headers['fromName']);
                $this->subject($headers['subject']);
                if (isset($headers['replyTo'])) {
                    $replyTo = !empty($headers['replyTo']) ? explode(', ', $headers['replyTo']) : [];

                    foreach ($replyTo as $replyToEmail) {
                        $this->reply_to($replyToEmail);
                    }
                }
            }

            $this->set_newline(config_item('newline'));
            $this->set_crlf(config_item('crlf'));

            $this->to($recipients);
            $this->cc($cc);
            $this->bcc($bcc);

            $this->message($email->message);
            $this->set_alt_message($email->alt_message);

            $status = ($this->send(true) ? 'sent' : 'failed');
            $this->clear(true);

            if ($email->engine == 'codeigniter') {
                $this->_attachments = [];
            } else {
                $this->phpmailer->clearAttachments();
            }

            $this->clear(true);

            $this->CI->db->where('id', $email->id);
            $this->CI->db->set('status', $status);
            $this->CI->db->set('date', date('Y-m-d H:i:s'));
            $this->CI->db->update($this->email_queue_table);
        }
    }

    /**
     * Retry failed emails
     *
     * Resend failed or expired emails
     * @return void
     */
    public function retry_queue()
    {
        $expire      = (time() - (60 * 5));
        $date_expire = date('Y-m-d H:i:s', $expire);
        $this->CI->db->set('status', 'pending');
        $this->CI->db->where("(date < '{$date_expire}' AND status = 'sending')");
        $this->CI->db->or_where("status = 'failed'");
        $this->CI->db->update($this->email_queue_table);
        log_message('debug', 'Email queue retrying...');
    }

    /**
     * Will remove queue rows from database that are sent and are older then 1 week
     * This function will remove also pending rows that are not sent for 3 days, probably the user cron job is not working properly or the email is not
     * working properly, removing these rows will help hundreds of stuck emails to be sent at once when user configure cron job or email.
     * @return null
     */
    public function clean_up_old_queue()
    {
        $this->CI->db->query('DELETE FROM ' . $this->email_queue_table . ' WHERE (status = "sent" AND date < "' . date('Y-m-d H:i:s', strtotime('-1 week')) . '") OR (status="pending" AND date < "' . date('Y-m-d H:i:s', strtotime('-3 day')) . '")');
    }
}
