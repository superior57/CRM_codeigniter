<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_mail_template
{
    /**
     * Email template slug
     * @var string
     */
    public $slug = '';

    /**
     * Email to send to
     * @var string
     */
    public $send_to;

    /**
     * Email CC
     * @var string
     */
    public $cc = '';

    /**
     * The merge fields for the email template
     * @var array
     */
    public $merge_fields = [];

    /**
     * Attachments
     * @var array
     */
    public $attachments = [];

    /**
     * Relation ID, e.q. invoice id
     * @var mixed
     */
    public $rel_id;

    /**
     * Relation type, e.q invoice
     * @var string
     */
    public $rel_type;

    /**
     * If mail is sent to staff member, set staff id
     * @var mixed
     */
    public $staff_id;

    /**
     * Codeigniter instance
     * @var object
     */
    protected $ci;

    /**
     * The actual template object from database
     * @var object
     */
    protected $template;

    /**
     * Parent template should set $for property so the sending script can identify whether this email is for the customer or staff
     * Allowed values: customer, staff;
     * @var string
     */
    protected $for;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * Send mail template
     * @return boolean
     */
    public function send()
    {
        if (!method_exists($this, 'build')) {
            show_error('Mail class "' . get_class($this) . '" must contain "build" method.');
        }

        $GLOBALS['SENDING_EMAIL_TEMPLATE_CLASS'] = $this;

        $this->build();

        $this->send_to = hooks()->apply_filters('send_email_template_to', $this->send_to);

        $this->template = $this->prepare();

        if ($this->is_user_inactive()) {
            return false;
        }

        /**
         * Template not found?
         */
        if (!$this->template) {
            log_activity('Failed to send email template [Template Not Found]');
            $this->clear();

            return false;
        }

        /**
         * Template is disabled or invalid email?
         * Log activity
         */
        if (!$this->validate()) {
            hooks()->do_action('failed_to_send_email_template', [
                 'template'     => $this->template,
                 'send_to'      => $this->send_to,
                 'merge_fields' => $this->merge_fields,
               ]);

            $this->clear();

            return false;
        }

        $this->template = hooks()->apply_filters('before_parse_email_template_message', $this->template);

        $this->template = parse_email_template($this->template, $this->merge_fields);

        $this->template = hooks()->apply_filters('after_parse_email_template_message', $this->template);

        $this->template->message = get_option('email_header') . $this->template->message . get_option('email_footer');

        // Parse merge fields again in case there is merge fields found in email_header and email_footer option.
        // We cant parse this in parse_email_template function because in case the template content is send via $_POST wont work
        $this->template = parse_email_template_merge_fields($this->template, $this->merge_fields);

        /**
         * Template is plain text?
         */
        if ($this->template->plaintext == 1) {
            $this->ci->config->set_item('mailtype', 'text');
            $this->template->message = strip_html_tags($this->template->message, '<br/>, <br>, <br />');
        }

        $hook_data['template']    = $this->template;
        $hook_data['email']       = $this->send_to;
        $hook_data['attachments'] = $this->attachments;

        $hook_data['template']->message = $this->template->plaintext != 1
        ? check_for_links($hook_data['template']->message)
        : $hook_data['template']->message;

        $hook_data = hooks()->apply_filters('before_email_template_send', $hook_data);

        $this->template    = $hook_data['template'];
        $this->send_to     = $hook_data['email'];
        $this->attachments = $hook_data['attachments'];

        if (isset($this->template->prevent_sending) && $this->template->prevent_sending == true) {
            $this->clear();

            return false;
        }

        $this->ci->load->config('email');

        $this->ci->email->clear(true);
        $this->ci->email->set_newline(config_item('newline'));

        $from = $this->_from();

        $this->ci->email->from($from['fromemail'], $from['fromname']);

        $this->ci->email->subject($this->_subject());

        $this->ci->email->message($this->template->message);
        $this->ci->email->to($this->send_to);

        if (is_array($this->cc) || !empty($this->cc)) {
            $this->ci->email->cc($this->cc);
        }

        $this->_bcc();

        if ($reply_to = $this->_reply_to()) {
            $this->ci->email->reply_to($reply_to);
        }

        $this->_alt_message();

        $this->_attachments();

        if ($this->ci->email->send()) {
            log_activity('Email Send To [Email: ' . $this->send_to . ', Template: ' . $this->template->name . ']');

            hooks()->do_action('email_template_sent', [
                'template'     => $this->template,
                'email'        => $this->send_to,
                'merge_fields' => $this->merge_fields,
            ]);

            $this->clear();

            return true;
        }

        if (ENVIRONMENT !== 'production') {
            log_activity('Failed to send email template - ' . $this->ci->email->print_debugger());
        }

        return false;
    }

    /**
     * Return for who this email is intended
     * @return mixed
     */
    public function is_for($for)
    {
        return $this->for === $for;
    }

    /**
     * Sets mail alt message
     * @return null
     */
    protected function _alt_message()
    {
        if ($this->template->plaintext == 0) {
            $alt_message = strip_html_tags($this->template->message, '<br/>, <br>, <br />');
            // Replace <br /> with \n
            $alt_message = clear_textarea_breaks($alt_message, "\r\n");
            $this->ci->email->set_alt_message($alt_message);
        }
    }

    /**
     * Set the mail attachments
     * @return null
     */
    protected function _attachments()
    {
        if (count($this->attachments) > 0) {
            foreach ($this->attachments as $attachment) {
                !isset($attachment['read'])
                ? $this->ci->email->attach($attachment['attachment'], 'attachment', $attachment['filename'], $attachment['type'])
                : $this->ci->email->attach($attachment['attachment'], '', $attachment['filename']);
            }
        }
    }

    /**
     * Get template subject
     * @return string
     */
    protected function _subject()
    {
        return $this->template->subject;
    }

    /**
     * Get template reply to header
     * @return mixed
     */
    protected function _reply_to()
    {
        return isset($this->template->reply_to) ? $this->template->reply_to : null;
    }

    /**
     * Get template from header
     * @return array
     */
    protected function _from()
    {
        if (hooks()->apply_filters('use_deprecated_from_email_header_template_field', false)) {
            $fromemail = $this->template->fromemail;
            $fromname  = $this->template->fromname;


            if ($fromemail == '') {
                $fromemail = get_option('smtp_email');
            }

            if ($fromname == '') {
                $fromname = get_option('companyname');
            }

        return [
            'fromemail' => $fromemail,
            'fromname'  => $fromname,
        ];
        }

        return [
                'fromemail' => get_option('smtp_email'),
                'fromname'  => $this->template->fromname != '' ? $this->template->fromname : get_option('companyname'),
            ];
    }

    /**
     * Validate the template and the email
     * @return boolean
     */
    private function validate()
    {
        if ($this->template->active == 0 || !valid_email($this->send_to)) {
            return false;
        }

        return true;
    }

    /**
     * Set template BCC
     */
    private function _bcc()
    {
        $bcc = '';
        // Used for action hooks
        if (isset($this->template->bcc)) {
            $bcc = $this->template->bcc;
            if (is_array($bcc)) {
                $bcc = implode(', ', $bcc);
            }
        }

        $systemBCC = get_option('bcc_emails');

        if ($systemBCC != '') {
            if ($bcc != '') {
                $bcc .= ', ' . $systemBCC;
            } else {
                $bcc .= $systemBCC;
            }
        }

        if ($bcc != '') {
            $bcc = array_map('trim', explode(',', $bcc));
            $bcc = array_unique($bcc);
            $bcc = implode(', ', $bcc);
            $this->ci->email->bcc($bcc);
        }
    }

    /**
     * Check whether the user is active or inactive
     * Valid for customers and for staff members, this function performs the check based on the email and the template slug
     * @return boolean
     */
    private function is_user_inactive()
    {
        $inactive_user_table_check = '';

        /**
         * Dont send email templates for non active contacts/staff
         * Do checking here
         */
        if ($this->for === 'staff') {
            $inactive_user_table_check = db_prefix() . 'staff';
        } elseif ($this->for === 'customer') {
            $inactive_user_table_check = db_prefix() . 'contacts';
        }

        /**
         * Is really inactive?
         */
        if ($inactive_user_table_check != '') {
            $this->ci->db->select('active')->where('email', $this->send_to);
            $user = $this->ci->db->get($inactive_user_table_check)->row();
            if ($user && $user->active == 0) {
                $this->clear();

                return true;
            }
        }

        return false;
    }

    /**
     * Get reflection class default property
     * @param  string $property  property name
     * @param  string $className className
     * @param  array  $params    option mail class params
     * @return mixed
     */
    public function get_default_property_value($property, $className, $params = [])
    {
        $properties = $this->getReflectionClassDefaultProperties($className, $params);

        return isset($properties[$property]) ? $properties[$property] : false;
    }

    /**
     * Based on the template slug and email the function will fetch a template from database
     * The template will be fetched on the language that should be sent
     * @param  string $template_slug
     * @param  string $email
     * @return object
     */
    public function prepare($email = null, $template = null, $params = [])
    {
        $slug  = $this->slug;
        $email = $email === null ? $this->send_to : $email;

        if ($template) {
            $slug = $this->get_default_property_value('slug', $template, $params);
        }

        $language = $this->get_language($email, $template, $params);

        if (!is_dir(APPPATH . 'language/' . $language)) {
            $language = 'english';
        }

        if (!class_exists('emails_model', false)) {
            $this->ci->load->model('emails_model');
        }

        $template = $this->ci->emails_model->get(['language' => $language, 'slug' => $slug], 'row');

        // Template languages not yet inserted
        // Users needs to visit Setup->Email Templates->Any template to initialize all languages
        if (!$template) {
            $template = $this->ci->emails_model->get(['language' => 'english', 'slug' => $slug], 'row');
        } else {
            if ($template && $template->message == '') {
                // Template message blank use the active language default template
                $template = $this->ci->emails_model->get(['language' => get_option('active_language'), 'slug' => $slug], 'row');

                if ($template->message == '') {
                    $template = $this->ci->emails_model->get(['language' => 'english', 'slug' => $slug], 'row');
                }
            }
        }

        return $template;
    }

    /**
     * Function that will return in what language the email template should be sent
     * @param  string $template_slug the template slug
     * @param  string $email         email that this template will be sent
     * @return string
     */
    private function get_language($email, $template = null, $params = [])
    {
        $language = get_option('active_language');

        $for      = $this->for;
        $rel_type = $this->rel_type;

        if ($template) {
            $for      = $this->get_default_property_value('for', $template, $params);
            $rel_type = $this->get_default_property_value('rel_type', $template, $params);
        }

        if ($rel_type != 'proposal' && $rel_type != 'lead') {
            if ($for === 'customer' && total_rows(db_prefix() . 'contacts', ['email' => $email]) > 0) {
                $this->ci->db->where('email', $email);

                $contact = $this->ci->db->get(db_prefix() . 'contacts')->row();
                $lang    = get_client_default_language($contact->userid);

                if ($lang != '') {
                    $language = $lang;
                }
            } elseif ($for === 'staff' && total_rows(db_prefix() . 'staff', ['email' => $email]) > 0) {
                $this->ci->db->where('email', $email);
                $staff = $this->ci->db->get(db_prefix() . 'staff')->row();

                $lang = get_staff_default_language($staff->staffid);
                if ($lang != '') {
                    $language = $lang;
                }
            }
        } elseif ($rel_type == 'lead') {
            $this->ci->db->select('default_language');
            $this->ci->db->where('id', $this->get_rel_id());
            $lead = $this->ci->db->get(db_prefix() . 'leads')->row();
        } elseif ($rel_type == 'proposal') {
            $this->ci->db->select('rel_type, rel_id');
            $this->ci->db->where('id', $this->get_rel_id());
            $proposal = $this->ci->db->get(db_prefix() . 'proposals')->row();
            if ($proposal && $proposal->rel_type == 'lead') {
                $this->ci->db->select('default_language')
                ->where('id', $proposal->rel_id);

                $lead = $this->ci->db->get(db_prefix() . 'leads')->row();
            } elseif ($proposal && $proposal->rel_type == 'customer') {
                $customerDefault = get_client_default_language($proposal->rel_id);
                if (!empty($customerDefault)) {
                    $language = $customerDefault;
                }
            }
        }

        if (isset($lead) && $lead && !empty($lead->default_language)) {
            $language = $lead->default_language;
        }

        return hooks()->apply_filters('email_template_language', $language, ['template' => $this, 'email' => $email]);
    }

    /**
     * Set template merge fields
     * @param array $fields
     */
    public function set_merge_fields($fields, ...$params)
    {
        if (!is_array($fields)) {
            $fields = $this->ci->app_merge_fields->format_feature($fields, ...$params);
        }

        $this->merge_fields = array_merge($this->merge_fields, $fields);

        return $this;
    }

    /**
     * Get template merge fields
     * @return array
     */
    public function get_merge_fields()
    {
        return $this->merge_fields;
    }

    /**
     * Set template CC header
     * @param  mixed $cc
     * @return object
     */
    public function cc($cc)
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * Set template TO email header
     * @param  string $email
     * @return object
     */
    public function to($email)
    {
        $this->send_to = $email;

        return $this;
    }

    /**
     * @param array
     * @return object App_send_mail
     * Add attachment to property to check before an email is send
     */
    public function add_attachment($attachment)
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * @return object App_send_mail
     * Clear all attachment properties
     */
    private function clear_attachments()
    {
        $this->attachments = [];

        return $this;
    }

    /**
     * Set template relation id
     * @param mixed $rel_id
     */
    public function set_rel_id($rel_id)
    {
        $this->rel_id = $rel_id;

        return $this;
    }

    /**
     * Get template relation id
     * @return mixed x
     */
    public function get_rel_id()
    {
        return $this->rel_id;
    }

    /**
     * Set template relation type
     * @param string $rel_type
     */
    public function set_rel_type($rel_type)
    {
        $this->rel_type = $rel_type;

        return $this;
    }

    /**
     * Get template relation typ
     * @return string
     */
    public function get_rel_type()
    {
        return $this->rel_type;
    }

    /**
     * Set template staff id
     * @param mixed $id
     */
    public function set_staff_id($id)
    {
        $this->staff_id = $id;

        return $this;
    }

    /**
     * Get template staff id
     * @return mixed
     */
    public function get_staff_id()
    {
        return $this->staff_id;
    }

    private function createReflectionMailClass($className, $params = [])
    {
        include_once(get_mail_template_path($className, $params));

        return new ReflectionClass($className);
    }

    private function getReflectionClassDefaultProperties($className, $params = [])
    {
        $reflection = $this->createReflectionMailClass($className, $params);

        return $reflection->getDefaultProperties();
    }

    /**
     * Clear template data
     * @return null
     */
    private function clear()
    {
        $this->clear_attachments();

        $this->set_staff_id(null);
        $this->set_rel_type(null);
        $this->set_rel_id(null);
    }
}
