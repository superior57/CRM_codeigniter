<?php

namespace app\services;

defined('BASEPATH') or exit('No direct script access allowed');

trait ValidatesContact
{
    protected function validateContact()
    {
        if (!is_client_logged_in()) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }

        if (is_client_logged_in() && !is_contact_email_verified()) {
            redirect(site_url('verification'));
        }
    }
}
