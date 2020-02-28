<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Lead_web_form_submitted extends App_mail_template
{
    protected $lead;

    public $slug = 'new-web-to-lead-form-submitted';

    public $rel_type = 'lead';

    public function __construct($lead)
    {
        parent::__construct();
        $this->lead = $lead;
    }

    public function build()
    {
        $this->to($this->lead->email)
        ->set_rel_id($this->lead->id)
        ->set_merge_fields('leads_merge_fields', $this->lead);
    }
}
