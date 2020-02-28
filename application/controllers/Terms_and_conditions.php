<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Terms_and_conditions extends ClientsController
{
    public function index()
    {
        $data['terms'] = get_option('terms_and_conditions');
        $data['title'] = _l('terms_and_conditions') . ' - ' . get_option('companyname');
        $this->data($data);
        $this->view('terms_and_conditions');
        $this->layout();
    }
}
